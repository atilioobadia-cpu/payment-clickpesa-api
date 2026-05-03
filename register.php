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

    $name = request_value($_POST, 'name');
    $email = strtolower(request_value($_POST, 'email'));
    $password = request_value($_POST, 'password');
    $confirmPassword = request_value($_POST, 'confirm_password');
    $errors = [];

    if ($name === '') {
        $errors[] = 'Full name is required.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if (mb_strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    remember_old_input([
        'name' => $name,
        'email' => $email,
    ]);

    if ($errors !== []) {
        flash('error', implode(' ', $errors));
        redirect('register.php');
    }

    $duplicateCheck = db()->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
    $duplicateCheck->execute([':email' => $email]);

    if ((int) $duplicateCheck->fetchColumn() > 0) {
        flash('error', 'That email address is already in use.');
        redirect('register.php');
    }

    $statement = db()->prepare(
        'INSERT INTO users (name, email, password, role, created_at)
         VALUES (:name, :email, :password, :role, NOW())'
    );
    $statement->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':role' => 'tester',
    ]);

    $userId = (int) db()->lastInsertId();
    login_user([
        'id' => $userId,
        'name' => $name,
        'email' => $email,
        'role' => 'tester',
    ]);

    unset($_SESSION['old_input']);
    flash('success', 'Account created successfully. You are now signed in.');
    redirect('dashboard.php');
}

$pageTitle = 'Register';
$pageHeading = 'Create account';
$pageSubtitle = 'Start testing the simulated gateway flow in a few steps.';
$authLayout = true;

require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
    <div class="auth-brand-block">
        <span class="brand-mark">F</span>
        <div class="auth-brand-copy">
            <h1 class="auth-title">Create account</h1>
        </div>
    </div>

    <section class="auth-card auth-card-compact">
        <form method="post" action="<?= e(route_path('register.php')) ?>" class="auth-form auth-form-compact" data-loading-form>
            <?= csrf_input() ?>
            <label class="auth-field">
                <span>Full name</span>
                <div class="auth-input-group">
                    <?= app_icon('users', 'auth-input-icon') ?>
                    <input type="text" name="name" value="<?= e($oldInput['name'] ?? '') ?>" required autocomplete="name">
                </div>
            </label>

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
                    <input id="register-password" type="password" name="password" required autocomplete="new-password">
                    <button type="button" class="auth-input-action" data-password-toggle data-password-target="register-password">Show</button>
                </div>
            </label>

            <label class="auth-field">
                <span>Confirm password</span>
                <div class="auth-input-group">
                    <?= app_icon('lock', 'auth-input-icon') ?>
                    <input id="register-confirm-password" type="password" name="confirm_password" required autocomplete="new-password">
                    <button type="button" class="auth-input-action" data-password-toggle data-password-target="register-confirm-password">Show</button>
                </div>
            </label>

            <button type="submit" class="button button-primary button-block" data-loading-text="Creating account...">
                <span>Create account</span>
            </button>
        </form>

        <div class="auth-divider"><span>or</span></div>

        <a class="button button-secondary button-block auth-secondary-button" href="<?= e(route_path('login.php')) ?>">Go to login</a>
    </section>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
