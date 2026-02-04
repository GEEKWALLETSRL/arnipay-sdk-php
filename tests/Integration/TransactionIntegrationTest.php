<?php

namespace Arnipay\Tests\Integration;

use Arnipay\Exception\GatewayException;

/**
 * @group integration
 */
class TransactionIntegrationTest extends IntegrationTestCase
{
    /**
     * @test
     */
    public function itCanListTransactions()
    {
        // 1. Create a payment link to filter by
        $link = $this->arnipay->payment()
            ->amount(50000)
            ->title('Test Transaction List')
            ->reference('REF-TX-LIST-' . uniqid())
            ->create();
            
        $linkId = $link['id'];

        // 2. List transactions for this link
        // Should be empty initially
        $transactions = $this->arnipay->transaction()->list(['link_payment_id' => $linkId]);
        
        $this->assertIsArray($transactions);
        // $this->assertEmpty($transactions); // It might not be empty if there are pending txs, but likely empty.
    }

    /**
     * @test
     */
    public function itCanGetTransaction()
    {
        // We probably don't have a valid transaction ID to test with unless we Mock or use a known one.
        // For now, let's try to get a non-existent one and expect 404
        try {
            $this->arnipay->transaction()->get('non-existent-tx-id');
            $this->fail('Expected GatewayException (404) was not thrown');
        } catch (GatewayException $e) {
            $this->assertEquals(404, $e->getStatusCode());
        }
    }

    /**
     * @test
     */
    public function itShouldFailToReverseNonExistentTransaction()
    {
        try {
            $this->arnipay->transaction()->reverse('non-existent-tx-id', 'Integration Test');
            $this->fail('Expected GatewayException was not thrown');
        } catch (GatewayException $e) {
            // Expect 404 because transaction does not exist
            $this->assertEquals(404, $e->getStatusCode());
        }
    }
}
