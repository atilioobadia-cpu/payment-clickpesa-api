<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/clickpesa.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('payment_form.php');
}

require_csrf_token();

$user = current_user();
$customerName = request_value($_POST, 'customer_name');
$phone = normalize_phone(request_value($_POST, 'phone'));
$network = request_value($_POST, 'network');
$amount = normalize_amount($_POST['amount'] ?? null);
$description = request_value($_POST, 'description');
$apiMode = clickpesa_api_mode();
$clickpesaPhone = clickpesa_phone_number($phone);
$errors = [];

if ($customerName === '') {
    $errors[] = 'Customer name is required.';
}

if (!isset(network_options()[$network])) {
    $errors[] = 'Choose a valid mobile money network.';
}

if (!is_valid_phone($phone)) {
    $errors[] = 'Enter a valid phone number in local or international format.';
}

if ($amount === null) {
    $errors[] = 'Enter a valid amount greater than zero.';
} elseif (!amount_is_within_limits($amount)) {
    $errors[] = 'Amount must be between 1 TZS and 3,000,000 TZS.';
}

if ($apiMode === 'ClickPesa Test' && $clickpesaPhone === null) {
    $errors[] = 'For ClickPesa Test, enter a Tanzania mobile number like 0712345678, +255712345678, or 255712345678.';
}

remember_old_input([
    'customer_name' => $customerName,
    'phone' => $phone,
    'network' => $network,
    'amount' => $_POST['amount'] ?? '',
    'description' => $description,
]);

if ($errors !== []) {
    flash('error', implode(' ', $errors));
    redirect('payment_form.php');
}

$pdo = db();
$currency = (string) app_config('app.currency', 'TZS');
$orderId = build_unique_order_id($pdo);

try {
    $insert = $pdo->prepare(
        'INSERT INTO payments (
            order_id, customer_name, phone, network, amount, currency, description, status,
            gateway_reference, gateway_response, callback_received_at, created_by, created_at, updated_at
         ) VALUES (
            :order_id, :customer_name, :phone, :network, :amount, :currency, :description, :status,
            :gateway_reference, :gateway_response, NULL, :created_by, NOW(), NOW()
         )'
    );
    $insert->execute([
        ':order_id' => $orderId,
        ':customer_name' => $customerName,
        ':phone' => $phone,
        ':network' => $network,
        ':amount' => $amount,
        ':currency' => $currency,
        ':description' => $description,
        ':status' => 'PENDING',
        ':gateway_reference' => null,
        ':gateway_response' => null,
        ':created_by' => $user['id'],
    ]);

    $paymentId = (int) $pdo->lastInsertId();

    log_payment_action($paymentId, 'PAYMENT_CREATED', 'Payment record created in pending state.', [
        'order_id' => $orderId,
        'customer_name' => $customerName,
        'amount' => $amount,
    ]);
    unset($_SESSION['old_input']);
} catch (Throwable) {
    flash('error', 'The payment could not be created. Please try again.');
    redirect('payment_form.php');
}

if ($apiMode === 'Simulation') {
    $gatewayReference = build_unique_gateway_reference($pdo);
    $gatewayQueueState = [
        'provider' => 'SIMULATION',
        'mode' => 'Simulation',
        'message' => 'Payment accepted by sandbox gateway and queued for processing.',
        'queued_at' => date(DATE_ATOM),
        'gateway_reference' => $gatewayReference,
    ];
    $gatewayQueueResponse = json_encode($gatewayQueueState, JSON_UNESCAPED_SLASHES);
    $update = $pdo->prepare(
        'UPDATE payments SET status = :status, gateway_reference = :gateway_reference, gateway_response = :gateway_response, updated_at = NOW() WHERE id = :payment_id'
    );
    $update->execute([
        ':status' => 'PROCESSING',
        ':gateway_reference' => $gatewayReference,
        ':gateway_response' => $gatewayQueueResponse,
        ':payment_id' => $paymentId,
    ]);

    log_payment_action($paymentId, 'GATEWAY_REQUEST_SENT', 'Payment request was sent to the simulated gateway.', [
        'gateway_reference' => $gatewayReference,
        'mode' => app_config('sandbox.mode', 'Simulation'),
    ]);
    log_payment_action($paymentId, 'STATUS_UPDATED', 'Payment status moved to PROCESSING.', ['source' => 'pay.php']);

    flash('success', 'Payment request created and sent to the simulator gateway.');
    redirect('payment_status.php?order_id=' . urlencode($orderId));
}

$gatewayState = [
    'provider' => 'CLICKPESA_TEST',
    'mode' => 'ClickPesa Test',
    'clickpesa' => [
        'clientId' => app_config('clickpesa.client_id'),
        'requestedNetwork' => $network,
        'phoneNumber' => $clickpesaPhone,
        'currency' => $currency,
        'amount' => $amount,
        'orderReference' => $orderId,
    ],
];

$previewResponse = clickpesa_preview_ussd_push($amount, $currency, $orderId, (string) $clickpesaPhone);
$gatewayState['clickpesa']['preview'] = [
    'statusCode' => $previewResponse['status_code'] ?? 0,
    'response' => $previewResponse['decoded'] ?? ($previewResponse['body'] ?? ''),
];
$previewMessage = clickpesa_error_message($previewResponse, 'ClickPesa preview request failed.');

