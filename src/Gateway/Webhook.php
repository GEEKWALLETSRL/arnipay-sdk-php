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
     * Webhook constructor.
     *
     * @param string $webhookSecret Your webhook secret key
     */
    public function __construct(string $webhookSecret)
    {
        $this->webhookSecret = $webhookSecret;
    }

    /**
     * Validate the webhook signature
     *
     * @param string $payload Raw request payload
     * @param string $signature Signature from X-Webhook-Signature header
     * @return bool Whether the signature is valid
     */
    public function validateSignature(string $payload, string $signature): bool
    {
        // Accept both raw hex and prefixed format like "sha256=..."
        $providedSignature = strpos($signature, 'sha256=') === 0
            ? substr($signature, 7)
            : $signature;

        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expectedSignature, $providedSignature);
    }

    /**
     * Process webhook event
     *
     * @param string $payload Raw request payload
     * @param string $signature Signature from X-Webhook-Signature header
     * @return array Processed event data or empty array if invalid
     */
    public function processEvent(string $payload, string $signature): array
    {
        if (!$this->validateSignature($payload, $signature)) {
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
