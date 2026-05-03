<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response([
        'success' => false,
        'message' => 'Method not allowed.',
    ], 405);
}

$callbackKey = $_SERVER['HTTP_X_SANDBOX_KEY'] ?? '';

if (!hash_equals((string) app_config('sandbox.callback_key'), $callbackKey)) {
    json_response([
        'success' => false,
        'message' => 'Unauthorized callback request.',
    ], 401);
}

$rawJson = file_get_contents('php://input') ?: '';
$payload = json_decode($rawJson, true);

if ($rawJson === '' || !is_array($payload)) {
    json_response([
        'success' => false,
        'message' => 'Invalid JSON payload.',
    ], 400);
}

$response = process_callback_payload($payload, $rawJson);
$statusCode = (int) ($response['code'] ?? 200);
unset($response['code']);

json_response($response, $statusCode);
