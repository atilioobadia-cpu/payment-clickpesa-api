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
$paymentId = (int) ($_GET['id'] ?? 0);
$payment = $paymentId > 0 ? get_payment_by_id($paymentId) : null;

if ($payment === null || (int) $payment['created_by'] !== (int) $user['id']) {
    flash('error', 'Payment details could not be found.');
    redirect('payments.php');
}

if (payment_is_clickpesa($payment) && !is_final_status((string) $payment['status'])) {
    $syncResult = sync_clickpesa_payment($payment);

    if ($syncResult['success'] ?? false) {
        $payment = get_payment_by_id($paymentId) ?? $payment;
    }
}

$logs = get_payment_logs((int) $payment['id']);
$decodedGatewayResponse = json_decode((string) $payment['gateway_response'], true);
$gatewayResponseOutput = $decodedGatewayResponse !== null
    ? json_encode($decodedGatewayResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    : ((string) $payment['gateway_response'] !== '' ? (string) $payment['gateway_response'] : 'No callback payload stored yet.');

$pageTitle = 'Payment Details';
$pageHeading = 'Payment Details';
$pageSubtitle = 'Review the full payment record, callback data, and event timeline.';
$pageBreadcrumbs = ['Payment Sandbox', 'Payment Details'];
$activePage = 'payments';

require_once __DIR__ . '/includes/header.php';
?>
<section class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Record Details</p>
                <h2><?= e($payment['order_id']) ?></h2>
            </div>
            <div class="inline-actions">
                <a class="button button-secondary" href="<?= e(route_path('payment_status.php?order_id=' . urlencode($payment['order_id']))) ?>"><?= app_icon('list', 'button-icon') ?><span>Status page</span></a>
                <?php if ($payment['status'] === 'PAID'): ?>
                    <button type="button" class="button button-primary" data-print-page><?= app_icon('print', 'button-icon') ?><span>Print receipt</span></button>
                <?php endif; ?>
            </div>
        </div>

        <div class="detail-list">
            <div><span>Order ID</span><strong id="view-order-id"><?= e($payment['order_id']) ?></strong><button type="button" class="button button-ghost button-inline" data-copy-target="view-order-id"><?= app_icon('copy', 'button-icon') ?><span>Copy</span></button></div>
            <div><span>Customer name</span><strong><?= e($payment['customer_name']) ?></strong></div>
            <div><span>Phone number</span><strong><?= e($payment['phone']) ?></strong></div>
            <div><span>Network</span><strong><?= e($payment['network']) ?></strong></div>
            <div><span>Amount</span><strong><?= e(format_currency($payment['amount'], $payment['currency'])) ?></strong></div>
            <div><span>Status</span><strong><span class="status-badge" data-status="<?= e(status_slug($payment['status'])) ?>"><?= e($payment['status']) ?></span></strong></div>
            <div><span>Gateway reference</span><strong><?= e($payment['gateway_reference'] ?: 'Not issued') ?></strong></div>
            <div><span>Created by</span><strong><?= e($payment['created_by_name'] ?: 'Unknown user') ?></strong></div>
            <div><span>Created at</span><strong><?= e(format_datetime($payment['created_at'])) ?></strong></div>
            <div><span>Last updated</span><strong><?= e(format_datetime($payment['updated_at'])) ?></strong></div>
            <div><span>Callback received</span><strong><?= e(format_datetime($payment['callback_received_at'])) ?></strong></div>
            <div class="detail-full"><span>Description</span><strong><?= e($payment['description'] ?: 'No description provided') ?></strong></div>
        </div>
    </article>

    <aside class="stack-lg">
        <article class="panel">
            <div class="panel-header">
                <div>
                    <p class="eyebrow">Callback Payload</p>
                    <h2>Stored response data</h2>
                </div>
            </div>
            <pre class="code-block"><?= e($gatewayResponseOutput) ?></pre>
        </article>

        <article class="panel">
            <div class="panel-header">
                <div>
                    <p class="eyebrow">Timeline</p>
                    <h2>Lifecycle events</h2>
                </div>
            </div>

            <?php if ($logs === []): ?>
                <div class="empty-state compact">
                    <p>No payment logs are available yet.</p>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($logs as $log): ?>
                        <div class="timeline-item">
                            <strong><?= e($log['action']) ?></strong>
                            <p><?= e($log['message']) ?></p>
                            <span><?= e(format_datetime($log['created_at'])) ?></span>
                            <?php if (!empty($log['raw_data'])): ?>
                                <pre class="code-block compact"><?= e($log['raw_data']) ?></pre>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    </aside>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
