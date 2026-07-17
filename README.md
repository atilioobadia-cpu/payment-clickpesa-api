# Payment Sandbox

Payment Sandbox Demo is a PHP 8 + MySQL learning project for testing a mobile money payment flow. It supports both the original local simulator and a real ClickPesa test-mode flow using token generation, preview USSD push, initiate USSD push, and payment status query endpoints.

## Features

- User registration, login, and logout with hashed passwords
- Dashboard with payment summary cards and recent transactions
- New payment form for M-Pesa, Airtel Money, Tigo Pesa / Mixx by Yass, and HaloPesa
- Sandbox payment processing flow that moves payments from `PENDING` to `PROCESSING`
- Gateway simulator that posts JSON callbacks to the webhook endpoint
- ClickPesa test-mode integration for token generation, preview, initiate, and status sync
- Callback validation with order and amount checks
- Searchable, filterable payments table
- Payment detail page with callback payload and timeline logs
- Settings page for sandbox merchant and callback configuration
- Responsive Frappe-inspired interface with light and dark theme toggle

## Requirements

- PHP 8.0 or newer
- MySQL or MariaDB
- Apache and PHP environment such as XAMPP
- PDO MySQL extension enabled
- cURL enabled for HTTP callback simulation

## Installation

1. Copy the project into your XAMPP web root so the app lives at:

```text
htdocs/payments/payment-sandbox-demo
```

2. Create the database and tables:

- Open phpMyAdmin or MySQL CLI.
- Import [`install.sql`](/C:/xampp/htdocs/payments/payment-sandbox-demo/install.sql).

3. Configure your database credentials in [`config/config.php`](/C:/xampp/htdocs/payments/payment-sandbox-demo/config/config.php).

4. Start Apache and MySQL in XAMPP.

5. Open the project in your browser:

[http://localhost/payments/payment-sandbox-demo/](http://localhost/payments/payment-sandbox-demo/)

## Default Login Credentials

- Name: `Demo Admin`
- Email: `admin@paymentsandbox.test`
- Password: `Password123!`

These credentials are seeded by [`install.sql`](/C:/xampp/htdocs/payments/payment-sandbox-demo/install.sql).

If you imported the database before the demo user was added, do one of the following:

1. Re-import [`install.sql`](/C:/xampp/htdocs/payments/payment-sandbox-demo/install.sql), or
2. Run the demo user `INSERT ... ON DUPLICATE KEY UPDATE` statement from the bottom of [`install.sql`](/C:/xampp/htdocs/payments/payment-sandbox-demo/install.sql)

## ClickPesa Test Mode

The project is preconfigured in [`config/config.php`](/C:/xampp/htdocs/payments/payment-sandbox-demo/config/config.php) for:

The settings page lets you switch between `ClickPesa Test` and `Simulation`. Use ClickPesa Test to send a real test-mode preview/initiate request, or switch back to Simulation to keep using the local gateway simulator.

## Test Flow

1. Register a new account or use the default login credentials above.
2. Log in to the dashboard with `admin@paymentsandbox.test` / `Password123!` if you are using the seeded demo account.
3. Open **New Payment**.
4. Enter customer name, phone number, network, amount, and description.
5. Submit the payment.
6. The app creates a payment record and moves it to `PROCESSING`.
7. If the app is in `Simulation` mode, open **Gateway Simulator**.
8. Click `Mark as PAID`, `Mark as FAILED`, `Mark as CANCELLED`, or `Mark as TIMEOUT`.
9. Return to the payment status page.
10. The status auto-refreshes every 5 seconds while processing and updates after the callback is received.

## Notes

- Amount validation in the app allows values from `1` TZS up to `3,000,000` TZS.
- In `ClickPesa Test` mode, the final minimum accepted amount or failure reason can still come from ClickPesa or the mobile money provider.
- In `Simulation` mode, no real payment push is sent to a phone.
