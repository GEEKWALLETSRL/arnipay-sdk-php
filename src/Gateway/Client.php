<?php

namespace Arnipay\Gateway;

use Arnipay\Exception\GatewayException;
use InvalidArgumentException;

class Client
{
    public const PRODUCTION_BASE_URL = 'https://arnipay.com.py/api/v1';
    public const SANDBOX_BASE_URL = 'https://sandbox.arnipay.com.py/api/v1';

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $privateKey;

    /**
     * @var string
     */
    protected $baseUrl = self::PRODUCTION_BASE_URL;

    /**
     * @var bool Whether to verify the SSL certificate
     */
    protected $verifySsl = true;

    /**
     * @var SignatureService
     */
    protected $signatureService;

    /**
     * Client constructor.
     *
     * @param string $clientId Your Commerce client ID
     * @param string $privateKey Your Commerce private key
     */
    public function __construct(string $clientId, string $privateKey)
    {
        $this->clientId = $clientId;
        $this->privateKey = $privateKey;
        $this->signatureService = new SignatureService();
    }

    /**
     * @param string $baseUrl API base URL. Must use https:// if verifySsl is true.
     * @param bool $verifySsl Whether to verify the server's SSL certificate.
     * @return self
     * @throws InvalidArgumentException If baseUrl is not HTTPS when verifySsl is true.
     */
    public function setBaseUrl(string $baseUrl, bool $verifySsl = true): self
    {
        if ($verifySsl && strpos($baseUrl, 'https://') !== 0) {
            throw new InvalidArgumentException('Base URL must use HTTPS when SSL verification is enabled.');
        }

        $this->baseUrl = $baseUrl;
        $this->verifySsl = $verifySsl;

        return $this;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Execute a request to the API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array Response data
     * @throws GatewayException
     */
    public function request(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;
        $curl = curl_init();

        $timestamp = time();
        $requestUri = $this->signatureService->extractUri($url);

        $hasBodyMethod = in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true);
        $rawBody = '';
        if ($hasBodyMethod && !empty($data)) {
            $rawBody = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $signature = $this->signatureService->generate(
            $method,
            $requestUri,
            (int) $timestamp,
            $this->clientId,
            $this->privateKey,
            $rawBody
        );

        $headers = [
            'Content-Type: application/json',
            'X-Client-ID: ' . $this->clientId,
            'X-Timestamp: ' . $timestamp,
            'X-Signature: ' . $signature,
        ];

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        if ($this->verifySsl) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        }

        if ($rawBody !== '') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $rawBody);
        }

        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        $curlErrno = curl_errno($curl);

        curl_close($curl);

        if ($curlErrno) {
            throw new GatewayException($curlError, 0);
        }

        $responseData = json_decode($response, true);

        if ($statusCode >= 400) {
            $message = $responseData['message'] ?? 'API request failed';
            $errors = $responseData['errors'] ?? null;

            throw new GatewayException($message, $statusCode, $errors);
        }

        return $responseData;
    }
}
