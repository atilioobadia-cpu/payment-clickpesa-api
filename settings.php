<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/clickpesa.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_login();
ensure_settings_exist();

$oldInput = consume_old_input();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $action = request_value($_POST, 'action', 'save_settings');

    if ($action === 'test_clickpesa_token') {
        $testResponse = clickpesa_generate_token(true);

        if ($testResponse['success'] ?? false) {
            flash('success', 'ClickPesa token test succeeded. The credentials are valid and the token endpoint is reachable.');
        } else {
            flash('error', clickpesa_error_message($testResponse, 'ClickPesa token test failed.'));
        }

        redirect('settings.php');
    }

    $merchantName = request_value($_POST, 'merchant_name');
    $callbackUrl = request_value($_POST, 'callback_url');
    $sandboxEnabled = isset($_POST['sandbox_mode_enabled']) ? '1' : '0';
    $apiMode = request_value($_POST, 'api_mode', 'ClickPesa Test');
    $errors = [];

    if ($merchantName === '') {
        $errors[] = 'Merchant name is required.';
    }

    if ($callbackUrl === '' || !filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'Enter a valid absolute callback URL.';
    }

    if (!in_array($apiMode, ['Simulation', 'ClickPesa Test'], true)) {
        $errors[] = 'Choose a valid API mode.';
    }

    if ($errors !== []) {
        remember_old_input([
            'merchant_name' => $merchantName,
            'callback_url' => $callbackUrl,
            'sandbox_mode_enabled' => $sandboxEnabled,
            'api_mode' => $apiMode,
        ]);
        flash('error', implode(' ', $errors));
        redirect('settings.php');
    }

    save_settings([
        'merchant_name' => $merchantName,
        'callback_url' => $callbackUrl,
        'sandbox_mode_enabled' => $sandboxEnabled,
        'api_mode' => $apiMode,
    ]);

    flash('success', 'Gateway settings saved successfully.');
    redirect('settings.php');
}

$settings = get_settings_map();
$pageTitle = 'Settings';
$pageHeading = 'Settings';
$pageSubtitle = 'Manage merchant identity and callback behavior for the sandbox.';
$pageBreadcrumbs = ['Payment Sandbox', 'Settings'];
$pageActions = [
    ['label' => 'Gateway Simulator', 'href' => route_path('simulator.php'), 'variant' => 'button-secondary', 'icon' => 'simulator'],
    ['label' => 'New Payment', 'href' => route_path('payment_form.php'), 'variant' => 'button-dark', 'icon' => 'plus'],
];
$activePage = 'settings';

require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Sandbox Configuration</p>
            <h2>Gateway and merchant settings</h2>
        </div>
        <p class="panel-note">ClickPesa credentials stay in config/config.php. Use the token test below to confirm the credentials without sending a payment push.</p>
    </div>

    <form method="post" action="<?= e(route_path('settings.php')) ?>" class="form-grid" data-loading-form>
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="save_settings">
        <label class="field">
            <span>Merchant name</span>
            <input type="text" name="merchant_name" value="<?= e($oldInput['merchant_name'] ?? ($settings['merchant_name'] ?? '')) ?>" required>
        </label>

        <label class="field">
            <span>Callback URL</span>
            <input type="url" name="callback_url" value="<?= e($oldInput['callback_url'] ?? ($settings['callback_url'] ?? app_url('payment_callback.php'))) ?>" required>
        </label>

        <label class="field">
            <span>API mode</span>
            <select name="api_mode">
                <?php $selectedMode = $oldInput['api_mode'] ?? ($settings['api_mode'] ?? 'ClickPesa Test'); ?>
                <option value="ClickPesa Test" <?= $selectedMode === 'ClickPesa Test' ? 'selected' : '' ?>>ClickPesa Test</option>
                <option value="Simulation" <?= $selectedMode === 'Simulation' ? 'selected' : '' ?>>Simulation</option>
            </select>
        </label>

        <label class="field checkbox-field">
            <input type="checkbox" name="sandbox_mode_enabled" value="1" <?= ($oldInput['sandbox_mode_enabled'] ?? ($settings['sandbox_mode_enabled'] ?? '1')) === '1' ? 'checked' : '' ?>>
            <span>Sandbox mode enabled</span>
        </label>

        <div class="field field-full info-card">
            <strong>Stored in config only</strong>
            <p>ClickPesa client ID: <?= e(mask_secret((string) app_config('clickpesa.client_id'))) ?></p>
            <p>ClickPesa API key: <?= e(mask_secret((string) app_config('clickpesa.api_key'))) ?></p>
            <p>Generate token: <?= e((string) app_config('clickpesa.token_url')) ?></p>
            <p>Preview USSD push: <?= e((string) app_config('clickpesa.preview_url')) ?></p>
            <p>Initiate USSD push: <?= e((string) app_config('clickpesa.initiate_url')) ?></p>
            <p>Query payments: <?= e((string) app_config('clickpesa.query_url')) ?></p>
            <p>Simulator callback secret: <?= e(mask_secret((string) app_config('sandbox.callback_key'))) ?></p>
        </div>

        <div class="form-actions field-full">
            <button type="submit" class="button button-primary" data-loading-text="Saving settings..."><?= app_icon('settings', 'button-icon') ?><span>Save settings</span></button>
        </div>
    </form>

    <form method="post" action="<?= e(route_path('settings.php')) ?>" class="inline-actions" data-loading-form>
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="test_clickpesa_token">
        <button type="submit" class="button button-secondary" data-loading-text="Testing token..."><?= app_icon('refresh', 'button-icon') ?><span>Test ClickPesa token</span></button>
    </form>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
