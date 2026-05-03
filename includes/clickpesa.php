<?php
declare(strict_types=1);

function clickpesa_api_mode(): string
{
    return get_setting('api_mode', 'ClickPesa Test') ?: 'ClickPesa Test';
}

function clickpesa_enabled(): bool
{
    return (bool) app_config('clickpesa.enabled', false);
}

function clickpesa_phone_number(string $phone): ?string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';

    if ($digits === '') {
        return null;
    }

    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }

    if (str_starts_with($digits, '255') && strlen($digits) === 12) {
        return $digits;
    }

    if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
        return '255' . substr($digits, 1);
    }

    if (strlen($digits) === 9 && preg_match('/^[67]/', $digits) === 1) {
        return '255' . $digits;
    }

    return null;
}

function clickpesa_network_provider_map(): array
{
    return [
        'M-Pesa' => ['M-PESA'],
        'Airtel Money' => ['AIRTEL-MONEY', 'AIRTEL MONEY'],
        'Tigo Pesa / Mixx by Yass' => ['TIGO-PESA', 'TIGO PESA', 'MIXX BY YASS'],
        'HaloPesa' => ['HALOPESA', 'HALO PESA'],
    ];
}

function clickpesa_network_matches(string $selectedNetwork, string $providerName): bool
{
    $providerName = strtoupper($providerName);
    $expectedProviders = clickpesa_network_provider_map()[$selectedNetwork] ?? [];

    foreach ($expectedProviders as $expectedProvider) {
        if (str_contains($providerName, strtoupper($expectedProvider))) {
            return true;
        }
    }

    return false;
}

function clickpesa_available_method_names(array $previewResponse): array
{
    $methods = $previewResponse['activeMethods'] ?? [];

    if (!is_array($methods)) {
        return [];
    }

    $names = [];

    foreach ($methods as $method) {
        if (!is_array($method)) {
            continue;
        }

        $name = trim((string) ($method['name'] ?? ''));

        if ($name !== '') {
            $names[] = $name;
        }
    }

    return $names;
}

function clickpesa_selected_network_available(string $selectedNetwork, array $previewResponse): bool
{
    $methodNames = clickpesa_available_method_names($previewResponse);

    if ($methodNames === []) {
        return false;
    }

    foreach ($methodNames as $methodName) {
        if (clickpesa_network_matches($selectedNetwork, $methodName)) {
            return true;
        }
    }

    return false;
}

function clickpesa_is_associative_array(array $value): bool
{
    return $value !== [] && array_keys($value) !== range(0, count($value) - 1);
}

function clickpesa_canonicalize(mixed $value): mixed
{
    if (!is_array($value)) {
        return $value;
    }

    if (!clickpesa_is_associative_array($value)) {
        return array_map('clickpesa_canonicalize', $value);
    }

    ksort($value);

    foreach ($value as $key => $item) {
        $value[$key] = clickpesa_canonicalize($item);
    }

    return $value;
}

function clickpesa_create_checksum(array $payload): ?string
{
    $checksumKey = trim((string) app_config('clickpesa.checksum_key', ''));

    if ($checksumKey === '') {
        return null;
    }

    $canonicalPayload = clickpesa_canonicalize($payload);
    $payloadString = json_encode($canonicalPayload, JSON_UNESCAPED_SLASHES);

    if ($payloadString === false) {
        return null;
    }

    return hash_hmac('sha256', $payloadString, $checksumKey);
}

function clickpesa_http_request(string $method, string $url, array $headers = [], ?array $payload = null, array $query = []): array
{
    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'status_code' => 0,
            'body' => '',
            'decoded' => null,
            'error' => 'cURL is required for ClickPesa integration.',
        ];
    }

    if ($query !== []) {
        $separator = str_contains($url, '?') ? '&' : '?';
        $url .= $separator . http_build_query($query);
    }

    $curl = curl_init($url);
    $curlHeaders = [];

    foreach ($headers as $name => $value) {
        $curlHeaders[] = $name . ': ' . $value;
    }

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $curlHeaders,
        CURLOPT_TIMEOUT => 30,
    ];

    if ($payload !== null) {
        $encodedPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $options[CURLOPT_POSTFIELDS] = $encodedPayload === false ? '{}' : $encodedPayload;
    }

    curl_setopt_array($curl, $options);
    $body = curl_exec($curl);
    $error = curl_error($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $bodyString = is_string($body) ? $body : '';
    $decoded = $bodyString !== '' ? json_decode($bodyString, true) : null;

    return [
        'success' => $error === '' && $statusCode >= 200 && $statusCode < 300,
        'status_code' => $statusCode,
        'body' => $bodyString,
        'decoded' => is_array($decoded) ? $decoded : null,
        'error' => $error,
    ];
}

