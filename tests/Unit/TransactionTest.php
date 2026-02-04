<?php

namespace Arnipay\Tests\Unit;

use Arnipay\Gateway\Client;
use Arnipay\Gateway\Transaction;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;

class TransactionTest extends TestCase
{
    use PHPMock;

    /**
     * @test
     */
    public function itCanListTransactions()
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('request')
            ->with('GET', '/transactions?link_payment_id=10&page=1')
            ->willReturn(['data' => []]);

        $transaction = new Transaction($client);
        $transaction->list(['link_payment_id' => 10, 'page' => 1]);
    }

    /**
     * @test
     */
    public function itCanGetTransaction()
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('request')
            ->with('GET', '/transactions/tx-123')
            ->willReturn(['data' => ['id' => 'tx-123']]);

        $transaction = new Transaction($client);
        $result = $transaction->get('tx-123');
        $this->assertEquals('tx-123', $result['id']);
    }

    /**
     * @test
     */
    public function itCanReverseTransaction()
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST', 
                '/transactions/tx-123/reverse',
                ['reason' => 'Refund']
            )
            ->willReturn(['data' => ['status' => 'processing_refund']]);

        $transaction = new Transaction($client);
        $transaction->reverse('tx-123', 'Refund');
    }
}