if (!($previewResponse['success'] ?? false) || !is_array($previewResponse['decoded'])) {
    $statement = $pdo->prepare(
        'UPDATE payments SET status = :status, gateway_response = :gateway_response, updated_at = NOW() WHERE id = :payment_id'
    );
    $statement->execute([
        ':status' => 'FAILED',
        ':gateway_response' => json_encode($gatewayState, JSON_UNESCAPED_SLASHES),
        ':payment_id' => $paymentId,
    ]);

    log_payment_action($paymentId, 'CLICKPESA_PREVIEW_FAILED', 'ClickPesa preview validation failed.', [
        'message' => $previewMessage,
        'status_code' => $previewResponse['status_code'] ?? 0,
    ]);
    log_payment_action($paymentId, 'STATUS_UPDATED', 'Payment status moved to FAILED after ClickPesa preview rejected the request.', [
        'source' => 'clickpesa_preview',
    ]);

    flash('error', $previewMessage);
    redirect('payment_status.php?order_id=' . urlencode($orderId));
}

log_payment_action($paymentId, 'CLICKPESA_PREVIEW_OK', 'ClickPesa preview validated the payment request.', $previewResponse['decoded']);

if (!clickpesa_selected_network_available($network, $previewResponse['decoded'])) {
    $availableMethods = clickpesa_available_method_names($previewResponse['decoded']);
    $message = $availableMethods === []
        ? 'ClickPesa preview did not return an available payment channel for the selected number.'
        : 'The selected network is not available for this number. Available methods: ' . implode(', ', $availableMethods) . '.';

    $statement = $pdo->prepare(
        'UPDATE payments SET status = :status, gateway_response = :gateway_response, updated_at = NOW() WHERE id = :payment_id'
    );
    $statement->execute([
        ':status' => 'FAILED',
        ':gateway_response' => json_encode($gatewayState, JSON_UNESCAPED_SLASHES),
        ':payment_id' => $paymentId,
    ]);

    log_payment_action($paymentId, 'CLICKPESA_NETWORK_UNAVAILABLE', $message, $previewResponse['decoded']);
    log_payment_action($paymentId, 'STATUS_UPDATED', 'Payment status moved to FAILED because the chosen network was unavailable in ClickPesa preview.', [
        'source' => 'clickpesa_preview',
    ]);

    flash('error', $message);
    redirect('payment_status.php?order_id=' . urlencode($orderId));
}

$initiateResponse = clickpesa_initiate_ussd_push($amount, $currency, $orderId, (string) $clickpesaPhone);
$gatewayState['clickpesa']['initiate'] = [
    'statusCode' => $initiateResponse['status_code'] ?? 0,
    'response' => $initiateResponse['decoded'] ?? ($initiateResponse['body'] ?? ''),
];
$initiateMessage = clickpesa_error_message($initiateResponse, 'ClickPesa initiate request failed.');

if (!($initiateResponse['success'] ?? false) || !is_array($initiateResponse['decoded'])) {
    $statement = $pdo->prepare(
        'UPDATE payments SET status = :status, gateway_response = :gateway_response, updated_at = NOW() WHERE id = :payment_id'
    );
    $statement->execute([
        ':status' => 'FAILED',
        ':gateway_response' => json_encode($gatewayState, JSON_UNESCAPED_SLASHES),
        ':payment_id' => $paymentId,
    ]);

    log_payment_action($paymentId, 'CLICKPESA_INITIATE_FAILED', 'ClickPesa initiate request failed.', [
        'message' => $initiateMessage,
        'status_code' => $initiateResponse['status_code'] ?? 0,
    ]);
    log_payment_action($paymentId, 'STATUS_UPDATED', 'Payment status moved to FAILED after ClickPesa initiation failed.', [
        'source' => 'clickpesa_initiate',
    ]);

    flash('error', $initiateMessage);
    redirect('payment_status.php?order_id=' . urlencode($orderId));
}

$remotePayment = $initiateResponse['decoded'];
$localStatus = clickpesa_map_status((string) ($remotePayment['status'] ?? 'PROCESSING'));
$gatewayReference = (string) ($remotePayment['id'] ?? '');
$gatewayResponse = json_encode($gatewayState, JSON_UNESCAPED_SLASHES);
$statement = $pdo->prepare(
    'UPDATE payments
     SET status = :status,
         gateway_reference = :gateway_reference,
         gateway_response = :gateway_response,
         callback_received_at = CASE
             WHEN :is_final = 1 THEN NOW()
             ELSE callback_received_at
         END,
         updated_at = NOW()
     WHERE id = :payment_id'
);
$statement->execute([
    ':status' => $localStatus,
    ':gateway_reference' => $gatewayReference,
    ':gateway_response' => $gatewayResponse,
    ':is_final' => is_final_status($localStatus) ? 1 : 0,
    ':payment_id' => $paymentId,
]);

log_payment_action($paymentId, 'CLICKPESA_INITIATED', 'ClickPesa USSD push request was initiated successfully.', $remotePayment);
log_payment_action($paymentId, 'STATUS_UPDATED', 'Payment status moved to ' . $localStatus . ' after ClickPesa initiation.', [
    'source' => 'clickpesa_initiate',
]);

flash('success', 'ClickPesa USSD push initiated. Ask the customer to complete the payment on the phone.');
redirect('payment_status.php?order_id=' . urlencode($orderId));
