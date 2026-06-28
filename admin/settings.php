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
                'GOOGLE_SITE_VERIFICATION' => normalize_text($_POST['GOOGLE_SITE_VERIFICATION'] ?? '', 220),
                'DEFAULT_SEO_TITLE' => normalize_text($_POST['DEFAULT_SEO_TITLE'] ?? '', 180),
                'DEFAULT_SEO_DESCRIPTION' => normalize_text($_POST['DEFAULT_SEO_DESCRIPTION'] ?? '', 320),
                'DEFAULT_SOCIAL_IMAGE' => normalize_text($_POST['DEFAULT_SOCIAL_IMAGE'] ?? '', 300),
                'GOOGLE_SERVICE_ACCOUNT_EMAIL' => normalize_text($_POST['GOOGLE_SERVICE_ACCOUNT_EMAIL'] ?? '', 220),
                'GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY_SET' => !empty($_POST['GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY']) || $settings['GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY_SET'],
                'GA4_PROPERTY_ID' => normalize_text($_POST['GA4_PROPERTY_ID'] ?? '', 80),
                'SEARCH_CONSOLE_SITE_URL' => normalize_text($_POST['SEARCH_CONSOLE_SITE_URL'] ?? '', 220),
                'GOOGLE_AI_MODEL' => normalize_text($_POST['GOOGLE_AI_MODEL'] ?? '', 80),
                'GOOGLE_AI_API_KEY_SET' => !empty($_POST['GOOGLE_AI_API_KEY']) || $settings['GOOGLE_AI_API_KEY_SET'],
                'AUTOMATION_TOKEN_SET' => !empty($_POST['AUTOMATION_TOKEN']) || $settings['AUTOMATION_TOKEN_SET'],
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
            <div class="admin-help-card compact">
                <h2>Settings guide</h2>
                <p>SMTP controls appointment emails. Google SEO controls Search Console verification, Analytics tracking, default snippets, and future admin reports. Leave secret fields blank to keep saved values.</p>
            </div>
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

            <div id="google-seo-settings" class="settings-section-title">
                <h2>Google SEO</h2>
                <p>Paste Google codes here. The public website outputs them automatically in the page head.</p>
            </div>
            <div class="settings-grid">
                <div>
                    <label>Search Console Verification Content</label>
                    <input type="text" name="GOOGLE_SITE_VERIFICATION" value="<?= e($settings['GOOGLE_SITE_VERIFICATION']) ?>" placeholder="content value from Google meta tag">
                    <p class="field-help">Paste only the content value, not the full meta tag.</p>
                </div>
                <div>
                    <label>Default Social Image</label>
                    <input type="text" name="DEFAULT_SOCIAL_IMAGE" value="<?= e($settings['DEFAULT_SOCIAL_IMAGE']) ?>" placeholder="assets/images/flexi-feet-logo.png">
                </div>
                <div>
                    <label>Default SEO Title</label>
                    <input type="text" name="DEFAULT_SEO_TITLE" value="<?= e($settings['DEFAULT_SEO_TITLE']) ?>">
                </div>
                <div>
                    <label>GA4 Property ID</label>
                    <input type="text" name="GA4_PROPERTY_ID" value="<?= e($settings['GA4_PROPERTY_ID']) ?>" placeholder="123456789">
                    <p class="field-help">Only needed when enabling admin analytics fetch.</p>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label>Default Meta Description</label>
                    <textarea name="DEFAULT_SEO_DESCRIPTION" rows="3"><?= e($settings['DEFAULT_SEO_DESCRIPTION']) ?></textarea>
                </div>
                <div>
                    <label>Search Console Site URL</label>
                    <input type="text" name="SEARCH_CONSOLE_SITE_URL" value="<?= e($settings['SEARCH_CONSOLE_SITE_URL']) ?>" placeholder="https://flexifeet.net">
                </div>
                <div>
                    <label>Service Account Email</label>
                    <input type="email" name="GOOGLE_SERVICE_ACCOUNT_EMAIL" value="<?= e($settings['GOOGLE_SERVICE_ACCOUNT_EMAIL']) ?>" placeholder="name@project.iam.gserviceaccount.com">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label>Service Account Private Key</label>
                    <textarea name="GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY" rows="5" placeholder="<?= $settings['GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY_SET'] ? 'Leave blank to keep current private key' : 'Paste private_key from Google service account JSON' ?>"></textarea>
                    <p class="field-help">This is optional and saved locally. No Composer package is required.</p>
                </div>
            </div>

            <div class="settings-section-title">
                <h2>AI and Automation</h2>
                <p>Optional tools for internal content drafting and scheduled tasks.</p>
            </div>
            <div class="settings-grid">
                <div>
                    <label>Google AI Studio Model</label>
                    <input type="text" name="GOOGLE_AI_MODEL" value="<?= e($settings['GOOGLE_AI_MODEL']) ?>" placeholder="gemma-4-31b-it">
                </div>
                <div>
                    <label>Google AI Studio API Key</label>
                    <input type="password" name="GOOGLE_AI_API_KEY" value="" placeholder="<?= $settings['GOOGLE_AI_API_KEY_SET'] ? 'Leave blank to keep current key' : 'Enter API key' ?>">
                </div>
                <div>
                    <label>Automation Token</label>
                    <input type="password" name="AUTOMATION_TOKEN" value="" placeholder="<?= $settings['AUTOMATION_TOKEN_SET'] ? 'Leave blank to keep current token' : 'Enter long random token' ?>">
                </div>
            </div>
            <button class="wp-button primary" type="submit">Save Settings</button>
            <p class="field-help">Settings are saved to <code>includes/config.local.php</code>, which is ignored by Git.</p>
        </form>
    </main>
</body>
</html>
