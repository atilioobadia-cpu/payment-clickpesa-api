<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_login();

$oldInput = consume_old_input();
$networks = network_options();

$pageTitle = 'New Payment';
$pageHeading = 'New Payment';
$pageSubtitle = 'Create a mobile money request and send it through ClickPesa test mode or the local simulator.';
$pageBreadcrumbs = ['Payment Sandbox', 'New Payment'];
$pageActions = [
    ['label' => 'All Payments', 'href' => route_path('payments.php'), 'variant' => 'button-secondary', 'icon' => 'list'],
    ['label' => 'Gateway Simulator', 'href' => route_path('simulator.php'), 'variant' => 'button-dark', 'icon' => 'simulator'],
];
$activePage = 'payment_form';

require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Payment Request</p>
            <h2>Submit a mobile money payment</h2>
        </div>
        <p class="panel-note">ClickPesa Test mode previews the request, then sends a real USSD push to a supported Tanzania number. Simulation mode keeps the flow local for demos.</p>
    </div>

    <form method="post" action="<?= e(route_path('pay.php')) ?>" class="form-grid" data-loading-form>
        <?= csrf_input() ?>
        <label class="field">
            <span>Customer name</span>
            <input type="text" name="customer_name" value="<?= e($oldInput['customer_name'] ?? '') ?>" required>
        </label>

        <label class="field">
            <span>Phone number</span>
            <input type="text" name="phone" value="<?= e($oldInput['phone'] ?? '') ?>" placeholder="+255712345678" required>
        </label>

        <label class="field">
            <span>Network</span>
            <select name="network" required>
                <option value="">Select network</option>
                <?php foreach ($networks as $network): ?>
                    <option value="<?= e($network) ?>" <?= ($oldInput['network'] ?? '') === $network ? 'selected' : '' ?>><?= e($network) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="field">
            <span>Amount</span>
            <input type="number" name="amount" min="1" max="3000000" step="0.01" value="<?= e($oldInput['amount'] ?? '') ?>" required>
        </label>

        <label class="field field-full">
            <span>Description</span>
            <textarea name="description" rows="4" placeholder="Utility payment demo, order testing, classroom simulation..."><?= e($oldInput['description'] ?? '') ?></textarea>
        </label>

        <div class="form-actions field-full">
            <a class="button button-secondary" href="<?= e(route_path('dashboard.php')) ?>">Cancel</a>
            <button type="submit" class="button button-primary" data-loading-text="Submitting payment..."><?= app_icon('plus', 'button-icon') ?><span>Submit payment</span></button>
        </div>
    </form>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
