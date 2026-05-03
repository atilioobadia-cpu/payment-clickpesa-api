<?php
declare(strict_types=1);

$activePage = $activePage ?? '';
$menuGroups = [
    [
        'label' => 'Dashboard',
        'items' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => route_path('dashboard.php'), 'icon' => 'dashboard'],
        ],
    ],
    [
        'label' => 'Payments',
        'items' => [
            ['key' => 'payment_form', 'label' => 'New Payment', 'href' => route_path('payment_form.php'), 'icon' => 'payment'],
            ['key' => 'payments', 'label' => 'Transactions', 'href' => route_path('payments.php'), 'icon' => 'transactions'],
        ],
    ],
    [
        'label' => 'Testing',
        'items' => [
            ['key' => 'simulator', 'label' => 'Simulator', 'href' => route_path('simulator.php'), 'icon' => 'simulator'],
            ['key' => 'settings', 'label' => 'Settings', 'href' => route_path('settings.php'), 'icon' => 'settings'],
        ],
    ],
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
        <?php foreach ($menuGroups as $group): ?>
            <div class="sidebar-group">
                <p class="sidebar-caption"><?= e($group['label']) ?></p>
                <div class="sidebar-group-links">
                    <?php foreach ($group['items'] as $item): ?>
                        <?php $isActive = $activePage === $item['key']; ?>
                        <a class="sidebar-link <?= $isActive ? 'is-active' : '' ?>" href="<?= e($item['href']) ?>" data-sidebar-link>
                            <?= app_icon((string) $item['icon'], 'sidebar-link-icon') ?>
                            <span><?= e($item['label']) ?></span>
                            <?php if ($isActive): ?>
                                <span class="sidebar-link-indicator"></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
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
            <span>Reports live inside Transactions</span>
            <?= app_icon('external', 'icon') ?>
        </div>
        <p class="sidebar-caption">Auth</p>
        <a class="sidebar-link sidebar-link-utility" href="<?= e(route_path('logout.php')) ?>" data-sidebar-link>
            <?= app_icon('logout', 'sidebar-link-icon') ?>
            <span>Sign out</span>
        </a>
    </div>
</aside>
