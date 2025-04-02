<?php

namespace GwSdk\Tests;

use GwSdk\Gateway\Webhook;
use PHPUnit\Framework\TestCase;

class WebhookTest extends TestCase
{
    private $webhook;
    private $webhookSecret;

    protected function setUp(): void
    {
        $this->webhookSecret = $_ENV['WEBHOOK_SECRET'] ?? 'test-webhook-secret';
        $this->webhook = new Webhook($this->webhookSecret);
    }

    public function testValidateSignatureWithValidSignature()
    {
        $payload = json_encode([
            'event' => 'payment.completed',
            'timestamp' => '2023-01-01T00:00:00Z',
            'data' => [
                'link_id' => '550e8400-e29b-41d4-a716-446655440000',
                'payment_id' => '12345',
                'status' => 'paid',
                'amount' => 150000
            ]
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, $this->webhookSecret);

        $result = $this->webhook->validateSignature($payload, $signature);

        $this->assertTrue($result);
    }

    public function testValidateSignatureWithInvalidSignature()
    {
        $payload = json_encode([
            'event' => 'payment.completed',
            'timestamp' => '2023-01-01T00:00:00Z',
            'data' => [
                'link_id' => '550e8400-e29b-41d4-a716-446655440000',
                'payment_id' => '12345',
                'status' => 'paid',
                'amount' => 150000
            ]
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'wrong-secret');

        $result = $this->webhook->validateSignature($payload, $signature);

        $this->assertFalse($result);
    }

    public function testProcessEventWithValidSignature()
    {
        $payload = json_encode([
            'event' => 'payment.completed',
            'timestamp' => '2023-01-01T00:00:00Z',
            'data' => [
                'link_id' => '550e8400-e29b-41d4-a716-446655440000',
                'payment_id' => '12345',
                'status' => 'paid',
                'amount' => 150000
            ]
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, $this->webhookSecret);

        $result = $this->webhook->processEvent($payload, $signature);

        $this->assertEquals('payment.completed', $result['event']);
        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $result['data']['link_id']);
        $this->assertEquals('12345', $result['data']['payment_id']);
        $this->assertEquals('paid', $result['data']['status']);
        $this->assertEquals(150000, $result['data']['amount']);
    }

    public function testProcessEventWithInvalidSignature()
    {
        $payload = json_encode([
            'event' => 'payment.completed',
            'timestamp' => '2023-01-01T00:00:00Z',
            'data' => [
                'link_id' => '550e8400-e29b-41d4-a716-446655440000',
                'payment_id' => '12345',
                'status' => 'paid',
                'amount' => 150000
            ]
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'wrong-secret');

        $result = $this->webhook->processEvent($payload, $signature);

        $this->assertEmpty($result);
    }

    public function testProcessEventWithInvalidPayload()
    {
        $payload = 'not-a-json-string';
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $this->webhookSecret);

        $result = $this->webhook->processEvent($payload, $signature);

        $this->assertEmpty($result);
    }

    public function testProcessEventWithMissingFields()
    {
        $payload = json_encode([
            'timestamp' => '2023-01-01T00:00:00Z'
            // Missing event and data fields
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, $this->webhookSecret);

        $result = $this->webhook->processEvent($payload, $signature);

        $this->assertEmpty($result);
    }
}
