<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$csrf = csrf_token();
$message = '';
$error = '';
$settings = current_mail_settings();
$seoReport = google_seo_report(($_GET['refresh_seo'] ?? '') === '1');
$pagespeedReport = google_pagespeed_report(($_GET['refresh_pagespeed'] ?? '') === '1');
$googleChecks = google_connection_checks($seoReport, $pagespeedReport);
$posts = read_blog_posts(false);
$publishedPosts = array_values(array_filter($posts, fn($post) => ($post['status'] ?? '') === 'Published'));
$averageSeo = !empty($posts)
    ? (int) round(array_sum(array_map(fn($post) => seo_score_post($post)['percent'], $posts)) / count($posts))
    : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'Security token expired. Please refresh and try again.';
    } elseif (($_POST['action'] ?? '') === 'smtp_test') {
        $sent = send_smtp_mail(
            BOOKING_OWNER_EMAIL,
            'Flexi Feet SMTP test',
            '<div style="font-family:Arial,sans-serif;color:#1d1d1f;line-height:1.5;"><h2 style="color:#1e1b5d;">SMTP test successful</h2><p>This confirms Flexi Feet can send outbound email from the current server settings.</p><p>Sent at ' . e(date('Y-m-d H:i:s')) . '</p></div>'
        );
        if ($sent) {
            $message = 'SMTP test email sent to ' . BOOKING_OWNER_EMAIL . '.';
        } else {
            $error = smtp_configured()
                ? 'SMTP test failed. Check host, port, encryption, username, password, and whether the mailbox allows SMTP login.'
                : 'SMTP is not fully configured yet.';
        }
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
                'GOOGLE_ADSENSE_CLIENT_ID' => normalize_text($_POST['GOOGLE_ADSENSE_CLIENT_ID'] ?? '', 80),
                'GOOGLE_SITE_VERIFICATION' => normalize_text($_POST['GOOGLE_SITE_VERIFICATION'] ?? '', 220),
                'DEFAULT_SEO_TITLE' => normalize_text($_POST['DEFAULT_SEO_TITLE'] ?? '', 180),
                'DEFAULT_SEO_DESCRIPTION' => normalize_text($_POST['DEFAULT_SEO_DESCRIPTION'] ?? '', 320),
                'DEFAULT_SOCIAL_IMAGE' => normalize_text($_POST['DEFAULT_SOCIAL_IMAGE'] ?? '', 300),
                'GOOGLE_SERVICE_ACCOUNT_EMAIL' => normalize_text($_POST['GOOGLE_SERVICE_ACCOUNT_EMAIL'] ?? '', 220),
                'GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY_SET' => !empty($_POST['GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY']) || $settings['GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY_SET'],
                'GA4_PROPERTY_ID' => normalize_text($_POST['GA4_PROPERTY_ID'] ?? '', 80),
                'SEARCH_CONSOLE_SITE_URL' => normalize_text($_POST['SEARCH_CONSOLE_SITE_URL'] ?? '', 220),
                'GOOGLE_PAGESPEED_API_KEY_SET' => !empty($_POST['GOOGLE_PAGESPEED_API_KEY']) || $settings['GOOGLE_PAGESPEED_API_KEY_SET'],
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
                <p>Update mail delivery, Google SEO connection, AI, and automation keys.</p>
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
                    <p class="field-help">Kept for records/settings. Appointment owner alerts should come from mailbox forwarding, not a separate website email.</p>
                </div>
                <div>
                    <label>Google Analytics Measurement ID</label>
                    <input type="text" name="GA_MEASUREMENT_ID" value="<?= e($settings['GA_MEASUREMENT_ID']) ?>" placeholder="G-XXXXXXXXXX">
                </div>
                <div>
                    <label>Google AdSense Client ID</label>
                    <input type="text" name="GOOGLE_ADSENSE_CLIENT_ID" value="<?= e($settings['GOOGLE_ADSENSE_CLIENT_ID']) ?>" placeholder="ca-pub-XXXXXXXXXXXXXXXX">
                </div>
            </div>

            <div id="google-seo-settings" class="settings-section-title">
                <h2>Google SEO</h2>
                <p>Paste Google codes here. The public website outputs them automatically in the page head.</p>
            </div>
            <div class="seo-dashboard-grid compact">
                <div class="seo-stat-card soft">
                    <span>Published posts</span>
                    <strong><?= count($publishedPosts) ?></strong>
                    <p><?= count(array_filter($publishedPosts, fn($post) => !post_noindex($post))) ?> indexable posts.</p>
                </div>
                <div class="seo-stat-card soft">
                    <span>SEO readiness</span>
                    <strong><?= $averageSeo ?>%</strong>
                    <p>Average blog score from post titles, snippets, images, and indexability.</p>
                </div>
                <div class="seo-stat-card soft">
                    <span>Google report</span>
                    <strong><?= $seoReport['ok'] ? 'Ready' : 'Setup' ?></strong>
                    <p><?= $seoReport['ok'] ? 'Analytics fetch is configured.' : e($seoReport['message']) ?></p>
                </div>
            </div>
            <div class="seo-report-panel">
                <div class="section-heading-row">
                    <div>
                        <h2>Google Site Kit Style Status</h2>
                        <p>Connection checks inspired by Google Site Kit: tags, verification, private reports, sitemap, robots, and speed.</p>
                    </div>
                    <a class="wp-button" href="settings.php?refresh_seo=1&refresh_pagespeed=1#google-seo-settings">Refresh All</a>
                </div>
                <div class="google-check-grid">
                    <?php foreach ($googleChecks as $check): ?>
                        <div class="google-check-card <?= $check['ok'] ? 'ok' : 'todo' ?>">
                            <strong><?= $check['ok'] ? 'Connected' : 'Needed' ?></strong>
                            <span><?= e($check['label']) ?></span>
                            <p><?= e($check['detail']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="seo-report-panel">
                <div class="section-heading-row">
                    <div>
                        <h2>Google Report</h2>
                        <p>Fetches GA4 and Search Console with service account authentication. No Composer package required.</p>
                    </div>
                    <a class="wp-button" href="settings.php?refresh_seo=1#google-seo-settings">Refresh Report</a>
                </div>
                <?php if ($seoReport['ok']): ?>
                    <div class="seo-two-col">
                        <div>
                            <h3>Analytics</h3>
                            <table class="wp-table compact-table">
                                <tbody>
                                    <tr><td>Active users</td><td><?= e((string) ($seoReport['ga4']['metrics']['activeUsers'] ?? 0)) ?></td></tr>
                                    <tr><td>Sessions</td><td><?= e((string) ($seoReport['ga4']['metrics']['sessions'] ?? 0)) ?></td></tr>
                                    <tr><td>Views</td><td><?= e((string) ($seoReport['ga4']['metrics']['views'] ?? 0)) ?></td></tr>
                                </tbody>
                            </table>
                            <?php if (!$seoReport['ga4']['ok']): ?><p class="field-help"><?= e($seoReport['ga4']['message']) ?></p><?php endif; ?>
                        </div>
                        <div>
                            <h3>Search Console</h3>
                            <table class="wp-table compact-table">
                                <tbody>
                                    <tr><td>Clicks</td><td><?= e((string) ($seoReport['search_console']['metrics']['clicks'] ?? 0)) ?></td></tr>
                                    <tr><td>Impressions</td><td><?= e((string) ($seoReport['search_console']['metrics']['impressions'] ?? 0)) ?></td></tr>
                                    <tr><td>CTR</td><td><?= e((string) ($seoReport['search_console']['metrics']['ctr'] ?? 0)) ?>%</td></tr>
                                </tbody>
                            </table>
                            <?php if (!$seoReport['search_console']['ok']): ?><p class="field-help"><?= e($seoReport['search_console']['message']) ?></p><?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="field-help"><?= e($seoReport['message']) ?></p>
                <?php endif; ?>
            </div>
            <div class="seo-report-panel">
                <div class="section-heading-row">
                    <div>
                        <h2>PageSpeed Insights</h2>
                        <p>Performance, SEO, accessibility, and best-practices scores for <?= e(SITE_URL) ?>.</p>
                    </div>
                    <a class="wp-button" href="settings.php?refresh_pagespeed=1#google-seo-settings">Refresh Speed</a>
                </div>
                <?php if ($pagespeedReport['ok']): ?>
                    <div class="pagespeed-grid">
                        <?php foreach (['mobile' => 'Mobile', 'desktop' => 'Desktop'] as $strategy => $label): ?>
                            <?php $speed = $pagespeedReport['strategies'][$strategy] ?? ['ok' => false]; ?>
                            <div class="pagespeed-card">
                                <h3><?= e($label) ?></h3>
                                <?php if ($speed['ok'] ?? false): ?>
                                    <?php
                                    $perf = $speed['performance'] ?? null;
                                    $perfClass = $perf !== null ? ($perf >= 90 ? 'good' : ($perf >= 50 ? 'warn' : 'bad')) : '';
                                    $seoVal = $speed['seo'] ?? null;
                                    $seoClass = $seoVal !== null ? ($seoVal >= 90 ? 'score-good' : ($seoVal >= 50 ? 'score-warn' : 'score-bad')) : '';
                                    $acc = $speed['accessibility'] ?? null;
                                    $accClass = $acc !== null ? ($acc >= 90 ? 'score-good' : ($acc >= 50 ? 'score-warn' : 'score-bad')) : '';
                                    $bp = $speed['best_practices'] ?? null;
                                    $bpClass = $bp !== null ? ($bp >= 90 ? 'score-good' : ($bp >= 50 ? 'score-warn' : 'score-bad')) : '';
                                    ?>
                                    <div class="pagespeed-score <?= $perfClass ?>"><?= e((string) ($perf ?? '-')) ?></div>
                                    <dl>
                                        <div><dt>SEO</dt><dd class="<?= $seoClass ?>"><?= e((string) ($seoVal ?? '-')) ?></dd></div>
                                        <div><dt>Accessibility</dt><dd class="<?= $accClass ?>"><?= e((string) ($acc ?? '-')) ?></dd></div>
                                        <div><dt>Best Practices</dt><dd class="<?= $bpClass ?>"><?= e((string) ($bp ?? '-')) ?></dd></div>
                                    </dl>
                                <?php else: ?>
                                    <p class="field-help"><?= e($speed['message'] ?? 'No PageSpeed data.') ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="field-help"><?= e($pagespeedReport['message']) ?></p>
                <?php endif; ?>
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
                <div style="grid-column: 1 / -1;">
                    <label>PageSpeed Insights API Key</label>
                    <input type="password" name="GOOGLE_PAGESPEED_API_KEY" value="" placeholder="<?= $settings['GOOGLE_PAGESPEED_API_KEY_SET'] ? 'Leave blank to keep current key' : 'Optional; PageSpeed works without a key until quota is limited' ?>">
                    <p class="field-help">Optional. Add a Google API key only if anonymous PageSpeed quota becomes limited.</p>
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
        <form method="POST" class="wp-panel smtp-test-panel">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="smtp_test">
            <div>
                <h2>Outbound Mail Test</h2>
                <p>Send a real test message from the support mailbox to <?= e(BOOKING_OWNER_EMAIL) ?> using the currently saved SMTP settings.</p>
            </div>
            <button class="wp-button" type="submit">Send Test Email</button>
        </form>
    </main>
</body>
</html>
