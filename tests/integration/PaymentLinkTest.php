<?php

namespace Arnipay\Tests\Integration;

use Arnipay\Gateway\Client;
use Arnipay\Gateway\PaymentLink;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 */
class PaymentLinkTest extends TestCase
{
    private $client;
    private $paymentLink;
    private $testLinkId;

    protected function setUp(): void
    {
        // Skip tests if environment variables are not set
        if (!isset($_ENV['CLIENT_ID']) || !isset($_ENV['PRIVATE_KEY']) || !isset($_ENV['API_BASE_URL'])) {
            $this->markTestSkipped('Required environment variables not set');
        }

        // Create a real client instance with actual environment variables
        $this->client = new Client(
            $_ENV['CLIENT_ID'],
            $_ENV['PRIVATE_KEY']
        );

        $this->client->setBaseUrl($_ENV['API_BASE_URL'], false);

        $this->paymentLink = new PaymentLink($this->client);
    }

    public function testCreate()
    {
        // Call the actual API to create a payment link
        $result = $this->paymentLink->create(
            150000,
            'Test Subscription',
            'Test description created at ' . date('Y-m-d H:i:s'),
            [
                'payment_methods' => ['qr', 'tigo'],
                'reference' => 'TEST-REF-' . uniqid()
            ]
        );

        // Save the link ID for later tests
        $this->testLinkId = $result['id'];

        // Verify the structure of the response
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('price', $result);

        // Check the content matches what we sent
        $this->assertEquals('Test Subscription', $result['title']);
        $this->assertEquals(150000, $result['price']);
    }

    public function testGet()
    {
        // If we don't have a link ID from the create test, create one now
        if (empty($this->testLinkId)) {
            $createResult = $this->paymentLink->create(
                150000,
                'Test Subscription for Get',
                'Test description for Get',
                ['reference' => 'TEST-REF-GET-' . uniqid()]
            );
            $this->testLinkId = $createResult['id'];
        }

        // Call the get method with the real link ID
        $result = $this->paymentLink->get($this->testLinkId);

        // Verify the structure and content of the response
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('price', $result);
        $this->assertArrayHasKey('is_paid', $result);

        $this->assertEquals($this->testLinkId, $result['id']);
        $this->assertEquals(150000, $result['price']);
    }

    public function testList()
    {
        // Call the list method
        $result = $this->paymentLink->list();

        // Basic validation of the response structure
        $this->assertIsArray($result);

        // If we have results, check the structure of the first item
        if (count($result) > 0) {
            $this->assertArrayHasKey('id', $result[0]);
            $this->assertArrayHasKey('title', $result[0]);
            $this->assertArrayHasKey('price', $result[0]);
            $this->assertArrayHasKey('is_paid', $result[0]);
        } else {
            $this->markTestSkipped('No payment links found, cannot verify structure');
        }
    }

    /**
     * This helps ensure that tests run in the right order
     * Create should run first, then Get, then List
     */
    public static function suite()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Payment Link Test Suite');
        $suite->addTest(new PaymentLinkTest('testCreate'));
        $suite->addTest(new PaymentLinkTest('testGet'));
        $suite->addTest(new PaymentLinkTest('testList'));
        return $suite;
    }
}
