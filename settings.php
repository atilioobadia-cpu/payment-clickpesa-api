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
    ['label' => 'New Payment', 'href' => route_path('payment_form.php'), 'variant' => 'button-primary', 'icon' => 'plus'],
];
$activePage = 'settings';

require_once __DIR__ . '/includes/header.php';
?>
<section class="document-layout">
    <article class="panel document-sheet">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Sandbox Configuration</p>
                <h2>Gateway and merchant settings</h2>
            </div>
            <p class="panel-note">ClickPesa credentials stay in config/config.php. This page controls only the saved sandbox-facing behavior and display settings.</p>
        </div>

        <form method="post" action="<?= e(route_path('settings.php')) ?>" class="stack-lg" data-loading-form>
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="save_settings">

            <section class="form-section">
                <div class="form-section-head">
                    <div>
                        <h3>Merchant profile</h3>
                        <p>These values are stored in the database and used by the demo workspace.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <label class="field">
                        <span>Merchant name</span>
                        <input type="text" name="merchant_name" value="<?= e($oldInput['merchant_name'] ?? ($settings['merchant_name'] ?? '')) ?>" required>
                    </label>

                    <label class="field">
                        <span>Callback URL</span>
                        <input type="url" name="callback_url" value="<?= e($oldInput['callback_url'] ?? ($settings['callback_url'] ?? app_url('payment_callback.php'))) ?>" required>
                    </label>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-head">
                    <div>
                        <h3>Execution mode</h3>
                        <p>Switch between real ClickPesa test requests and the local simulator.</p>
                    </div>
                </div>
                <div class="form-grid">
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
                </div>
            </section>

            <div class="form-actions field-full">
                <button type="submit" class="button button-primary" data-loading-text="Saving settings..."><?= app_icon('settings', 'button-icon') ?><span>Save settings</span></button>
            </div>
        </form>
    </article>

    <aside class="stack-lg">
        <article class="panel side-note-card">
            <div class="panel-header compact">
                <div>
                    <p class="eyebrow">Stored In Config</p>
                    <h2>ClickPesa reference</h2>
                </div>
            </div>
            <div class="info-card">
                <p>Client ID: <?= e(mask_secret((string) app_config('clickpesa.client_id'))) ?></p>
                <p>API Key: <?= e(mask_secret((string) app_config('clickpesa.api_key'))) ?></p>
                <p>Generate token: <?= e((string) app_config('clickpesa.token_url')) ?></p>
                <p>Preview USSD push: <?= e((string) app_config('clickpesa.preview_url')) ?></p>
                <p>Initiate USSD push: <?= e((string) app_config('clickpesa.initiate_url')) ?></p>
                <p>Query payments: <?= e((string) app_config('clickpesa.query_url')) ?></p>
                <p>Simulator callback secret: <?= e(mask_secret((string) app_config('sandbox.callback_key'))) ?></p>
            </div>
        </article>

        <article class="panel side-note-card">
            <div class="panel-header compact">
                <div>
                    <p class="eyebrow">Connection Test</p>
                    <h2>Verify credentials</h2>
                </div>
            </div>
            <p class="panel-note">Use the token test to confirm the credentials and endpoint reachability without sending a payment request.</p>
            <form method="post" action="<?= e(route_path('settings.php')) ?>" class="stack-sm" data-loading-form>
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="test_clickpesa_token">
                <button type="submit" class="button button-secondary button-block" data-loading-text="Testing token..."><?= app_icon('refresh', 'button-icon') ?><span>Test ClickPesa token</span></button>
            </form>
        </article>
    </aside>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
