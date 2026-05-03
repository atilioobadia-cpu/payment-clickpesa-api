<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Nairobi');

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$appBasePath = rtrim(dirname($scriptName), '/.');
$appBasePath = $appBasePath === '/' ? '' : $appBasePath;
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $scheme . '://' . $host . ($appBasePath !== '' ? '/' . ltrim($appBasePath, '/') : '');

$GLOBALS['app_config'] = [
    'app' => [
        'name' => 'Payment Sandbox Demo',
        'currency' => 'TZS',
        'base_path' => $appBasePath,
        'base_url' => rtrim($baseUrl, '/'),
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'payment_sandbox_demo',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'sandbox' => [
        'api_key' => 'sandbox_demo_public_key',
        'callback_key' => 'sandbox_demo_callback_secret',
        'mode' => 'Simulation',
        'enabled' => true,
    ],
    'clickpesa' => [
        'enabled' => true,
        'client_id' => 'IDMSolyRDwKrydnzvlcjE8BdVs2ktfG3',
        'api_key' => 'SKx9UUlNntU6vigRLNqBG4qcbFLgSVOzVjl3UQzOAg',
        'checksum_key' => '',
        'fetch_sender_details' => false,
        'token_url' => 'https://api.clickpesa.com/third-parties/generate-token',
        'preview_url' => 'https://api.clickpesa.com/third-parties/payments/preview-ussd-push-request',
        'initiate_url' => 'https://api.clickpesa.com/third-parties/payments/initiate-ussd-push-request',
        'query_url' => 'https://api.clickpesa.com/third-parties/payments',
    ],
];
