<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = $GLOBALS['app_config']['db'] ?? [];
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'] ?? '127.0.0.1',
        $config['port'] ?? '3306',
        $config['name'] ?? '',
        $config['charset'] ?? 'utf8mb4'
    );

    try {
        $pdo = new PDO(
            $dsn,
            $config['username'] ?? '',
            $config['password'] ?? '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $exception) {
        throw new RuntimeException(
            'Database connection failed. Confirm your MySQL credentials in config/config.php.',
            0,
            $exception
        );
    }

    return $pdo;
}
