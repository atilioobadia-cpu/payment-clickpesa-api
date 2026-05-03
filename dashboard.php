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
    ['label' => 'New Payment', 'href' => route_path('payment_form.php'), 'variant' => 'button-primary', 'icon' => 'plus'],
    ['label' => 'Transactions', 'href' => route_path('payments.php'), 'variant' => 'button-secondary', 'icon' => 'transactions'],
    ['label' => 'Settings', 'href' => route_path('settings.php'), 'variant' => 'button-secondary', 'icon' => 'settings'],
];
$activePage = 'dashboard';

require_once __DIR__ . '/includes/header.php';
?>
<section class="desk-summary-grid">
    <article class="desk-summary-card">
        <span>Total Payments</span>
        <strong><?= e((string) $stats['total_payments']) ?></strong>
        <small>All created orders</small>
    </article>
    <article class="desk-summary-card">
        <span>Pending Payments</span>
        <strong><?= e((string) $stats['pending_payments']) ?></strong>
        <small>Waiting on customer confirmation</small>
    </article>
    <article class="desk-summary-card">
        <span>Paid Payments</span>
        <strong><?= e((string) $stats['paid_payments']) ?></strong>
        <small>Successful collections</small>
    </article>
    <article class="desk-summary-card">
        <span>Failed Payments</span>
        <strong><?= e((string) $stats['failed_payments']) ?></strong>
        <small>Rejected or cancelled attempts</small>
    </article>
</section>

<section class="workspace-board">
    <article class="workspace-section">
        <div class="workspace-section-head">
            <div>
                <p class="eyebrow">Operations</p>
                <h2>Payment Handling</h2>
            </div>
            <p>Create, submit, and track mobile money requests from one desk.</p>
        </div>
        <div class="workspace-shortcuts">
            <a class="workspace-shortcut" href="<?= e(route_path('payment_form.php')) ?>">
                <div class="workspace-shortcut-copy">
                    <strong>New Payment</strong>
                    <span>Open the payment document form and send a fresh request.</span>
                </div>
                <?= app_icon('external', 'workspace-shortcut-icon') ?>
            </a>
            <a class="workspace-shortcut" href="<?= e($recentPayments !== [] ? route_path('payment_status.php?order_id=' . urlencode($recentPayments[0]['order_id'])) : route_path('payment_form.php')) ?>">
                <div class="workspace-shortcut-copy">
                    <strong>Latest Payment Status</strong>
                    <span><?= e($recentPayments !== [] ? 'Jump to the newest payment record and review its current state.' : 'Open the payment form and create a new record first.') ?></span>
                </div>
                <?= app_icon('external', 'workspace-shortcut-icon') ?>
            </a>
        </div>
    </article>

    <article class="workspace-section">
        <div class="workspace-section-head">
            <div>
                <p class="eyebrow">Transactions</p>
                <h2>Customer & Transaction</h2>
            </div>
            <p>Inspect payments, customers, phones, and transaction history in list view.</p>
        </div>
        <div class="workspace-shortcuts">
            <a class="workspace-shortcut" href="<?= e(route_path('payments.php')) ?>">
                <div class="workspace-shortcut-copy">
                    <strong>Transactions</strong>
                    <span>Search by order ID, phone number, customer name, status, and date.</span>
                </div>
                <?= app_icon('external', 'workspace-shortcut-icon') ?>
            </a>
            <a class="workspace-shortcut" href="<?= e(route_path('payments.php?status=PAID')) ?>">
                <div class="workspace-shortcut-copy">
                    <strong>Reports</strong>
                    <span>Open filtered payment records and use them as lightweight reports.</span>
                </div>
                <?= app_icon('external', 'workspace-shortcut-icon') ?>
            </a>
        </div>
    </article>

    <article class="workspace-section">
        <div class="workspace-section-head">
            <div>
                <p class="eyebrow">Testing</p>
                <h2>Testing & Simulator</h2>
            </div>
            <p>Use sandbox tools to complete callbacks and observe the payment timeline.</p>
        </div>
        <div class="workspace-shortcuts">
            <a class="workspace-shortcut" href="<?= e(route_path('simulator.php')) ?>">
                <div class="workspace-shortcut-copy">
                    <strong>Gateway Simulator</strong>
                    <span>Trigger PAID, FAILED, CANCELLED, or TIMEOUT callback outcomes.</span>
                </div>
                <?= app_icon('external', 'workspace-shortcut-icon') ?>
            </a>
            <a class="workspace-shortcut" href="<?= e(route_path('settings.php')) ?>">
                <div class="workspace-shortcut-copy">
                    <strong>ClickPesa Settings</strong>
                    <span>Switch between simulation and live test mode without touching backend code.</span>
                </div>
                <?= app_icon('external', 'workspace-shortcut-icon') ?>
            </a>
        </div>
    </article>

    <article class="workspace-section">
        <div class="workspace-section-head">
            <div>
                <p class="eyebrow">Configuration</p>
                <h2>Settings & Reports</h2>
            </div>
            <p>Review the current merchant, API mode, and working currency at a glance.</p>
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
        <a class="button button-secondary" href="<?= e(route_path('payments.php')) ?>"><?= app_icon('transactions', 'button-icon') ?><span>Open payments list</span></a>
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
