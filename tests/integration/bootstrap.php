<?php

require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Required environment variables for integration tests
    $dotenv->required(['CLIENT_ID', 'PRIVATE_KEY', 'API_BASE_URL', 'WEBHOOK_SECRET']);
}
