<?php

namespace Arnipay\Tests\Integration;

use Arnipay\Arnipay;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 */
class IntegrationTestCase extends TestCase
{
    /**
     * @var Arnipay
     */
    protected $arnipay;

    /**
     * @var bool
     */
    protected $isSandbox = false;

    protected function setUp(): void
    {
        // Skip tests if environment variables are not set
        if (!isset($_ENV['CLIENT_ID']) || !isset($_ENV['PRIVATE_KEY'])) {
            $this->markTestSkipped('Required environment variables not set');
        }

        // Initialize Arnipay
        $this->arnipay = new Arnipay(
            $_ENV['CLIENT_ID'],
            $_ENV['PRIVATE_KEY'],
            $this->isSandbox
        );

        if (isset($_ENV['API_BASE_URL'])) {
            $this->arnipay->getClient()->setBaseUrl($_ENV['API_BASE_URL'], false);
        }
    }
}
