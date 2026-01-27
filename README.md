# Payment Gateway PHP SDK

This SDK provides a simple and easy-to-use interface for integrating with our payment processing system.

You can find the full API documentation [here](https://github.com/GEEKWALLETSRL/arnipay-api).

## Installation

Install via Composer:

```bash
composer require geekwalletsrl/arnipay-sdk
```

## Quick Start (Recommended)

The easiest way to use the SDK is via the fluent interface.

```php
require 'vendor/autoload.php';

use Arnipay\Arnipay;

// 1. Setup
// The third argument 'true' enables Sandbox mode automatically.
$arni = new Arnipay('CLIENT_ID', 'PRIVATE_KEY', true);

// 2. Create Payment Link
try {
    $url = $arni->payment()
        ->title('Pizza Order')
        ->amount(50000)
        ->reference('ORDER-123')
        ->description('Two large pizzas')
        ->redirect('https://site.com/thanks', 'https://site.com/oops')
        ->allow(['qr', 'card']) // Optional: restrict payment methods
        ->createUrl();
        
    echo "Pay here: " . $url;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// 3. Webhook Handling (webhook.php)
try {
    $arni->webhook('WEBHOOK_SECRET')->handle(function($event) {
        if ($event->isPaid()) {
            // $event->reference corresponds to the reference you set above
            Order::complete($event->reference);
        }
    });
    
    http_response_code(200);
} catch (Exception $e) {
    http_response_code(400);
}
```

## Advanced Usage (Service Pattern)

For more control, you can use the underlying services directly.

### Initialization

```php
use Arnipay\Gateway\Client;
use Arnipay\Gateway\PaymentLink;
use Arnipay\Gateway\Webhook;

// Initialize the client
$client = new Client(
    'your-client-id',
    'your-private-key'
);

// For sandbox:
// $client->setBaseUrl('https://sandbox-api.arnipay.com', false);
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

### Handling Webhooks (Manual)

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
