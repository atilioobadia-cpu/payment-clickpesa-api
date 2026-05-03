<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_login();
ensure_settings_exist();

$user = current_user();
$stats = dashboard_stats((int) $user['id']);
$recentPayments = recent_payments((int) $user['id']);

$pageTitle = 'Dashboard';
$pageHeading = 'Payment Sandbox';
$pageSubtitle = 'Track payment attempts, launch new pushes, and manage the sandbox from one workspace.';
$pageBreadcrumbs = ['Public', 'Payment Sandbox'];
$pageActions = [
    ['label' => 'Create Payment', 'href' => route_path('payment_form.php'), 'variant' => 'button-primary', 'icon' => 'plus'],
    ['label' => 'List View', 'href' => route_path('payments.php'), 'variant' => 'button-secondary', 'icon' => 'list'],
    ['label' => 'Settings', 'href' => route_path('settings.php'), 'variant' => 'button-dark', 'icon' => 'settings'],
];
$activePage = 'dashboard';

require_once __DIR__ . '/includes/header.php';
?>
<section class="summary-strip">
    <article class="summary-card">
        <span>Total Payments</span>
        <strong><?= e((string) $stats['total_payments']) ?></strong>
        <small>All created orders</small>
    </article>
    <article class="summary-card">
        <span>Pending Payments</span>
        <strong><?= e((string) $stats['pending_payments']) ?></strong>
        <small>Waiting on customer action</small>
    </article>
    <article class="summary-card">
        <span>Paid Payments</span>
        <strong><?= e((string) $stats['paid_payments']) ?></strong>
        <small>Successful collections</small>
    </article>
    <article class="summary-card">
        <span>Failed Payments</span>
        <strong><?= e((string) $stats['failed_payments']) ?></strong>
        <small>Rejected or cancelled flows</small>
    </article>
</section>

<section class="workspace-grid">
    <article class="workspace-card">
        <div class="panel-header compact">
            <div>
                <p class="eyebrow">Payment Operations</p>
                <h2>Collections workspace</h2>
            </div>
        </div>
        <div class="workspace-links">
            <a class="workspace-link" href="<?= e(route_path('payment_form.php')) ?>">Create mobile money payment <?= app_icon('external', 'workspace-link-icon') ?></a>
            <a class="workspace-link" href="<?= e(route_path('payments.php')) ?>">Review all payment records <?= app_icon('external', 'workspace-link-icon') ?></a>
            <a class="workspace-link" href="<?= e(route_path('payment_form.php')) ?>">Start a ClickPesa USSD push <?= app_icon('external', 'workspace-link-icon') ?></a>
        </div>
    </article>

    <article class="workspace-card">
        <div class="panel-header compact">
            <div>
                <p class="eyebrow">Gateway & Tools</p>
                <h2>Testing controls</h2>
            </div>
        </div>
        <div class="workspace-links">
            <a class="workspace-link" href="<?= e(route_path('simulator.php')) ?>">Open gateway simulator <?= app_icon('external', 'workspace-link-icon') ?></a>
            <a class="workspace-link" href="<?= e(route_path('settings.php')) ?>">Check ClickPesa credentials <?= app_icon('external', 'workspace-link-icon') ?></a>
            <a class="workspace-link" href="<?= e(route_path('settings.php')) ?>">Switch API mode or callback URL <?= app_icon('external', 'workspace-link-icon') ?></a>
        </div>
    </article>

    <article class="workspace-card">
        <div class="panel-header compact">
            <div>
                <p class="eyebrow">Current Status</p>
                <h2>Environment summary</h2>
            </div>
        </div>
        <div class="workspace-meta-list">
            <div><span>Merchant</span><strong><?= e(get_setting('merchant_name', 'Payment Sandbox Demo Merchant') ?? 'Payment Sandbox Demo Merchant') ?></strong></div>
            <div><span>API Mode</span><strong><?= e(get_setting('api_mode', 'ClickPesa Test') ?? 'ClickPesa Test') ?></strong></div>
            <div><span>Currency</span><strong><?= e((string) app_config('app.currency', 'TZS')) ?></strong></div>
        </div>
    </article>
</section>

<section class="panel list-panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Recent Activity</p>
            <h2>Latest transactions</h2>
        </div>
        <a class="button button-secondary" href="<?= e(route_path('payments.php')) ?>">Open payments list</a>
    </div>

    <?php if ($recentPayments === []): ?>
        <div class="empty-state">
            <h3>No payments yet</h3>
            <p>Create your first sandbox payment to see live processing updates here.</p>
            <a class="button button-primary" href="<?= e(route_path('payment_form.php')) ?>">Create payment</a>
        </div>
    <?php else: ?>
        <div class="table-wrap list-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Network</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentPayments as $payment): ?>
                        <tr>
                            <td><input type="checkbox" aria-label="Select payment"></td>
                            <td><?= e($payment['order_id']) ?></td>
                            <td><?= e($payment['customer_name']) ?></td>
                            <td><?= e($payment['network']) ?></td>
                            <td><?= e(format_currency($payment['amount'], $payment['currency'])) ?></td>
                            <td><span class="status-badge" data-status="<?= e(status_slug($payment['status'])) ?>"><?= e($payment['status']) ?></span></td>
                            <td><?= e(format_datetime($payment['created_at'])) ?></td>
                            <td><a class="button button-ghost button-small" href="<?= e(route_path('payment_status.php?order_id=' . urlencode($payment['order_id']))) ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
