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
        $methods = $this->arnipay->getPaymentMethods();

        $this->assertIsArray($methods);

        // We expect at least some payment methods to be returned if the environment is set up correctly
        $this->assertNotEmpty($methods);

        // Verify structure of the first item
        $firstMethod = $methods[0];
        $this->assertArrayHasKey('code', $firstMethod);
        $this->assertArrayHasKey('name', $firstMethod);
    }
}
