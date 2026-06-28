<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$csrf = csrf_token();
$message = '';
$error = '';
$settings = current_mail_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'Security token expired. Please refresh and try again.';
    } else {
        $result = save_mail_settings($_POST);
        if ($result['ok']) {
            $message = $result['message'];
            $settings = array_merge($settings, [
                'SMTP_HOST' => normalize_text($_POST['SMTP_HOST'] ?? '', 120),
                'SMTP_PORT' => normalize_text($_POST['SMTP_PORT'] ?? '', 10),
                'SMTP_ENCRYPTION' => normalize_text($_POST['SMTP_ENCRYPTION'] ?? '', 20),
                'SMTP_USERNAME' => normalize_text($_POST['SMTP_USERNAME'] ?? '', 180),
                'SMTP_FROM_EMAIL' => normalize_text($_POST['SMTP_FROM_EMAIL'] ?? '', 180),
                'SMTP_FROM_NAME' => normalize_text($_POST['SMTP_FROM_NAME'] ?? '', 180),
                'BOOKING_OWNER_EMAIL' => normalize_text($_POST['BOOKING_OWNER_EMAIL'] ?? '', 180),
                'GA_MEASUREMENT_ID' => normalize_text($_POST['GA_MEASUREMENT_ID'] ?? '', 40),
                'SMTP_PASSWORD_SET' => true,
            ]);
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Flexi Feet Admin</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <link rel="stylesheet" href="flexi-admin.css">
</head>
<body class="wp-admin">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="wp-main">
        <div class="wp-topbar">
            <div>
                <h1>Settings</h1>
                <p>Update SMTP and booking notification mail settings.</p>
            </div>
        </div>

        <?php if ($message): ?><div class="wp-notice"><?= e($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="wp-notice error"><?= e($error) ?></div><?php endif; ?>

        <form method="POST" class="settings-form wp-panel">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <div class="settings-grid">
                <div>
                    <label>SMTP Host</label>
                    <input type="text" name="SMTP_HOST" value="<?= e($settings['SMTP_HOST']) ?>" required>
                </div>
                <div>
                    <label>SMTP Port</label>
                    <input type="number" name="SMTP_PORT" value="<?= e($settings['SMTP_PORT']) ?>" min="1" max="65535" required>
                </div>
                <div>
                    <label>Encryption</label>
                    <select name="SMTP_ENCRYPTION" required>
                        <?php foreach (['ssl' => 'SSL', 'tls' => 'TLS', 'starttls' => 'STARTTLS'] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= strtolower($settings['SMTP_ENCRYPTION']) === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>SMTP Username</label>
                    <input type="email" name="SMTP_USERNAME" value="<?= e($settings['SMTP_USERNAME']) ?>" required>
                </div>
                <div>
                    <label>SMTP Password</label>
                    <input type="password" name="SMTP_PASSWORD" value="" placeholder="<?= $settings['SMTP_PASSWORD_SET'] ? 'Leave blank to keep current password' : 'Enter mailbox password' ?>">
                </div>
                <div>
                    <label>From Email</label>
                    <input type="email" name="SMTP_FROM_EMAIL" value="<?= e($settings['SMTP_FROM_EMAIL']) ?>" required>
                </div>
                <div>
                    <label>From Name</label>
                    <input type="text" name="SMTP_FROM_NAME" value="<?= e($settings['SMTP_FROM_NAME']) ?>" required>
                </div>
                <div>
                    <label>Booking Owner Email</label>
                    <input type="email" name="BOOKING_OWNER_EMAIL" value="<?= e($settings['BOOKING_OWNER_EMAIL']) ?>" required>
                </div>
                <div>
                    <label>Google Analytics Measurement ID</label>
                    <input type="text" name="GA_MEASUREMENT_ID" value="<?= e($settings['GA_MEASUREMENT_ID']) ?>" placeholder="G-XXXXXXXXXX">
                </div>
            </div>
            <button class="wp-button primary" type="submit">Save Settings</button>
            <p class="field-help">Settings are saved to <code>includes/config.local.php</code>, which is ignored by Git.</p>
        </form>
    </main>
</body>
</html>
