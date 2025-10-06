# Payment Gateway PHP SDK

This SDK provides a simple and easy-to-use interface for integrating with our payment processing system.

You can find the full API documentation [here](https://github.com/GEEKWALLETSRL/arnipay-api).

## Installation

Install via Composer:

```bash
composer require geekwalletsrl/arnipay-sdk
```

## Usage

### Initialization

```php
require 'vendor/autoload.php';

use Arnipay\Gateway\Client;
use Arnipay\Gateway\PaymentLink;
use Arnipay\Gateway\Webhook;

// Initialize the client
$client = new Client(
    'your-client-id',
    'your-private-key',
);
```

### Creating a Payment Link

```php
$paymentLink = new PaymentLink($client);

try {
    $link = $paymentLink->create(
        150000, // price
        'Premium Subscription', // title
        '1 year access to all premium content', // description
        [
            'payment_methods' => ['qr', 'tigo'],
            'reference' => 'SUB-' . date('Y'),
            'approved_redirection_url' => 'https://example.com/success',
            'failed_redirection_url' => 'https://example.com/failed'
        ]
    );
    
    echo "Payment link created with ID: " . $link['id'] . "\n";
    echo "Payment URL: " . $link['url'] . "\n";
} catch (Arnipay\Exception\GatewayException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if ($errors = $e->getErrors()) {
        print_r($errors);
    }
}
```

### Getting a Specific Payment Link

```php
$paymentLink = new PaymentLink($client);

try {
    $link = $paymentLink->get('payment-link-uuid');
    
    echo "Payment link details:\n";
    echo "Title: " . $link['title'] . "\n";
    echo "Price: " . $link['price'] . "\n";
    echo "Is Paid: " . ($link['is_paid'] ? 'Yes' : 'No') . "\n";
} catch (Arnipay\Exception\GatewayException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

### Handling Webhooks

The webhook payload is signed with the values below. Make sure your HTTP server forwards them to PHP:

- `X-Timestamp` (`$_SERVER['HTTP_X_TIMESTAMP']`)
- `X-Client-ID` (`$_SERVER['HTTP_X_CLIENT_ID']`)
- `X-Signature` (`$_SERVER['HTTP_X_SIGNATURE']`)

The SDK exposes helpers so you do not have to wire superglobals manually:

```php
$webhook = new Webhook('your-webhook-secret');

try {
    // Automatically captures method, URI, headers and body, then validates the signature.
    $event = $webhook->handleRequest();

    switch ($event['event']) {
        case 'payment.completed':
            $linkId = $event['data']['link_id'];
            $paymentId = $event['data']['payment_id'];
            $amount = $event['data']['amount'];
            // Update your database or take appropriate action
            break;

        case 'payment.failed':
            // Handle failed payment
            break;

        case 'payment.pending':
            // Handle pending payment
            break;
    }

    http_response_code(200);
    echo json_encode(['status' => 'success']);
} catch (Arnipay\Exception\GatewayException $e) {
    http_response_code($e->getStatusCode() ?: 400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
```

For custom frameworks or testing you can pass your own values:

```php
$captured = $webhook->captureRequest($serverArray, $rawPayload);

if ($webhook->validateSignature(
    $captured['method'],
    $captured['requestUri'],
    $captured['timestamp'],
    $captured['clientId'],
    $captured['payload'],
    $captured['signature']
)) {
    $event = $webhook->processEvent(
        $captured['method'],
        $captured['requestUri'],
        $captured['timestamp'],
        $captured['clientId'],
        $captured['payload'],
        $captured['signature']
    );
}
```

## Error Handling

The SDK throws `Arnipay\Exception\GatewayException` when an error occurs. This exception provides:

- Error message
- HTTP status code
- Validation errors (if available)

```php
try {
    // SDK operation
} catch (Arnipay\Exception\GatewayException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Status Code: " . $e->getStatusCode() . "\n";
    
    if ($errors = $e->getErrors()) {
        echo "Validation Errors:\n";
        print_r($errors);
    }
}
```

## Testing

The project defines separate PHPUnit test suites for unit and integration tests.

- Unit tests (no external services required):

```bash
vendor/bin/phpunit --testsuite Unit
```

- Integration tests (require environment variables; see `tests/integration/README.md`):

```bash
vendor/bin/phpunit --testsuite Integration
```

Note: Using `--testsuite` is the recommended way to exclude integration tests. If you previously used `--exclude-group=integration` and still saw integration tests run, switch to the `--testsuite` commands above.
