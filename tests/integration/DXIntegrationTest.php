<?php

namespace Arnipay\Tests\Integration;

use Arnipay\Arnipay;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 */
class DXIntegrationTest extends TestCase
{
    /**
     * @var Arnipay
     */
    private $arnipay;

    protected function setUp(): void
    {
        // Skip tests if environment variables are not set
        if (!isset($_ENV['CLIENT_ID']) || !isset($_ENV['PRIVATE_KEY'])) {
            $this->markTestSkipped('Required environment variables not set');
        }

        // Initialize Arnipay with sandbox mode = true
        $this->arnipay = new Arnipay(
            $_ENV['CLIENT_ID'],
            $_ENV['PRIVATE_KEY'],
            true
        );

        // Override base URL to match the test environment configuration
        // This ensures the test runs against the correct environment (e.g. mocked server or specific sandbox)
        // even though we initialized with sandbox=true to test the facade.
        if (isset($_ENV['API_BASE_URL'])) {
            $this->arnipay->getClient()->setBaseUrl($_ENV['API_BASE_URL'], false);
        }
    }

    public function testFluentPaymentCreation()
    {
        $reference = 'REF-DX-' . uniqid();
        
        $url = $this->arnipay->payment()
            ->amount(150000)
            ->title('DX Integration Test')
            ->description('Testing the Fluent Interface')
            ->redirect('https://example.com/success', 'https://example.com/failure')
            ->reference($reference)
            ->allow(['qr', 'tigo'])
            ->createUrl();

        $this->assertNotNull($url);
        $this->assertNotEmpty($url);
        $this->assertStringStartsWith('http', $url);

        echo "\nGenerated Payment URL (Fluent): " . $url . "\n";
    }

    public function testPaymentBuilderCreateReturnsArray()
    {
        $reference = 'REF-DX-ARRAY-' . uniqid();
        
        $result = $this->arnipay->payment()
            ->amount(100000)
            ->title('DX Integration Test (Array)')
            ->reference($reference)
            ->create();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals(100000, $result['price']);
        $this->assertEquals('DX Integration Test (Array)', $result['title']);
    }
}
