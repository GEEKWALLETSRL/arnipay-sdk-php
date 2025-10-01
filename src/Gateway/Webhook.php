<?php

namespace Arnipay\Gateway;

use Arnipay\Exception\GatewayException;

class Webhook
{
    /**
     * @var string
     */
    protected $webhookSecret;

    /**
     * @var SignatureService
     */
    protected $signatureService;

    /**
     * Webhook constructor.
     *
     * @param string $webhookSecret Your webhook secret key
     */
    public function __construct(string $webhookSecret)
    {
        $this->webhookSecret = $webhookSecret;
        $this->signatureService = new SignatureService();
    }

    /**
     * Validate the webhook signature using the canonical string
     *
     * @param string $method HTTP method used for the webhook request
     * @param string $requestUri Request URI (path + optional query, no scheme/host)
     * @param string $timestamp Timestamp from X-Timestamp header
     * @param string $clientId Client identifier from X-Client-ID header
     * @param string $payload Raw request payload
     * @param string $signature Signature from X-Signature header
     * @return bool Whether the signature is valid
     */
    public function validateSignature(string $method, string $requestUri, string $timestamp, string $clientId, string $payload, string $signature): bool
    {
        $expectedSignature = $this->signatureService->generate(
            $method,
            $requestUri,
            (int) $timestamp,
            $clientId,
            $this->webhookSecret,
            $payload
        );

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process webhook event
     *
     * @param string $method HTTP method used for the webhook request
     * @param string $requestUri Request URI (path + optional query)
     * @param string $timestamp Timestamp from X-Timestamp header
     * @param string $clientId Client identifier from X-Client-ID header
     * @param string $payload Raw request payload
     * @param string $signature Signature from X-Signature header
     * @return array Processed event data or empty array if invalid
     */
    public function processEvent(string $method, string $requestUri, string $timestamp, string $clientId, string $payload, string $signature): array
    {
        if (!$this->validateSignature($method, $requestUri, $timestamp, $clientId, $payload, $signature)) {
            throw new GatewayException('Invalid webhook signature', 401);
        }

        $event = json_decode($payload, true);

        if ($event === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new GatewayException('Invalid JSON payload', 400);
        }

        if (!is_array($event) || !isset($event['event']) || !isset($event['data'])) {
            throw new GatewayException('Invalid webhook payload', 422);
        }

        return $event;
    }
}
