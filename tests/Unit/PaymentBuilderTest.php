<?php

namespace Arnipay\Tests\Unit;

use Arnipay\Gateway\Client;
use Arnipay\Gateway\PaymentBuilder;
use PHPUnit\Framework\TestCase;

class PaymentBuilderTest extends TestCase
{
    public function testRequiresAmountAndTitleBeforeCreate()
    {
        $client = new Client('id', 'key');
        $builder = new PaymentBuilder($client);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment requires amount() and title() before create()');

        $builder->create();
    }

    public function testCreateUrlUsesPaymentLinkCreate()
    {
        $client = \Mockery::mock(Client::class);
        $client->shouldReceive('request')
            ->once()
            ->with('POST', '/payment', [
                'price' => 50000.0,
                'title' => 'Pizza',
                'description' => 'Two pies',
                'reference' => 'ORDER-1',
                'payment_methods' => ['qr', 'card'],
                'approved_redirection_url' => 'https://ok',
                'failed_redirection_url' => 'https://fail',
            ])
            ->andReturn([
                'data' => [
                    'id' => '1',
                    'url' => 'https://pay.test/1',
                ],
            ]);

        $builder = new PaymentBuilder($client);
        $url = $builder
            ->amount(50000)
            ->title('Pizza')
            ->description('Two pies')
            ->reference('ORDER-1')
            ->allow(['qr', 'card'])
            ->redirect('https://ok', 'https://fail')
            ->createUrl();

        $this->assertSame('https://pay.test/1', $url);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
