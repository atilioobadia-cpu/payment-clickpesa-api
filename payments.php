<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_login();

$user = current_user();
$search = request_value($_GET, 'search');
$statusFilter = strtoupper(request_value($_GET, 'status'));
$networkFilter = request_value($_GET, 'network');
$dateFilter = request_value($_GET, 'date');
$allowedStatuses = ['', 'PENDING', 'PROCESSING', 'PAID', 'FAILED', 'CANCELLED', 'TIMEOUT'];
$allowedNetworks = array_values(network_options());

$sql = 'SELECT * FROM payments WHERE created_by = :created_by';
$parameters = [':created_by' => $user['id']];

if ($search !== '') {
    $sql .= ' AND (order_id LIKE :search OR phone LIKE :search OR customer_name LIKE :search)';
    $parameters[':search'] = '%' . $search . '%';
}

if (in_array($statusFilter, $allowedStatuses, true) && $statusFilter !== '') {
    $sql .= ' AND status = :status';
    $parameters[':status'] = $statusFilter;
}

if (in_array($networkFilter, $allowedNetworks, true)) {
    $sql .= ' AND network = :network';
    $parameters[':network'] = $networkFilter;
}

if ($dateFilter !== '') {
    $sql .= ' AND DATE(created_at) = :created_date';
    $parameters[':created_date'] = $dateFilter;
}

$sql .= ' ORDER BY created_at DESC';
$statement = db()->prepare($sql);
$statement->execute($parameters);
$payments = $statement->fetchAll();

$pageTitle = 'All Payments';
$pageHeading = 'Transactions';
$pageSubtitle = 'Search, filter, and monitor ClickPesa and simulator payment records in one list view.';
$pageBreadcrumbs = ['Payment Sandbox', 'Transactions'];
$pageActions = [
    ['label' => 'List View', 'href' => route_path('payments.php'), 'variant' => 'button-secondary', 'icon' => 'list'],
    ['label' => 'Refresh', 'href' => route_path('payments.php?' . http_build_query($_GET)), 'variant' => 'button-secondary', 'icon' => 'refresh'],
    ['label' => 'New Payment', 'href' => route_path('payment_form.php'), 'variant' => 'button-primary', 'icon' => 'plus'],
];
$activePage = 'payments';

require_once __DIR__ . '/includes/header.php';
?>
<section class="content-layout">
    <aside class="filter-rail panel" id="payments-filters">
        <div class="panel-header compact">
            <div>
                <p class="eyebrow">Filter By</p>
                <h2>Refine results</h2>
            </div>
        </div>
        <form method="get" action="<?= e(route_path('payments.php')) ?>" class="filter-stack">
            <label class="field">
                <span>Search</span>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Order ID, phone, customer name">
            </label>

            <label class="field">
                <span>Status</span>
                <select name="status">
                    <option value="">All statuses</option>
                    <?php foreach (['PENDING', 'PROCESSING', 'PAID', 'FAILED', 'CANCELLED', 'TIMEOUT'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="field">
                <span>Network</span>
                <select name="network">
                    <option value="">All networks</option>
                    <?php foreach ($allowedNetworks as $network): ?>
                        <option value="<?= e($network) ?>" <?= $networkFilter === $network ? 'selected' : '' ?>><?= e($network) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="field">
                <span>Date</span>
                <input type="date" name="date" value="<?= e($dateFilter) ?>">
            </label>

            <div class="filter-actions">
                <button type="submit" class="button button-primary">Apply filters</button>
                <a class="button button-secondary" href="<?= e(route_path('payments.php')) ?>">Reset</a>
            </div>
        </form>
    </aside>

    <div class="list-panel panel">
        <div class="list-toolbar">
            <div class="list-toolbar-group">
                <button type="button" class="toolbar-chip is-active"><?= app_icon('list', 'chip-icon') ?>List View</button>
                <button type="button" class="toolbar-chip" data-filter-toggle aria-controls="payments-filters"><?= app_icon('filter', 'chip-icon') ?>Filter</button>
                <button type="button" class="toolbar-chip"><?= app_icon('refresh', 'chip-icon') ?>Last Updated</button>
            </div>
            <div class="list-toolbar-group">
                <span class="list-toolbar-meta"><?= e((string) count($payments)) ?> records</span>
            </div>
        </div>

        <?php if ($payments === []): ?>
            <div class="empty-state">
                <h3>No matching payments</h3>
                <p>Try adjusting your filters or create a new sandbox payment.</p>
                <a class="button button-primary" href="<?= e(route_path('payment_form.php')) ?>">Create payment</a>
            </div>
        <?php else: ?>
            <div class="table-wrap list-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" aria-label="Select all payments"></th>
                            <th>Customer Name</th>
                            <th>Order Reference</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Grand Total</th>
                            <th>Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><input type="checkbox" aria-label="Select payment"></td>
                                <td>
                                    <strong><?= e($payment['customer_name']) ?></strong>
                                    <div class="row-subtext"><?= e($payment['network']) ?></div>
                                </td>
                                <td><?= e($payment['order_id']) ?></td>
                                <td><?= e($payment['phone']) ?></td>
                                <td><span class="status-badge" data-status="<?= e(status_slug($payment['status'])) ?>"><?= e($payment['status']) ?></span></td>
                                <td><?= e(format_currency($payment['amount'], $payment['currency'])) ?></td>
                                <td><?= e(format_datetime($payment['updated_at'])) ?></td>
                                <td>
                                    <div class="inline-actions">
                                        <a class="button button-ghost button-small" href="<?= e(route_path('payment_status.php?order_id=' . urlencode($payment['order_id']))) ?>">Status</a>
                                        <a class="button button-secondary button-small" href="<?= e(route_path('payment_view.php?id=' . urlencode((string) $payment['id']))) ?>">Details</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
