<?php
declare(strict_types=1);

function app_config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['app_config'] ?? [];

    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }

        $value = $value[$segment];
    }

    return $value;
}

function route_path(string $path = ''): string
{
    $basePath = rtrim((string) app_config('app.base_path', ''), '/');
    $trimmedPath = ltrim($path, '/');

    if ($trimmedPath === '') {
        return $basePath !== '' ? $basePath : '/';
    }

    return $basePath !== '' ? $basePath . '/' . $trimmedPath : '/' . $trimmedPath;
}

function app_url(string $path = ''): string
{
    $baseUrl = rtrim((string) app_config('app.base_url', ''), '/');
    $trimmedPath = ltrim($path, '/');

    if ($trimmedPath === '') {
        return $baseUrl;
    }

    return $baseUrl . '/' . $trimmedPath;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_icon(string $name, string $class = 'icon'): string
{
    $icons = [
        'menu' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M3 5.5h14"/><path d="M3 10h14"/><path d="M3 14.5h14"/></svg>',
        'search' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="9" r="5.5"/><path d="M13.5 13.5L17 17"/></svg>',
        'bell' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 7.8a4 4 0 118 0c0 4 1.8 4.9 2 5.2H4c.2-.3 2-1.2 2-5.2"/><path d="M8.3 15.5a2 2 0 003.4 0"/></svg>',
        'help' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 7.3a2.5 2.5 0 114.4 1.6c-.6.8-1.4 1.2-1.9 1.7-.3.3-.4.6-.4 1.2"/><circle cx="10" cy="14.6" r=".7" fill="currentColor" stroke="none"/></svg>',
        'chevron-right' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 5l5 5-5 5"/></svg>',
        'chevron-down' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8l5 5 5-5"/></svg>',
        'dashboard' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="6" height="6"/><rect x="11" y="3" width="6" height="4"/><rect x="11" y="9" width="6" height="8"/><rect x="3" y="11" width="6" height="6"/></svg>',
        'plus' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M10 4v12"/><path d="M4 10h12"/></svg>',
        'list' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 5h10"/><path d="M7 10h10"/><path d="M7 15h10"/><circle cx="4" cy="5" r=".8" fill="currentColor" stroke="none"/><circle cx="4" cy="10" r=".8" fill="currentColor" stroke="none"/><circle cx="4" cy="15" r=".8" fill="currentColor" stroke="none"/></svg>',
        'payment' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="14" height="10" rx="1.5"/><path d="M3 8h14"/><path d="M7 12h2"/></svg>',
        'simulator' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="14" height="12" rx="2"/><path d="M7 8h6"/><path d="M7 12h3"/></svg>',
        'settings' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="2.6"/><path d="M10 3.5v1.7"/><path d="M10 14.8v1.7"/><path d="M15.2 10h1.3"/><path d="M3.5 10h1.3"/><path d="M13.7 6.3l1-1"/><path d="M5.3 14.7l1-1"/><path d="M13.7 13.7l1 1"/><path d="M5.3 5.3l1 1"/></svg>',
        'logout' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4H5a2 2 0 00-2 2v8a2 2 0 002 2h3"/><path d="M12 6l4 4-4 4"/><path d="M16 10H8"/></svg>',
        'refresh' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M16 10a6 6 0 10-1.3 3.8"/><path d="M16 5v5h-5"/></svg>',
        'filter' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5h14"/><path d="M5.5 10h9"/><path d="M8 15h4"/></svg>',
        'more' => '<svg viewBox="0 0 20 20" fill="currentColor"><circle cx="4.5" cy="10" r="1.4"/><circle cx="10" cy="10" r="1.4"/><circle cx="15.5" cy="10" r="1.4"/></svg>',
        'copy' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="7" width="9" height="9" rx="1.5"/><path d="M5 12H4a1 1 0 01-1-1V4a1 1 0 011-1h7a1 1 0 011 1v1"/></svg>',
        'print' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 7V3h8v4"/><rect x="4" y="8" width="12" height="6" rx="1.5"/><path d="M6 12h8v5H6z"/></svg>',
        'external' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4h5v5"/><path d="M9 11l7-7"/><path d="M16 11v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5a1 1 0 011-1h4"/></svg>',
        'moon' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 12.8A6.5 6.5 0 017.2 5.5 6.7 6.7 0 1014.5 12.8z"/></svg>',
        'sun' => '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="3.2"/><path d="M10 2.8v2"/><path d="M10 15.2v2"/><path d="M17.2 10h-2"/><path d="M4.8 10h-2"/><path d="M15.1 4.9l-1.4 1.4"/><path d="M6.3 13.7l-1.4 1.4"/><path d="M15.1 15.1l-1.4-1.4"/><path d="M6.3 6.3L4.9 4.9"/></svg>',
    ];

    $svg = $icons[$name] ?? $icons['dashboard'];

    return '<span class="' . e($class) . '" aria-hidden="true">' . $svg . '</span>';
}

function redirect(string $path): never
{
    header('Location: ' . route_path($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function consume_flashes(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    return $messages;
}

function remember_old_input(array $data): void
{
    $_SESSION['old_input'] = $data;
}

function consume_old_input(): array
{
    $oldInput = $_SESSION['old_input'] ?? [];
    unset($_SESSION['old_input']);

    return is_array($oldInput) ? $oldInput : [];
}

function request_value(array $source, string $key, string $default = ''): string
{
    $value = $source[$key] ?? $default;

    return is_scalar($value) ? trim((string) $value) : $default;
}

function network_options(): array
{
    return [
        'M-Pesa' => 'M-Pesa',
        'Airtel Money' => 'Airtel Money',
        'Tigo Pesa / Mixx by Yass' => 'Tigo Pesa / Mixx by Yass',
        'HaloPesa' => 'HaloPesa',
    ];
}

function normalize_phone(string $phone): string
{
    $phone = trim($phone);
    $phone = preg_replace('/(?!^\+)[^\d]/', '', $phone) ?? '';

    if (str_starts_with($phone, '00')) {
        $phone = '+' . substr($phone, 2);
    }

    return $phone;
}

function is_valid_phone(string $phone): bool
{
    return (bool) preg_match('/^\+?\d{9,15}$/', normalize_phone($phone));
}

function normalize_amount(mixed $amount): ?string
{
    if (!is_numeric($amount)) {
        return null;
    }

    $normalized = round((float) $amount, 2);

    if ($normalized <= 0) {
        return null;
    }

    return number_format($normalized, 2, '.', '');
}

function amount_is_within_limits(string $amount, float $minimum = 1.0, float $maximum = 3000000.0): bool
{
    $numericAmount = (float) $amount;

    return $numericAmount >= $minimum && $numericAmount <= $maximum;
}

function generate_order_id(): string
{
    return 'PSD' . date('ymdHis') . random_int(1000, 9999);
}

function generate_gateway_reference(): string
{
    return 'GATE-' . strtoupper(bin2hex(random_bytes(4)));
}

function payment_status_message(string $status): string
{
    return match (strtoupper($status)) {
        'PROCESSING' => 'Payment request sent. Please complete payment on your phone.',
        'PAID' => 'Payment successful.',
        'FAILED' => 'Payment failed.',
        'CANCELLED' => 'Payment cancelled.',
        'TIMEOUT' => 'Payment timed out.',
        default => 'Payment request is pending review.',
    };
}

function format_currency(float|string $amount, ?string $currency = null): string
{
    $currency = $currency ?: (string) app_config('app.currency', 'TZS');

    return $currency . ' ' . number_format((float) $amount, 2);
}

function format_datetime(?string $value): string
{
    if ($value === null || $value === '') {
        return 'Not available';
    }

    try {
        return (new DateTimeImmutable($value))->format('d M Y, H:i');
    } catch (Exception) {
        return $value;
    }
}

function mask_secret(string $value): string
{
    if (mb_strlen($value) <= 8) {
        return str_repeat('*', mb_strlen($value));
    }

    return substr($value, 0, 4) . str_repeat('*', max(4, mb_strlen($value) - 8)) . substr($value, -4);
}

function is_final_status(string $status): bool
{
    return in_array(strtoupper($status), ['PAID', 'FAILED', 'CANCELLED', 'TIMEOUT'], true);
}

function default_settings(): array
{
    return [
        'merchant_name' => 'Payment Sandbox Demo Merchant',
        'callback_url' => app_url('payment_callback.php'),
        'sandbox_mode_enabled' => '1',
        'api_mode' => 'ClickPesa Test',
    ];
}

function ensure_settings_exist(): void
{
    $settings = default_settings();
    $pdo = db();
    $statement = $pdo->prepare(
        'INSERT IGNORE INTO settings (setting_key, setting_value, updated_at) VALUES (:setting_key, :setting_value, NOW())'
    );

    foreach ($settings as $key => $value) {
        $statement->execute([
            ':setting_key' => $key,
            ':setting_value' => $value,
        ]);
    }
}

function get_setting(string $key, ?string $default = null): ?string
{
    ensure_settings_exist();
    $statement = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = :setting_key LIMIT 1');
    $statement->execute([':setting_key' => $key]);
    $value = $statement->fetchColumn();

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function get_settings_map(): array
{
    ensure_settings_exist();
    $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
    $settings = [];

    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    return $settings;
}

function save_settings(array $settings): void
{
    $statement = db()->prepare(
        'INSERT INTO settings (setting_key, setting_value, updated_at)
         VALUES (:setting_key, :setting_value, NOW())
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
    );

    foreach ($settings as $key => $value) {
        $statement->execute([
            ':setting_key' => $key,
            ':setting_value' => $value,
        ]);
    }
}

function get_payment_by_order_id(string $orderId): ?array
{
    $statement = db()->prepare(
        'SELECT payments.*, users.name AS created_by_name
         FROM payments
         LEFT JOIN users ON users.id = payments.created_by
         WHERE order_id = :order_id
         LIMIT 1'
    );
    $statement->execute([':order_id' => $orderId]);
    $payment = $statement->fetch();

    return $payment ?: null;
}

function get_payment_by_id(int $paymentId): ?array
{
    $statement = db()->prepare(
        'SELECT payments.*, users.name AS created_by_name
         FROM payments
         LEFT JOIN users ON users.id = payments.created_by
         WHERE payments.id = :payment_id
         LIMIT 1'
    );
    $statement->execute([':payment_id' => $paymentId]);
    $payment = $statement->fetch();

    return $payment ?: null;
}

function log_payment_action(int $paymentId, string $action, string $message, mixed $rawData = null): void
{
    $statement = db()->prepare(
        'INSERT INTO payment_logs (payment_id, action, message, raw_data, created_at)
         VALUES (:payment_id, :action, :message, :raw_data, NOW())'
    );
    $statement->execute([
        ':payment_id' => $paymentId,
        ':action' => $action,
        ':message' => $message,
        ':raw_data' => $rawData === null
            ? null
            : (is_string($rawData) ? $rawData : json_encode($rawData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)),
    ]);
}

function get_payment_logs(int $paymentId): array
{
    $statement = db()->prepare('SELECT * FROM payment_logs WHERE payment_id = :payment_id ORDER BY created_at ASC, id ASC');
    $statement->execute([':payment_id' => $paymentId]);

    return $statement->fetchAll();
}

function build_unique_order_id(PDO $pdo): string
{
    do {
        $orderId = generate_order_id();
        $statement = $pdo->prepare('SELECT COUNT(*) FROM payments WHERE order_id = :order_id');
        $statement->execute([':order_id' => $orderId]);
    } while ((int) $statement->fetchColumn() > 0);

    return $orderId;
}

function build_unique_gateway_reference(PDO $pdo): string
{
    do {
        $gatewayReference = generate_gateway_reference();
        $statement = $pdo->prepare('SELECT COUNT(*) FROM payments WHERE gateway_reference = :gateway_reference');
        $statement->execute([':gateway_reference' => $gatewayReference]);
    } while ((int) $statement->fetchColumn() > 0);

    return $gatewayReference;
}

function status_slug(string $status): string
{
    return strtolower(trim($status));
}

function dashboard_stats(int $userId): array
{
    $statement = db()->prepare(
        'SELECT
            COUNT(*) AS total_payments,
            SUM(CASE WHEN status IN ("PENDING", "PROCESSING") THEN 1 ELSE 0 END) AS pending_payments,
            SUM(CASE WHEN status = "PAID" THEN 1 ELSE 0 END) AS paid_payments,
            SUM(CASE WHEN status IN ("FAILED", "CANCELLED", "TIMEOUT") THEN 1 ELSE 0 END) AS failed_payments
         FROM payments
         WHERE created_by = :created_by'
    );
    $statement->execute([':created_by' => $userId]);
    $stats = $statement->fetch() ?: [];

    return [
        'total_payments' => (int) ($stats['total_payments'] ?? 0),
        'pending_payments' => (int) ($stats['pending_payments'] ?? 0),
        'paid_payments' => (int) ($stats['paid_payments'] ?? 0),
        'failed_payments' => (int) ($stats['failed_payments'] ?? 0),
    ];
}

function recent_payments(int $userId, int $limit = 6): array
{
    $limit = max(1, min(20, $limit));
    $statement = db()->prepare("SELECT * FROM payments WHERE created_by = :created_by ORDER BY created_at DESC LIMIT {$limit}");
    $statement->execute([':created_by' => $userId]);

    return $statement->fetchAll();
}

function json_response(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function process_callback_payload(array $payload, string $rawJson): array
{
    $orderId = trim((string) ($payload['order_id'] ?? ''));
    $incomingStatus = strtoupper(trim((string) ($payload['status'] ?? '')));
    $amount = normalize_amount($payload['amount'] ?? null);
    $mappedStatuses = [
        'SUCCESS' => 'PAID',
        'FAILED' => 'FAILED',
        'CANCELLED' => 'CANCELLED',
        'TIMEOUT' => 'TIMEOUT',
    ];

    if ($orderId === '' || $amount === null || !isset($mappedStatuses[$incomingStatus])) {
        return [
            'success' => false,
            'message' => 'Invalid callback payload.',
            'code' => 422,
        ];
    }

    $payment = get_payment_by_order_id($orderId);

    if ($payment === null) {
        return [
            'success' => false,
            'message' => 'Payment not found.',
            'code' => 404,
        ];
    }

    if ((float) $payment['amount'] !== (float) $amount) {
        log_payment_action((int) $payment['id'], 'CALLBACK_REJECTED', 'Gateway callback amount did not match the expected payment amount.', $rawJson);

        return [
            'success' => false,
            'message' => 'Amount mismatch.',
            'code' => 409,
        ];
    }

    $finalStatus = $mappedStatuses[$incomingStatus];
    $pdo = db();

    try {
        $pdo->beginTransaction();

        if (is_final_status($payment['status'])) {
            log_payment_action((int) $payment['id'], 'CALLBACK_IGNORED', 'Callback received after the payment had already reached a final state.', $rawJson);
            $pdo->commit();

            return [
                'success' => true,
                'message' => 'Payment already finalized.',
                'order_id' => $orderId,
                'payment_status' => $payment['status'],
                'code' => 200,
            ];
        }

        $updateStatement = $pdo->prepare(
            'UPDATE payments
             SET status = :status,
                 gateway_reference = :gateway_reference,
                 gateway_response = :gateway_response,
                 callback_received_at = NOW(),
                 updated_at = NOW()
             WHERE id = :payment_id'
        );
        $updateStatement->execute([
            ':status' => $finalStatus,
            ':gateway_reference' => $payload['gateway_reference'] ?? $payment['gateway_reference'],
            ':gateway_response' => $rawJson,
            ':payment_id' => $payment['id'],
        ]);

        log_payment_action((int) $payment['id'], 'CALLBACK_RECEIVED', 'Gateway callback processed and mapped to ' . $finalStatus . '.', $rawJson);
        log_payment_action((int) $payment['id'], 'STATUS_UPDATED', 'Payment status moved to ' . $finalStatus . '.', ['source' => 'gateway_callback']);
        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Callback processed successfully.',
            'order_id' => $orderId,
            'payment_status' => $finalStatus,
            'code' => 200,
        ];
    } catch (Throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            'success' => false,
            'message' => 'Callback processing failed.',
            'code' => 500,
        ];
    }
}

function send_simulated_callback(string $callbackUrl, array $payload): array
{
    $rawJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $headers = [
        'Content-Type: application/json',
        'X-Sandbox-Key: ' . app_config('sandbox.callback_key'),
    ];

    if (function_exists('curl_init')) {
        $curl = curl_init($callbackUrl);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $rawJson,
            CURLOPT_TIMEOUT => 15,
        ]);
        $body = curl_exec($curl);
        $error = curl_error($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($body === false) {
            return [
                'success' => false,
                'message' => 'Simulator could not reach the callback endpoint: ' . $error,
                'status_code' => 0,
                'body' => '',
            ];
        }

        return [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'message' => 'Callback dispatched over HTTP.',
            'status_code' => $statusCode,
            'body' => (string) $body,
            'decoded' => json_decode((string) $body, true),
        ];
    }

    $response = process_callback_payload($payload, $rawJson ?: '{}');

    return [
        'success' => (bool) ($response['success'] ?? false),
        'message' => 'Callback processed through the internal fallback dispatcher.',
        'status_code' => (int) ($response['code'] ?? 200),
        'body' => json_encode($response, JSON_UNESCAPED_SLASHES),
        'decoded' => $response,
    ];
}
