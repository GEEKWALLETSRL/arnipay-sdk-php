<?php

namespace Arnipay\Tests\Unit;

use Arnipay\Gateway\Client;
use Arnipay\Gateway\PaymentLink;
use PHPUnit\Framework\TestCase;

class PaymentLinkTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Client
     */
    private $mockClient;
    private $paymentLink;
    private $testLinkId = 'mock-payment-link-id';

    protected function setUp(): void
    {
        // Create a mock client
        $this->mockClient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentLink = new PaymentLink($this->mockClient);
    }

    public function testCreate()
    {
        // Mock data matching the actual API response format
        $mockResponse = [
            'status' => 'success',
            'message' => 'Payment link created successfully',
            'data' => [
                'id' => $this->testLinkId,
                'url' => 'https://arnipay.com.py/checkout/' . $this->testLinkId,
                'commerce_id' => 123,
                'title' => 'Test Subscription',
                'price' => 150000,
                'created_at' => '2023-01-01T00:00:00Z'
            ]
        ];

        // Configure mock client to return the mock response
        $this->mockClient->method('request')
            ->willReturn($mockResponse);

        // Call the create method
        $result = $this->paymentLink->create(
            150000,
            'Test Subscription',
            'Test description',
            [
                'payment_methods' => ['qr', 'tigo'],
                'reference' => 'TEST-REF-123'
            ]
        );

        // Verify the structure of the response
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('price', $result);

        // Check the content matches what we expected
        $this->assertEquals('Test Subscription', $result['title']);
        $this->assertEquals(150000, $result['price']);
    }

    public function testGet()
    {
        // Mock data matching the actual API response format
        $mockResponse = [
            'status' => 'success',
            'data' => [
                'id' => $this->testLinkId,
                'url' => 'https://arnipay.com.py/checkout/' . $this->testLinkId,
                'commerce_id' => 123,
                'title' => 'Test Subscription',
                'price' => 150000,
                'description' => 'Test description',
                'enabled' => true,
                'payment_methods' => ['qr', 'tigo'],
                'reference' => 'TEST-REF-123',
                'stock' => null,
                'quantity' => null,
                'start_date' => null,
                'expiration_date' => null,
                'created_at' => '2023-01-01T00:00:00Z',
                'updated_at' => '2023-01-01T00:00:00Z',
                'is_paid' => false
            ]
        ];

        // Configure mock client to return the mock response
        $this->mockClient->method('request')
            ->willReturn($mockResponse);

        // Call the get method
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
        // Mock data matching the actual API response format
        $mockResponse = [
            'status' => 'success',
            'data' => [
                [
                    'id' => $this->testLinkId,
                    'url' => 'https://arnipay.com.py/checkout/' . $this->testLinkId,
                    'commerce_id' => 123,
                    'title' => 'Test Subscription',
                    'price' => 150000,
                    'description' => 'Test description',
                    'enabled' => true,
                    'payment_methods' => ['qr', 'tigo'],
                    'reference' => 'TEST-REF-123',
                    'is_paid' => false,
                    'created_at' => '2023-01-01T00:00:00Z'
                ]
            ]
        ];

        // Configure mock client to return the mock response
        $this->mockClient->method('request')
            ->willReturn($mockResponse);

        // Call the list method
        $result = $this->paymentLink->list();

        // Basic validation of the response structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('title', $result[0]);
        $this->assertArrayHasKey('price', $result[0]);
        $this->assertArrayHasKey('is_paid', $result[0]);
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
