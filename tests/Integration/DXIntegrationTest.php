<?php

namespace Arnipay\Tests\Integration;

/**
 * @group integration
 */
class DXIntegrationTest extends IntegrationTestCase
{
    protected $isSandbox = true;

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
