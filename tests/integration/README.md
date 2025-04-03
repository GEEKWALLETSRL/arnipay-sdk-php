# Integration Tests

This directory contains integration tests for the Arnipay that require a running server to test against.

## Requirements

- A running server instance at the URL specified in the `.env` file
- Valid API credentials configured in the `.env` file

## Setup

1. Copy `.env.example` to `.env`
2. Fill in the required environment variables:
   - `CLIENT_ID`: Your API client ID
   - `PRIVATE_KEY`: Your API private key
   - `API_BASE_URL`: URL of the API server (e.g. http://localhost/api/v1)
   - `WEBHOOK_SECRET`: Secret used for webhook validation

## Running Integration Tests

To run integration tests, use:

```bash
vendor/bin/phpunit tests/integration
```

## Important Notes

- These tests make actual API calls and will create, read, and potentially update real resources
- Do not run against a production environment unless absolutely necessary
- Tests are designed to be run in sequence, as some tests depend on resources created by earlier tests 