function clickpesa_generate_token(bool $forceRefresh = false): array
{
    $cachedToken = $_SESSION['clickpesa_token'] ?? null;
    $cachedAt = (int) ($_SESSION['clickpesa_token_created_at'] ?? 0);

    if (!$forceRefresh && is_string($cachedToken) && $cachedToken !== '' && $cachedAt > 0 && (time() - $cachedAt) < 3300) {
        return [
            'success' => true,
            'token' => $cachedToken,
            'status_code' => 200,
            'body' => '',
            'decoded' => ['token' => $cachedToken],
        ];
    }

    $response = clickpesa_http_request('POST', (string) app_config('clickpesa.token_url'), [
        'client-id' => (string) app_config('clickpesa.client_id'),
        'api-key' => (string) app_config('clickpesa.api_key'),
    ]);

    if ($response['success'] && is_array($response['decoded']) && !empty($response['decoded']['token'])) {
        $_SESSION['clickpesa_token'] = (string) $response['decoded']['token'];
        $_SESSION['clickpesa_token_created_at'] = time();
        $response['token'] = (string) $response['decoded']['token'];
    }

    return $response;
}

function clickpesa_authorized_request(string $method, string $url, ?array $payload = null, array $query = [], bool $retried = false): array
{
    $tokenResponse = clickpesa_generate_token($retried);

    if (empty($tokenResponse['success']) || empty($tokenResponse['token'])) {
        return [
            'success' => false,
            'status_code' => (int) ($tokenResponse['status_code'] ?? 0),
            'body' => (string) ($tokenResponse['body'] ?? ''),
            'decoded' => $tokenResponse['decoded'] ?? null,
            'error' => clickpesa_error_message($tokenResponse, 'Unable to generate ClickPesa access token.'),
        ];
    }

    $response = clickpesa_http_request($method, $url, [
        'Authorization' => (string) $tokenResponse['token'],
        'Content-Type' => 'application/json',
    ], $payload, $query);

    if ((int) $response['status_code'] === 401 && !$retried) {
        unset($_SESSION['clickpesa_token'], $_SESSION['clickpesa_token_created_at']);

        return clickpesa_authorized_request($method, $url, $payload, $query, true);
    }

    return $response;
}

function clickpesa_preview_ussd_push(string $amount, string $currency, string $orderReference, string $phoneNumber): array
{
    $payload = [
        'amount' => $amount,
        'currency' => $currency,
        'orderReference' => $orderReference,
        'phoneNumber' => $phoneNumber,
        'fetchSenderDetails' => (bool) app_config('clickpesa.fetch_sender_details', false),
    ];

    $checksum = clickpesa_create_checksum($payload);

    if ($checksum !== null) {
        $payload['checksum'] = $checksum;
    }

    return clickpesa_authorized_request('POST', (string) app_config('clickpesa.preview_url'), $payload);
}

function clickpesa_initiate_ussd_push(string $amount, string $currency, string $orderReference, string $phoneNumber): array
{
    $payload = [
        'amount' => $amount,
        'currency' => $currency,
        'orderReference' => $orderReference,
        'phoneNumber' => $phoneNumber,
    ];

    $checksum = clickpesa_create_checksum($payload);

    if ($checksum !== null) {
        $payload['checksum'] = $checksum;
    }

    return clickpesa_authorized_request('POST', (string) app_config('clickpesa.initiate_url'), $payload);
}

function clickpesa_query_payment(string $orderReference): array
{
    $baseUrl = rtrim((string) app_config('clickpesa.query_url'), '/');

    return clickpesa_authorized_request('GET', $baseUrl . '/' . rawurlencode($orderReference));
}

