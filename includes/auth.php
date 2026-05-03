<?php
declare(strict_types=1);

function is_logged_in(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function current_user(): ?array
{
    return is_logged_in() ? $_SESSION['user'] : null;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function require_login(): void
{
    if (is_logged_in()) {
        return;
    }

    flash('error', 'Please log in to continue.');
    redirect('login.php');
}

function require_guest(): void
{
    if (!is_logged_in()) {
        return;
    }

    redirect('dashboard.php');
}
