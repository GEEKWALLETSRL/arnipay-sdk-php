<?php

namespace Arnipay;

use Arnipay\Gateway\Client;
use Arnipay\Gateway\PaymentBuilder;
use Arnipay\Gateway\PaymentLink;
use Arnipay\Gateway\Transaction;
use Arnipay\Gateway\WebhookHandler;

class Arnipay
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @param string $clientId
     * @param string $privateKey
     * @param bool $isSandbox Whether to use the sandbox environment (default: false)
     */
    public function __construct(string $clientId, string $privateKey, bool $isSandbox = false)
    {
        $this->client = new Client($clientId, $privateKey);

        if ($isSandbox) {
            $this->client->setBaseUrl(Client::SANDBOX_BASE_URL, false);
        }
    }

    /**
     * Fluent payment-link builder
     */
    public function payment(): PaymentBuilder
    {
        return new PaymentBuilder($this->client);
    }

    /**
     * Payment-link service (create/get/list/reverse)
     */
    public function paymentLinks(): PaymentLink
    {
        return new PaymentLink($this->client);
    }

    /**
     * Webhook handler (validates + invokes callback)
     */
    public function webhook(string $secret): WebhookHandler
    {
        return new WebhookHandler($secret);
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function transaction(): Transaction
    {
        return new Transaction($this->client);
    }

    /**
     * @return array
     * @throws Exception\GatewayException
     */
    public function getPaymentMethods(): array
    {
        $response = $this->client->request('GET', '/payment_methods');
        return $response['data'] ?? [];
    }
}
