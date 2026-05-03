<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $paymentId = (int) ($_POST['payment_id'] ?? 0);
    $selectedAction = strtoupper(request_value($_POST, 'callback_status'));
    $allowedActions = ['SUCCESS', 'FAILED', 'CANCELLED', 'TIMEOUT'];
    $payment = $paymentId > 0 ? get_payment_by_id($paymentId) : null;

    if ($payment === null) {
        flash('error', 'The selected payment could not be found.');
        redirect('simulator.php');
    }

    if (!in_array($selectedAction, $allowedActions, true)) {
        flash('error', 'Choose a valid simulated callback action.');
        redirect('simulator.php');
    }

    if (strtoupper($payment['status']) !== 'PROCESSING') {
        flash('error', 'Only processing payments can be updated from the simulator.');
        redirect('simulator.php');
    }

    $payload = [
        'order_id' => $payment['order_id'],
        'amount' => (float) $payment['amount'],
        'status' => $selectedAction,
        'gateway_reference' => $payment['gateway_reference'],
        'phone' => $payment['phone'],
        'network' => $payment['network'],
        'received_at' => date(DATE_ATOM),
    ];

    $callbackUrl = get_setting('callback_url', app_url('payment_callback.php')) ?: app_url('payment_callback.php');
    $result = send_simulated_callback($callbackUrl, $payload);

    if ($result['success']) {
        $decoded = $result['decoded'] ?? [];
        $status = $decoded['payment_status'] ?? $selectedAction;
        flash('success', 'Callback sent successfully. Payment is now ' . $status . '.');
    } else {
        flash('error', 'Callback dispatch failed. ' . ($result['message'] ?? 'Unknown simulator error.'));
    }

    redirect('simulator.php');
}

$processingStatement = db()->query(
    'SELECT payments.*, users.name AS creator_name
     FROM payments
     LEFT JOIN users ON users.id = payments.created_by
     WHERE payments.status = "PROCESSING"
       AND payments.gateway_reference LIKE "GATE-%"
     ORDER BY payments.created_at ASC'
);
$processingPayments = $processingStatement->fetchAll();

$recentUpdatesStatement = db()->query(
    'SELECT payments.*, users.name AS creator_name
     FROM payments
     LEFT JOIN users ON users.id = payments.created_by
     WHERE payments.status IN ("PAID", "FAILED", "CANCELLED", "TIMEOUT")
     ORDER BY payments.updated_at DESC
     LIMIT 8'
);
$recentUpdates = $recentUpdatesStatement->fetchAll();

$pageTitle = 'Gateway Simulator';
$pageHeading = 'Gateway Simulator';
$pageSubtitle = 'Trigger sandbox callback outcomes for any payment currently in processing.';
$pageBreadcrumbs = ['Payment Sandbox', 'Gateway Simulator'];
$pageActions = [
    ['label' => 'Refresh', 'href' => route_path('simulator.php'), 'variant' => 'button-secondary', 'icon' => 'refresh'],
    ['label' => 'New Payment', 'href' => route_path('payment_form.php'), 'variant' => 'button-dark', 'icon' => 'plus'],
];
$activePage = 'simulator';

require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Simulator Queue</p>
            <h2>Processing payments awaiting callback</h2>
        </div>
        <p class="panel-note">The simulator posts JSON to the callback endpoint, using the shared sandbox secret stored only in config.</p>
    </div>

    <?php if ($processingPayments === []): ?>
        <div class="empty-state">
            <h3>No processing payments</h3>
            <p>Create a new payment first, then come back here to simulate PAID, FAILED, CANCELLED, or TIMEOUT responses.</p>
            <a class="button button-primary" href="<?= e(route_path('payment_form.php')) ?>">Create payment</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Network</th>
                        <th>Amount</th>
                        <th>Reference</th>
                        <th>Created by</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($processingPayments as $payment): ?>
                        <tr>
                            <td><?= e($payment['order_id']) ?></td>
                            <td><?= e($payment['customer_name']) ?></td>
                            <td><?= e($payment['network']) ?></td>
                            <td><?= e(format_currency($payment['amount'], $payment['currency'])) ?></td>
                            <td><?= e($payment['gateway_reference']) ?></td>
                            <td><?= e($payment['creator_name'] ?: 'Unknown user') ?></td>
                            <td>
                                <div class="button-row">
                                    <?php foreach (['SUCCESS' => 'Mark as PAID', 'FAILED' => 'Mark as FAILED', 'CANCELLED' => 'Mark as CANCELLED', 'TIMEOUT' => 'Mark as TIMEOUT'] as $status => $label): ?>
                                        <form method="post" action="<?= e(route_path('simulator.php')) ?>" data-loading-form>
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="payment_id" value="<?= e((string) $payment['id']) ?>">
                                            <input type="hidden" name="callback_status" value="<?= e($status) ?>">
                                            <button type="submit" class="button button-ghost button-small" data-loading-text="Sending..."><?= e($label) ?></button>
                                        </form>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Latest Callback Results</p>
            <h2>Recently finalized payments</h2>
        </div>
    </div>

    <?php if ($recentUpdates === []): ?>
        <div class="empty-state compact">
            <p>No finalized payments yet.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUpdates as $payment): ?>
                        <tr>
                            <td><?= e($payment['order_id']) ?></td>
                            <td><?= e($payment['customer_name']) ?></td>
                            <td><span class="status-badge" data-status="<?= e(status_slug($payment['status'])) ?>"><?= e($payment['status']) ?></span></td>
                            <td><?= e(format_datetime($payment['updated_at'])) ?></td>
                            <td><a class="button button-ghost" href="<?= e(route_path('payment_view.php?id=' . urlencode((string) $payment['id']))) ?>">Details</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
