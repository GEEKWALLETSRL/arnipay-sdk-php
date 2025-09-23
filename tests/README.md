# Tests for Arnipay

This directory contains tests for the Arnipay PHP library.

## Test Structure

The tests are organized into two categories:

1. **Unit/Mock Tests** - Located in the main `tests/` directory
   - These tests use mock objects and don't require an actual server
   - They can be run without any external dependencies
   - They validate basic functionality and interfaces

2. **Integration Tests** - Located in the `tests/integration/` directory
   - These tests require a running server to test against
   - They validate actual API interactions
   - They require environment configuration (see `tests/integration/README.md`)

## Running Tests

### Unit/Mock Tests Only

```bash
vendor/bin/phpunit --testsuite Unit
```

### All Tests (including integration)

```bash
vendor/bin/phpunit
```

### Integration Tests Only

```bash
vendor/bin/phpunit --testsuite Integration
```

## Environment Configuration

For unit/mock tests, no special configuration is required.

For integration tests, see the README in the `integration` directory for setup instructions. 
