<?php

namespace Arnipay\Tests;

use Arnipay\Gateway\Webhook;
use Arnipay\Exception\GatewayException;
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
        $method = 'POST';
        $requestUri = '/webhook/test?foo=bar';
        $timestamp = (string) 1690000000;
        $clientId = 'demo-client';

        $payload = json_encode([
            'event' => 'payment.completed',
            'timestamp' => '2023-01-01T00:00:00Z',
            'data' => [
                'link_id' => '550e8400-e29b-41d4-a716-446655440000',
                'payment_id' => '12345',
                'status' => 'paid',
                'amount' => 150000
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $bodyHash = base64_encode(hash('sha256', $payload, true));
        $canonical = implode("\n", [
            strtoupper($method),
            $requestUri,
            $timestamp,
            $clientId,
            $bodyHash,
        ]);
        $signature = hash_hmac('sha256', $canonical, $this->webhookSecret);

        $result = $this->webhook->validateSignature($method, $requestUri, $timestamp, $clientId, $payload, $signature);

        $this->assertTrue($result);
    }

    public function testValidateSignatureWithInvalidSignature()
    {
        $method = 'POST';
        $requestUri = '/webhook/test';
        $timestamp = (string) 1690000001;
        $clientId = 'demo-client';

        $payload = json_encode([
            'event' => 'payment.completed',
            'timestamp' => '2023-01-01T00:00:00Z',
            'data' => [
                'link_id' => '550e8400-e29b-41d4-a716-446655440000',
                'payment_id' => '12345',
                'status' => 'paid',
                'amount' => 150000
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $bodyHash = base64_encode(hash('sha256', $payload, true));
        $canonical = implode("\n", [
            strtoupper($method),
            $requestUri,
            $timestamp,
            $clientId,
            $bodyHash,
        ]);
        $signature = hash_hmac('sha256', $canonical, 'wrong-secret');

        $result = $this->webhook->validateSignature($method, $requestUri, $timestamp, $clientId, $payload, $signature);

        $this->assertFalse($result);
    }

    public function testProcessEventWithValidSignature()
    {
        $method = 'POST';
        $requestUri = '/webhook/test';
        $timestamp = (string) 1690000002;
        $clientId = 'demo-client';

        $payload = json_encode([
            'event' => 'payment.completed',
            'timestamp' => '2023-01-01T00:00:00Z',
            'data' => [
                'link_id' => '550e8400-e29b-41d4-a716-446655440000',
                'payment_id' => '12345',
                'status' => 'paid',
                'amount' => 150000
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $bodyHash = base64_encode(hash('sha256', $payload, true));
        $canonical = implode("\n", [
            strtoupper($method),
            $requestUri,
            $timestamp,
            $clientId,
            $bodyHash,
        ]);
        $signature = hash_hmac('sha256', $canonical, $this->webhookSecret);

        $result = $this->webhook->processEvent($method, $requestUri, $timestamp, $clientId, $payload, $signature);

        $this->assertEquals('payment.completed', $result['event']);
        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $result['data']['link_id']);
        $this->assertEquals('12345', $result['data']['payment_id']);
        $this->assertEquals('paid', $result['data']['status']);
        $this->assertEquals(150000, $result['data']['amount']);
    }

    public function testProcessEventWithInvalidSignature()
    {
        $method = 'POST';
        $requestUri = '/webhook/test';
        $timestamp = (string) 1690000003;
        $clientId = 'demo-client';

        $payload = json_encode([
            'event' => 'payment.completed',
            'timestamp' => '2023-01-01T00:00:00Z',
            'data' => [
                'link_id' => '550e8400-e29b-41d4-a716-446655440000',
                'payment_id' => '12345',
                'status' => 'paid',
                'amount' => 150000
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $bodyHash = base64_encode(hash('sha256', $payload, true));
        $canonical = implode("\n", [
            strtoupper($method),
            $requestUri,
            $timestamp,
            $clientId,
            $bodyHash,
        ]);
        $signature = hash_hmac('sha256', $canonical, 'wrong-secret');

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Invalid webhook signature');
        $this->expectExceptionCode(401);

        $this->webhook->processEvent($method, $requestUri, $timestamp, $clientId, $payload, $signature);
    }

    public function testProcessEventWithInvalidPayload()
    {
        $method = 'POST';
        $requestUri = '/webhook/test';
        $timestamp = (string) 1690000004;
        $clientId = 'demo-client';

        $payload = 'not-a-json-string';
        $bodyHash = base64_encode(hash('sha256', $payload, true));
        $canonical = implode("\n", [
            strtoupper($method),
            $requestUri,
            $timestamp,
            $clientId,
            $bodyHash,
        ]);
        $signature = hash_hmac('sha256', $canonical, $this->webhookSecret);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Invalid JSON payload');
        $this->expectExceptionCode(400);

        $this->webhook->processEvent($method, $requestUri, $timestamp, $clientId, $payload, $signature);
    }

    public function testProcessEventWithMissingFields()
    {
        $method = 'POST';
        $requestUri = '/webhook/test';
        $timestamp = (string) 1690000005;
        $clientId = 'demo-client';

        $payload = json_encode([
            'timestamp' => '2023-01-01T00:00:00Z'
            // Missing event and data fields
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $bodyHash = base64_encode(hash('sha256', $payload, true));
        $canonical = implode("\n", [
            strtoupper($method),
            $requestUri,
            $timestamp,
            $clientId,
            $bodyHash,
        ]);
        $signature = hash_hmac('sha256', $canonical, $this->webhookSecret);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('Invalid webhook payload');
        $this->expectExceptionCode(422);

        $this->webhook->processEvent($method, $requestUri, $timestamp, $clientId, $payload, $signature);
    }
}
