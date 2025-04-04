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

```php
$webhook = new Webhook('your-webhook-secret');

// Get the raw POST data
$payload = file_get_contents('php://input');

// Get the webhook signature from headers
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

// Validate and process the webhook
if ($event = $webhook->processEvent($payload, $signature)) {
    // Process based on event type
    switch ($event['event']) {
        case 'payment.completed':
            // Handle successful payment
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
    
    // Send a success response
    http_response_code(200);
    echo json_encode(['status' => 'success']);
} else {
    // Invalid webhook
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid webhook']);
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
