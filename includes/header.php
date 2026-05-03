<?php
declare(strict_types=1);

$authLayout = $authLayout ?? false;
$pageTitle = $pageTitle ?? app_config('app.name');
$pageHeading = $pageHeading ?? $pageTitle;
$pageSubtitle = $pageSubtitle ?? null;
$pageBreadcrumbs = $pageBreadcrumbs ?? ['Home', $pageHeading];
$pageActions = $pageActions ?? [];
$activePage = $activePage ?? '';
$bodyAttributes = $bodyAttributes ?? [];
$bodyClass = $authLayout ? 'auth-layout' : 'app-layout';
$flashes = consume_flashes();
$user = current_user();
$bodyAttributeMarkup = '';

foreach ($bodyAttributes as $attribute => $value) {
    $bodyAttributeMarkup .= ' ' . $attribute . '="' . e((string) $value) . '"';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(app_config('app.name')) ?></title>
    <script>
        (function () {
            try {
                if (localStorage.getItem('paymentSandboxTheme') === 'dark') {
                    document.documentElement.classList.add('theme-dark-root');
                }
            } catch (error) {
                console.warn(error);
            }
        }());
    </script>
    <link rel="stylesheet" href="<?= e(route_path('assets/css/style.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>"<?= $bodyAttributeMarkup ?>>
<?php if ($authLayout): ?>
    <div class="auth-shell">
        <header class="auth-topbar global-topbar no-print">
            <div class="global-topbar-left">
                <a class="global-brand brand-inline" href="<?= e(route_path('index.php')) ?>">
                    <span class="brand-mark">F</span>
                    <span><?= e(app_config('app.name')) ?></span>
                </a>
            </div>
            <div class="global-topbar-right">
                <button class="icon-button theme-toggle" type="button" data-theme-toggle aria-label="Toggle theme">
                    <?= app_icon('moon', 'icon') ?>
                </button>
            </div>
        </header>

        <?php if ($flashes !== []): ?>
            <div class="toast-stack no-print" data-toast-stack>
                <?php foreach ($flashes as $flash): ?>
                    <div class="toast" data-toast data-tone="<?= e((string) $flash['type']) ?>">
                        <p><?= e((string) $flash['message']) ?></p>
                        <button type="button" class="toast-close" data-toast-close>&times;</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <main class="auth-main">
<?php else: ?>
    <div class="mobile-overlay no-print" data-mobile-close></div>
    <div class="app-shell">
        <div class="app-frame">
            <header class="global-topbar no-print">
                <div class="global-topbar-left">
                    <a class="global-brand" href="<?= e(route_path('dashboard.php')) ?>">
                        <span class="brand-mark">F</span>
                    </a>
                    <nav class="global-breadcrumbs desktop-only" aria-label="Global breadcrumb">
                        <span>Payment Sandbox Demo</span>
                    </nav>
                </div>
                <button class="global-search" type="button" aria-label="Search commands">
                    <?= app_icon('search', 'icon') ?>
                    <span>Search or type a command</span>
                    <kbd>Ctrl K</kbd>
                </button>
                <div class="global-topbar-right">
                    <button class="icon-button theme-toggle" type="button" data-theme-toggle aria-label="Toggle theme">
                        <?= app_icon('moon', 'icon') ?>
                    </button>
                    <button class="icon-button desktop-only" type="button" aria-label="Notifications">
                        <?= app_icon('bell', 'icon') ?>
                    </button>
                    <button class="topbar-link desktop-only" type="button" aria-label="Help">
                        <span>Help</span>
                        <?= app_icon('chevron-down', 'topbar-link-icon') ?>
                    </button>
                    <div class="user-avatar" aria-label="<?= e($user['name'] ?? 'User') ?>">
                        <?= e(strtoupper(substr((string) ($user['name'] ?? 'A'), 0, 1))) ?>
                    </div>
                </div>
            </header>

            <?php include __DIR__ . '/sidebar.php'; ?>
            <div class="main-shell">
                <section class="page-head no-print">
                    <div class="page-head-main">
                        <button type="button" class="icon-button page-menu-button" data-mobile-toggle aria-label="Open menu">
                            <?= app_icon('menu', 'icon') ?>
                        </button>
                        <div class="page-title-group">
                            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                                <?php foreach ($pageBreadcrumbs as $index => $breadcrumb): ?>
                                    <span class="breadcrumb-item<?= $index === array_key_last($pageBreadcrumbs) ? ' is-current' : '' ?>">
                                        <?php if ($index > 0): ?>
                                            <?= app_icon('chevron-right', 'breadcrumb-separator') ?>
                                        <?php endif; ?>
                                        <span><?= e((string) $breadcrumb) ?></span>
                                    </span>
                                <?php endforeach; ?>
                            </nav>
                            <h1><?= e($pageHeading) ?></h1>
                            <?php if ($pageSubtitle): ?>
                                <p class="topbar-subtitle"><?= e((string) $pageSubtitle) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($pageActions !== []): ?>
                        <div class="page-actions">
                            <?php foreach ($pageActions as $action): ?>
                                <?php
                                $variantClass = $action['variant'] ?? 'button-secondary';
                                $iconName = $action['icon'] ?? null;
                                $extraClass = trim((string) ($action['class'] ?? ''));
                                $className = trim('button ' . $variantClass . ' ' . $extraClass);
                                ?>
                                <?php if (!empty($action['href'])): ?>
                                    <a class="<?= e($className) ?>" href="<?= e((string) $action['href']) ?>">
                                        <?php if ($iconName): ?><?= app_icon((string) $iconName, 'button-icon') ?><?php endif; ?>
                                        <span><?= e((string) $action['label']) ?></span>
                                    </a>
                                <?php else: ?>
                                    <button class="<?= e($className) ?>" type="button">
                                        <?php if ($iconName): ?><?= app_icon((string) $iconName, 'button-icon') ?><?php endif; ?>
                                        <span><?= e((string) $action['label']) ?></span>
                                    </button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

            <?php if ($flashes !== []): ?>
                <div class="toast-stack no-print" data-toast-stack>
                    <?php foreach ($flashes as $flash): ?>
                        <div class="toast" data-toast data-tone="<?= e((string) $flash['type']) ?>">
                            <p><?= e((string) $flash['message']) ?></p>
                            <button type="button" class="toast-close" data-toast-close>&times;</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <main class="page-shell">
<?php endif; ?>
