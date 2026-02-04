<?php

namespace Arnipay\Tests;

use Arnipay\Gateway\Client;
use Arnipay\Gateway\PaymentLink;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;

class PaymentReversalUnitTest extends TestCase
{
    use PHPMock;

    /**
     * @test
     */
    public function itCanReversePayment()
    {
        // Mock the client
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/payment/test-uuid/reverse',
                ['reason' => 'Customer requested refund']
            )
            ->willReturn([
                'status' => 'success',
                'message' => 'Reversal process initiated',
                'data' => [
                    'id' => 'test-uuid',
                    'status' => 'processing_refund'
                ]
            ]);

        $paymentLink = new PaymentLink($client);
        $response = $paymentLink->reverse('test-uuid', 'Customer requested refund');

        $this->assertEquals('processing_refund', $response['status']);
        $this->assertEquals('test-uuid', $response['id']);
    }

    /**
     * @test
     */
    public function itCanReversePaymentWithoutReason()
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/payment/test-uuid/reverse',
                []
            )
            ->willReturn([
                'data' => ['status' => 'processing_refund']
            ]);

        $paymentLink = new PaymentLink($client);
        $paymentLink->reverse('test-uuid');
    }
}
