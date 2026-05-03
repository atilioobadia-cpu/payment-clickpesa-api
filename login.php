<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_guest();

$oldInput = consume_old_input();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $email = strtolower(request_value($_POST, 'email'));
    $password = request_value($_POST, 'password');

    if ($email === '' || $password === '') {
        remember_old_input(['email' => $email]);
        flash('error', 'Email and password are required.');
        redirect('login.php');
    }

    $statement = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $statement->execute([':email' => $email]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        remember_old_input(['email' => $email]);
        flash('error', 'Invalid login details.');
        redirect('login.php');
    }

    login_user($user);
    flash('success', 'Welcome back. The sandbox workspace is ready.');
    redirect('dashboard.php');
}

$pageTitle = 'Login';
$pageHeading = 'Sign in';
$pageSubtitle = 'Access your simulated mobile money payment dashboard.';
$authLayout = true;

require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
    <div class="auth-brand-block">
        <span class="brand-mark">F</span>
        <div class="auth-brand-copy">
            <h1 class="auth-title">Login to Sandbox</h1>
        </div>
    </div>

    <section class="auth-card auth-card-compact">
        <form method="post" action="<?= e(route_path('login.php')) ?>" class="auth-form auth-form-compact" data-loading-form>
            <?= csrf_input() ?>
            <label class="auth-field">
                <span>Email address</span>
                <div class="auth-input-group">
                    <?= app_icon('mail', 'auth-input-icon') ?>
                    <input type="email" name="email" value="<?= e($oldInput['email'] ?? '') ?>" required autocomplete="email">
                </div>
            </label>

            <label class="auth-field">
                <span>Password</span>
                <div class="auth-input-group">
                    <?= app_icon('lock', 'auth-input-icon') ?>
                    <input id="login-password" type="password" name="password" required autocomplete="current-password">
                    <button type="button" class="auth-input-action" data-password-toggle data-password-target="login-password">Show</button>
                </div>
            </label>

            <button type="submit" class="button button-primary button-block" data-loading-text="Signing in...">
                <span>Login</span>
            </button>
        </form>

        <div class="auth-divider"><span>or</span></div>

        <a class="button button-secondary button-block auth-secondary-button" href="<?= e(route_path('register.php')) ?>">Create account</a>
    </section>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
