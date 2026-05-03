<?php
declare(strict_types=1);

$activePage = $activePage ?? '';
$menuItems = [
    'dashboard' => ['label' => 'Workspace', 'href' => route_path('dashboard.php'), 'icon' => 'dashboard'],
    'payment_form' => ['label' => 'New Payment', 'href' => route_path('payment_form.php'), 'icon' => 'payment'],
    'payments' => ['label' => 'All Payments', 'href' => route_path('payments.php'), 'icon' => 'list'],
    'simulator' => ['label' => 'Gateway Simulator', 'href' => route_path('simulator.php'), 'icon' => 'simulator'],
    'settings' => ['label' => 'Settings', 'href' => route_path('settings.php'), 'icon' => 'settings'],
];
?>
<aside class="sidebar no-print" id="app-sidebar">
    <div class="sidebar-section">
        <p class="sidebar-caption">Public</p>
        <div class="sidebar-switcher">
            <div class="sidebar-switcher-icon">F</div>
            <div class="sidebar-switcher-body">
                <strong><?= e(app_config('app.name')) ?></strong>
                <small>Sandbox workspace</small>
            </div>
            <?= app_icon('chevron-down', 'icon sidebar-switcher-caret') ?>
        </div>
    </div>

    <nav class="sidebar-nav sidebar-section">
        <p class="sidebar-caption">Modules</p>
        <?php foreach ($menuItems as $key => $item): ?>
            <a class="sidebar-link <?= $activePage === $key ? 'is-active' : '' ?>" href="<?= e($item['href']) ?>" data-sidebar-link>
                <?= app_icon((string) $item['icon'], 'sidebar-link-icon') ?>
                <span><?= e($item['label']) ?></span>
                <?php if ($activePage === $key): ?>
                    <span class="sidebar-link-indicator"></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer sidebar-section">
        <?php if (current_user() !== null): ?>
            <div class="sidebar-user">
                <div class="sidebar-user-avatar"><?= e(strtoupper(substr((string) current_user()['name'], 0, 1))) ?></div>
                <div class="sidebar-user-meta">
                    <strong><?= e(current_user()['name']) ?></strong>
                    <small><?= e(ucfirst((string) current_user()['role'])) ?></small>
                </div>
            </div>
        <?php endif; ?>
        <div class="sidebar-utility-card">
            <span>Raven</span>
            <?= app_icon('external', 'icon') ?>
        </div>
        <a class="sidebar-link sidebar-link-utility" href="<?= e(route_path('logout.php')) ?>" data-sidebar-link>
            <?= app_icon('logout', 'sidebar-link-icon') ?>
            <span>Sign out</span>
        </a>
    </div>
</aside>