function clickpesa_map_status(string $remoteStatus): string
{
    return match (strtoupper($remoteStatus)) {
        'SUCCESS', 'SETTLED' => 'PAID',
        'FAILED' => 'FAILED',
        'PROCESSING' => 'PROCESSING',
        'PENDING' => 'PENDING',
        default => 'PROCESSING',
    };
}

function payment_gateway_payload(array $payment): array
{
    $payload = json_decode((string) ($payment['gateway_response'] ?? ''), true);

    return is_array($payload) ? $payload : [];
}

function payment_is_clickpesa(array $payment): bool
{
    $gatewayPayload = payment_gateway_payload($payment);

    if (($gatewayPayload['provider'] ?? null) === 'CLICKPESA_TEST') {
        return true;
    }

    return isset($gatewayPayload['clickpesa']);
}

function clickpesa_error_message(array $response, string $fallbackMessage): string
{
    if (!empty($response['decoded']['message']) && is_string($response['decoded']['message'])) {
        return $response['decoded']['message'];
    }

    if (!empty($response['error']) && is_string($response['error'])) {
        return $response['error'];
    }

    if (!empty($response['body']) && is_string($response['body'])) {
        return $response['body'];
    }

    if (!empty($response['status_code'])) {
        return $fallbackMessage . ' HTTP ' . (int) $response['status_code'] . '.';
    }

    return $fallbackMessage;
}

function sync_clickpesa_payment(array $payment): array
{
    $response = clickpesa_query_payment((string) $payment['order_id']);

    if (!$response['success']) {
        return [
            'success' => false,
            'updated' => false,
            'message' => clickpesa_error_message($response, 'ClickPesa status sync failed.'),
        ];
    }

    $records = $response['decoded'] ?? null;

    if (!is_array($records) || $records === [] || !isset($records[0]) || !is_array($records[0])) {
        return [
            'success' => false,
            'updated' => false,
            'message' => 'No ClickPesa payment record was found for this order yet.',
        ];
    }

    $remotePayment = $records[0];
    $localStatus = clickpesa_map_status((string) ($remotePayment['status'] ?? 'PROCESSING'));
    $currentGatewayState = payment_gateway_payload($payment);
    $currentGatewayState['provider'] = 'CLICKPESA_TEST';
    $currentGatewayState['mode'] = 'ClickPesa Test';
    $currentGatewayState['clickpesa']['latestStatus'] = $remotePayment;
    $currentGatewayState['clickpesa']['lastSyncedAt'] = date(DATE_ATOM);
    $gatewayResponse = json_encode($currentGatewayState, JSON_UNESCAPED_SLASHES);
    $gatewayReference = (string) ($remotePayment['paymentReference'] ?? $payment['gateway_reference']);
    $statusChanged = strtoupper((string) $payment['status']) !== $localStatus;
    $gatewayChanged = (string) $payment['gateway_reference'] !== $gatewayReference
        || (string) $payment['gateway_response'] !== (string) $gatewayResponse;

    if (!$statusChanged && !$gatewayChanged) {
        return [
            'success' => true,
            'updated' => false,
            'message' => 'Payment status is already up to date.',
            'payment_status' => $localStatus,
        ];
    }

    $statement = db()->prepare(
        'UPDATE payments
         SET status = :status,
             gateway_reference = :gateway_reference,
             gateway_response = :gateway_response,
             callback_received_at = CASE
                 WHEN :is_final = 1 AND callback_received_at IS NULL THEN NOW()
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
        ':payment_id' => $payment['id'],
    ]);

    log_payment_action((int) $payment['id'], 'CLICKPESA_STATUS_SYNC', 'Payment status synchronized from ClickPesa.', $remotePayment);

    if ($statusChanged) {
        log_payment_action((int) $payment['id'], 'STATUS_UPDATED', 'Payment status moved to ' . $localStatus . ' from ClickPesa query.', [
            'source' => 'clickpesa_query',
        ]);
    }

    return [
        'success' => true,
        'updated' => true,
        'message' => 'Payment status synchronized successfully.',
        'payment_status' => $localStatus,
    ];
}
