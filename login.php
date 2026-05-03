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
<section class="auth-card">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Sandbox Access</p>
            <h2>Login</h2>
        </div>
        <p class="panel-note">Use the seeded demo account or your own registered account.</p>
    </div>

    <form method="post" action="<?= e(route_path('login.php')) ?>" class="stack-lg" data-loading-form>
        <?= csrf_input() ?>
        <label class="field">
            <span>Email address</span>
            <input type="email" name="email" value="<?= e($oldInput['email'] ?? '') ?>" required autocomplete="email">
        </label>

        <label class="field">
            <span>Password</span>
            <input type="password" name="password" required autocomplete="current-password">
        </label>

        <button type="submit" class="button button-primary button-block" data-loading-text="Signing in...">
            Sign in
        </button>
    </form>

    <div class="info-card">
        <strong>Demo login</strong>
        <p>Email: <code>admin@paymentsandbox.test</code></p>
        <p>Password: <code>Password123!</code></p>
    </div>

    <div class="auth-footer">
        <p>No account yet?</p>
        <a class="button button-secondary button-block" href="<?= e(route_path('register.php')) ?>">Create account</a>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
