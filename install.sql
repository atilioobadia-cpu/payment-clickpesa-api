CREATE DATABASE IF NOT EXISTS `payment_sandbox_demo` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `payment_sandbox_demo`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'tester',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` VARCHAR(60) NOT NULL,
    `customer_name` VARCHAR(120) NOT NULL,
    `phone` VARCHAR(25) NOT NULL,
    `network` VARCHAR(80) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'TZS',
    `description` TEXT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'PENDING',
    `gateway_reference` VARCHAR(80) NULL,
    `gateway_response` LONGTEXT NULL,
    `callback_received_at` DATETIME NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_payments_order_id` (`order_id`),
    UNIQUE KEY `uniq_payments_gateway_reference` (`gateway_reference`),
    KEY `idx_payments_status` (`status`),
    KEY `idx_payments_created_by` (`created_by`),
    KEY `idx_payments_created_at` (`created_at`),
    CONSTRAINT `fk_payments_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(120) NOT NULL,
    `setting_value` TEXT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `payment_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(80) NOT NULL,
    `message` TEXT NOT NULL,
    `raw_data` LONGTEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_payment_logs_payment_id` (`payment_id`),
    KEY `idx_payment_logs_created_at` (`created_at`),
    CONSTRAINT `fk_payment_logs_payment_id` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('merchant_name', 'Payment Sandbox Demo Merchant'),
    ('callback_url', 'http://localhost/payments/payment-sandbox-demo/payment_callback.php'),
    ('sandbox_mode_enabled', '1'),
    ('api_mode', 'ClickPesa Test')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

INSERT INTO `users` (`name`, `email`, `password`, `role`, `created_at`) VALUES
    ('Demo Admin', 'admin@paymentsandbox.test', '$2y$10$sFoon7DKZPW7RZ2OYtPPMeZzup3D5cE.0EeQBTIm.xU5PKgV0KhwS', 'admin', NOW())
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `password` = VALUES(`password`),
    `role` = VALUES(`role`);
