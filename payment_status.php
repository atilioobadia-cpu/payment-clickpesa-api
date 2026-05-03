<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/clickpesa.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_login();

$user = current_user();
$orderId = request_value($_GET, 'order_id');
$payment = $orderId !== '' ? get_payment_by_order_id($orderId) : null;

if ($payment === null || (int) $payment['created_by'] !== (int) $user['id']) {
    flash('error', 'Payment record not found.');
    redirect('payments.php');
}

if (payment_is_clickpesa($payment) && !is_final_status((string) $payment['status'])) {
    $syncResult = sync_clickpesa_payment($payment);

    if ($syncResult['success'] ?? false) {
        $payment = get_payment_by_order_id($orderId) ?? $payment;
    }
}

$logs = get_payment_logs((int) $payment['id']);
$statusMessage = payment_status_message($payment['status']);
$bodyAttributes = [];

if (strtoupper($payment['status']) === 'PROCESSING') {
    $bodyAttributes = [
        'data-refresh-url' => route_path('payment_status.php?order_id=' . urlencode($payment['order_id'])),
        'data-refresh-interval' => '5000',
    ];
}

$pageTitle = 'Payment Status';
$pageHeading = 'Payment Status';
$pageSubtitle = 'Review the live status of this sandbox transaction.';
$pageBreadcrumbs = ['Payment Sandbox', 'Payment Status'];
$activePage = 'payments';

require_once __DIR__ . '/includes/header.php';
?>
<section class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Order Overview</p>
                <h2>Status for <?= e($payment['order_id']) ?></h2>
            </div>
            <div class="inline-actions">
                <a class="button button-secondary" href="<?= e(route_path('payments.php')) ?>"><?= app_icon('list', 'button-icon') ?><span>All payments</span></a>
                <?php if ($payment['status'] === 'PAID'): ?>
                    <button type="button" class="button button-primary" data-print-page><?= app_icon('print', 'button-icon') ?><span>Print receipt</span></button>
                <?php endif; ?>
            </div>
        </div>

        <div class="status-banner" data-status="<?= e(status_slug($payment['status'])) ?>">
            <div>
                <strong><?= e($payment['status']) ?></strong>
                <p><?= e($statusMessage) ?></p>
            </div>
            <span class="status-badge" data-status="<?= e(status_slug($payment['status'])) ?>"><?= e($payment['status']) ?></span>
        </div>

        <div class="detail-list">
            <div>
                <span>Order ID</span>
                <strong id="order-id-value"><?= e($payment['order_id']) ?></strong>
                <button type="button" class="button button-ghost button-inline" data-copy-target="order-id-value"><?= app_icon('copy', 'button-icon') ?><span>Copy</span></button>
            </div>
            <div><span>Phone number</span><strong><?= e($payment['phone']) ?></strong></div>
            <div><span>Network</span><strong><?= e($payment['network']) ?></strong></div>
            <div><span>Amount</span><strong><?= e(format_currency($payment['amount'], $payment['currency'])) ?></strong></div>
            <div><span>Gateway reference</span><strong><?= e($payment['gateway_reference'] ?: 'Pending assignment') ?></strong></div>
            <div><span>Description</span><strong><?= e($payment['description'] ?: 'No description provided') ?></strong></div>
        </div>
    </article>

    <aside class="stack-lg">
        <article class="panel">
            <div class="panel-header">
                <div>
                    <p class="eyebrow">Timeline</p>
                    <h2>Progress updates</h2>
                </div>
            </div>

            <?php if ($logs === []): ?>
                <div class="empty-state compact">
                    <p>No timeline events are available yet.</p>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($logs as $log): ?>
                        <div class="timeline-item">
                            <strong><?= e($log['action']) ?></strong>
                            <p><?= e($log['message']) ?></p>
                            <span><?= e(format_datetime($log['created_at'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <?php if ($payment['status'] === 'PAID'): ?>
            <article class="panel receipt-card">
                <div class="panel-header">
                    <div>
                        <p class="eyebrow">Receipt</p>
                        <h2>Successful payment receipt</h2>
                    </div>
                </div>
                <div class="receipt-grid">
                    <div><span>Customer</span><strong><?= e($payment['customer_name']) ?></strong></div>
                    <div><span>Order</span><strong><?= e($payment['order_id']) ?></strong></div>
                    <div><span>Amount</span><strong><?= e(format_currency($payment['amount'], $payment['currency'])) ?></strong></div>
                    <div><span>Reference</span><strong><?= e($payment['gateway_reference']) ?></strong></div>
                    <div><span>Paid on</span><strong><?= e(format_datetime($payment['callback_received_at'])) ?></strong></div>
                    <div><span>Status</span><strong><?= e($payment['status']) ?></strong></div>
                </div>
            </article>
        <?php endif; ?>
    </aside>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
