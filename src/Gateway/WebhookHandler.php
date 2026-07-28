<?php

namespace Arnipay\Gateway;

class WebhookHandler
{
    /**
     * @var string
     */
    protected $secret;

    /**
     * @param string $secret
     */
    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * Process the webhook request and return an event object.
     *
     * @param array|null $server Optional server data; defaults to $_SERVER
     * @param string|null $payload Optional payload; defaults to php://input
     * @param int $toleranceSeconds Reject timestamps older than this many seconds (0 = disable)
     * @return WebhookEvent
     * @throws \Arnipay\Exception\GatewayException
     */
    public function process(
        ?array $server = null,
        ?string $payload = null,
        int $toleranceSeconds = Webhook::DEFAULT_TOLERANCE_SECONDS
    ): WebhookEvent {
        $webhook = new Webhook($this->secret);
        return $webhook->handleRequest($server, $payload, $toleranceSeconds);
    }

    /**
     * Handle the webhook request with a callback.
     *
     * @param callable $callback
     * @param array|null $server Optional server data; defaults to $_SERVER
     * @param string|null $payload Optional payload; defaults to php://input
     * @param int $toleranceSeconds Reject timestamps older than this many seconds (0 = disable)
     * @return mixed
     * @throws \Arnipay\Exception\GatewayException
     */
    public function handle(
        callable $callback,
        ?array $server = null,
        ?string $payload = null,
        int $toleranceSeconds = Webhook::DEFAULT_TOLERANCE_SECONDS
    ) {
        $event = $this->process($server, $payload, $toleranceSeconds);
        return $callback($event);
    }
}
