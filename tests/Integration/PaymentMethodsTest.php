<?php

namespace Arnipay\Tests\Integration;

/**
 * @group integration
 */
class PaymentMethodsTest extends IntegrationTestCase
{
    /**
     * @test
     */
    public function itCanGetPaymentMethods()
    {
        try {
            $methods = $this->arnipay->getPaymentMethods();

            $this->assertIsArray($methods);
            
            // We expect at least some payment methods to be returned if the environment is set up correctly
            $this->assertNotEmpty($methods);
            
            // Verify structure of the first item
            $firstMethod = $methods[0];
            $this->assertArrayHasKey('code', $firstMethod);
            $this->assertArrayHasKey('name', $firstMethod);
        } catch (\Arnipay\Exception\GatewayException $e) {
            // Allow 404 if the endpoint is not yet deployed on the test environment
            if ($e->getStatusCode() === 404) {
                $this->markTestSkipped('getPaymentMethods endpoint not available (404)');
            }
            throw $e;
        }
    }
}
