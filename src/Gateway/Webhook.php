<?php

namespace GwSdk\Gateway;

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
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expectedSignature, $signature);
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
            return [];
        }

        $event = json_decode($payload, true);
        if (!$event || !isset($event['event']) || !isset($event['data'])) {
            return [];
        }

        return $event;
    }
}
