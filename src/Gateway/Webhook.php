<?php

namespace Arnipay\Gateway;

use Arnipay\Exception\GatewayException;

class Webhook
{
    /**
     * Default max age for webhook timestamps (15 minutes). Set 0 to disable.
     */
    public const DEFAULT_TOLERANCE_SECONDS = 900;

    /**
     * @var string
     */
    protected $webhookSecret;

    /**
     * @var SignatureService
     */
    protected $signatureService;

    /**
     * @param string $webhookSecret Your webhook secret key
     */
    public function __construct(string $webhookSecret)
    {
        $this->webhookSecret = $webhookSecret;
        $this->signatureService = new SignatureService();
    }

    /**
     * Capture webhook request details from the PHP runtime or provided overrides.
     *
     * @param array|null $server Optional server data; defaults to $_SERVER
     * @param string|null $payload Optional payload; defaults to php://input contents
     * @return array{method:string,requestUri:string,timestamp:string,clientId:string,payload:string,signature:string}
     */
    public function captureRequest(?array $server = null, ?string $payload = null): array
    {
        $server = $server ?? $_SERVER;

        if ($payload === null) {
            $input = file_get_contents('php://input');
            $payload = $input === false ? '' : $input;
        }

        $method = strtoupper($server['REQUEST_METHOD'] ?? 'POST');

        $rawUri = $server['REQUEST_URI'] ?? ($server['HTTP_X_ORIGINAL_URI'] ?? '/');
        $requestUri = $this->signatureService->extractUri($rawUri);

        $timestamp = (string) ($server['HTTP_X_TIMESTAMP'] ?? '');
        $clientId = (string) ($server['HTTP_X_CLIENT_ID'] ?? '');
        $signature = (string) ($server['HTTP_X_SIGNATURE'] ?? '');

        return [
            'method' => $method,
            'requestUri' => $requestUri,
            'timestamp' => $timestamp,
            'clientId' => $clientId,
            'payload' => $payload,
            'signature' => $signature,
        ];
    }

    /**
     * Validate and process an incoming webhook HTTP request.
     *
     * @param array|null $server Optional server data; defaults to $_SERVER
     * @param string|null $payload Optional payload; defaults to php://input contents
     * @param int $toleranceSeconds Reject timestamps older than this many seconds (0 = disable)
     * @return WebhookEvent
     * @throws GatewayException
     */
    public function handleRequest(?array $server = null, ?string $payload = null, int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS): WebhookEvent
    {
        $captured = $this->captureRequest($server, $payload);

        return $this->processEvent(
            $captured['method'],
            $captured['requestUri'],
            $captured['timestamp'],
            $captured['clientId'],
            $captured['payload'],
            $captured['signature'],
            $toleranceSeconds
        );
    }

    /**
     * Validate the webhook signature using the canonical string
     *
     * @param int $toleranceSeconds Reject timestamps older than this many seconds (0 = disable)
     */
    public function validateSignature(
        string $method,
        string $requestUri,
        string $timestamp,
        string $clientId,
        string $payload,
        string $signature,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS
    ): bool {
        if ($timestamp === '' || $clientId === '' || $signature === '') {
            return false;
        }

        if (!ctype_digit((string) $timestamp) && !is_numeric($timestamp)) {
            return false;
        }

        $ts = (int) $timestamp;

        if ($toleranceSeconds > 0) {
            $now = time();
            if ($now - $ts > $toleranceSeconds) {
                return false;
            }
        }

        $expectedSignature = $this->signatureService->generate(
            $method,
            $requestUri,
            $ts,
            $clientId,
            $this->webhookSecret,
            $payload
        );

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process webhook event. Throws on invalid signature/payload.
     *
     * @param int $toleranceSeconds Reject timestamps older than this many seconds (0 = disable)
     * @return WebhookEvent
     * @throws GatewayException
     */
    public function processEvent(
        string $method,
        string $requestUri,
        string $timestamp,
        string $clientId,
        string $payload,
        string $signature,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS
    ): WebhookEvent {
        if (!$this->validateSignature($method, $requestUri, $timestamp, $clientId, $payload, $signature, $toleranceSeconds)) {
            throw new GatewayException('Invalid webhook signature', 401);
        }

        $event = json_decode($payload, true);

        if ($event === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new GatewayException('Invalid JSON payload', 400);
        }

        if (!is_array($event) || !isset($event['event']) || !isset($event['data'])) {
            throw new GatewayException('Invalid webhook payload', 422);
        }

        return new WebhookEvent($event);
    }
}
