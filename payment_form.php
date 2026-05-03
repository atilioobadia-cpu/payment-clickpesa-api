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
    ['label' => 'Transactions', 'href' => route_path('payments.php'), 'variant' => 'button-secondary', 'icon' => 'transactions'],
    ['label' => 'Simulator', 'href' => route_path('simulator.php'), 'variant' => 'button-secondary', 'icon' => 'simulator'],
];
$activePage = 'payment_form';

require_once __DIR__ . '/includes/header.php';
?>
<section class="document-layout">
    <article class="panel document-sheet">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Payment Request</p>
                <h2>Mobile money payment document</h2>
            </div>
            <p class="panel-note">Fill the customer and transaction details below. The backend flow, API integration, and validation remain unchanged.</p>
        </div>

        <form method="post" action="<?= e(route_path('pay.php')) ?>" class="stack-lg" data-loading-form>
            <?= csrf_input() ?>

            <section class="form-section">
                <div class="form-section-head">
                    <div>
                        <h3>Customer</h3>
                        <p>Capture the payer details exactly as they will be sent to the gateway.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <label class="field">
                        <span>Customer name</span>
                        <input type="text" name="customer_name" value="<?= e($oldInput['customer_name'] ?? '') ?>" required>
                    </label>

                    <label class="field">
                        <span>Phone number</span>
                        <input type="text" name="phone" value="<?= e($oldInput['phone'] ?? '') ?>" placeholder="+255712345678" required>
                    </label>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-head">
                    <div>
                        <h3>Payment details</h3>
                        <p>Choose the network and the amount to push to the customer phone.</p>
                    </div>
                </div>
                <div class="form-grid">
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
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-head">
                    <div>
                        <h3>Reference</h3>
                        <p>Add a note so the transaction can be recognized quickly from the list view later.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <label class="field field-full">
                        <span>Description</span>
                        <textarea name="description" rows="4" placeholder="Utility payment demo, order testing, classroom simulation..."><?= e($oldInput['description'] ?? '') ?></textarea>
                    </label>
                </div>
            </section>

            <div class="form-actions field-full">
                <a class="button button-secondary" href="<?= e(route_path('dashboard.php')) ?>">Cancel</a>
                <button type="submit" class="button button-primary" data-loading-text="Submitting payment..."><?= app_icon('plus', 'button-icon') ?><span>Submit payment</span></button>
            </div>
        </form>
    </article>

    <aside class="stack-lg">
        <article class="panel side-note-card">
            <div class="panel-header compact">
                <div>
                    <p class="eyebrow">Current Mode</p>
                    <h2><?= e(get_setting('api_mode', 'ClickPesa Test') ?? 'ClickPesa Test') ?></h2>
                </div>
            </div>
            <div class="workspace-meta-list">
                <div><span>Amount Range</span><strong>1 TZS - 3,000,000 TZS</strong></div>
                <div><span>Callback</span><strong><?= e(get_setting('sandbox_mode_enabled', '1') === '1' ? 'Enabled' : 'Disabled') ?></strong></div>
            </div>
        </article>

        <article class="panel side-note-card">
            <div class="panel-header compact">
                <div>
                    <p class="eyebrow">Flow</p>
                    <h2>What happens next</h2>
                </div>
            </div>
            <div class="workspace-shortcuts compact">
                <div class="workspace-shortcut is-static">
                    <div class="workspace-shortcut-copy">
                        <strong>1. Preview</strong>
                        <span>ClickPesa Test mode validates the number, amount, and available method.</span>
                    </div>
                </div>
                <div class="workspace-shortcut is-static">
                    <div class="workspace-shortcut-copy">
                        <strong>2. Initiate</strong>
                        <span>The app sends the USSD push request or keeps the flow local in simulator mode.</span>
                    </div>
                </div>
                <div class="workspace-shortcut is-static">
                    <div class="workspace-shortcut-copy">
                        <strong>3. Track</strong>
                        <span>Use the status page, transactions list, or simulator to follow the payment lifecycle.</span>
                    </div>
                </div>
            </div>
        </article>
    </aside>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
