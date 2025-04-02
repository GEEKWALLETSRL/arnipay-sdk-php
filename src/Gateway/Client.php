<?php

namespace GwSdk\Gateway;

use GwSdk\Exception\GatewayException;
use InvalidArgumentException;

class Client
{
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
    protected $baseUrl;

    /**
     * @var bool Whether to verify the SSL certificate
     */
    protected $verifySsl;

    /**
     * Client constructor.
     *
     * @param string $clientId Your Commerce client ID
     * @param string $privateKey Your Commerce private key
     * @param string $baseUrl API base URL. Must use https:// if verifySsl is true.
     * @param bool $verifySsl Optional. Whether to verify the server's SSL certificate. Defaults to true. Set to false only for trusted local/testing environments.
     * @throws InvalidArgumentException If baseUrl is not HTTPS when verifySsl is true.
     */
    public function __construct(string $clientId, string $privateKey, string $baseUrl = 'https://arnipay.com.py/api/v1', bool $verifySsl = true)
    {
        $this->clientId = $clientId;
        $this->privateKey = $privateKey;
        $this->baseUrl = $baseUrl;
        $this->verifySsl = $verifySsl;

        // Ensure HTTPS is used if verification is enabled
        if ($this->verifySsl && strpos($this->baseUrl, 'https://') !== 0) {
            throw new InvalidArgumentException('Base URL must use HTTPS when SSL verification is enabled.');
        }
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

        // Generate timestamp for the request
        $timestamp = time();

        // Generate signature for authentication
        //remove domain from url
        $requestUri = parse_url($url, PHP_URL_PATH);
        $signatureData = strtoupper($method) . $requestUri . $timestamp . $this->clientId;
        $signature = hash_hmac('sha256', $signatureData, $this->privateKey);

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

        // Set SSL verification options based on the setting
        if ($this->verifySsl) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            // Disable SSL verification (use with caution!)
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        }

        if (!empty($data) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
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
