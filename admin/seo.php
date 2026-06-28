<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$settings = current_seo_settings();
$posts = read_blog_posts(false);
$publishedPosts = array_values(array_filter($posts, fn($post) => ($post['status'] ?? '') === 'Published'));
$indexedPosts = array_values(array_filter($publishedPosts, fn($post) => !post_noindex($post)));
$averageSeo = 0;
if (!empty($posts)) {
    $averageSeo = (int) round(array_sum(array_map(fn($post) => seo_score_post($post)['percent'], $posts)) / count($posts));
}

$setup = [
    'Google Analytics tag' => $settings['GA_MEASUREMENT_ID'] !== '',
    'Search Console verification' => $settings['GOOGLE_SITE_VERIFICATION'] !== '',
    'Default SEO title' => $settings['DEFAULT_SEO_TITLE'] !== '',
    'Default meta description' => $settings['DEFAULT_SEO_DESCRIPTION'] !== '',
    'Sitemap available' => is_file(__DIR__ . '/../sitemap.php'),
    'Robots available' => is_file(__DIR__ . '/../robots.txt'),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google SEO | Flexi Feet Admin</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <link rel="stylesheet" href="flexi-admin.css">
</head>
<body class="wp-admin">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="wp-main">
        <div class="wp-topbar">
            <div>
                <h1>Google SEO</h1>
                <p>Manage Google visibility, Search Console verification, Analytics tracking, sitemaps, and blog SEO quality.</p>
            </div>
            <a href="settings.php#google-seo-settings" class="wp-button primary">Configure Google</a>
        </div>

        <div class="admin-help-card">
            <h2>How to use this page</h2>
            <p>Start at the checklist, paste Google codes in Settings, submit <code><?= e(absolute_url('sitemap.php')) ?></code> in Search Console, then improve posts with weak SEO scores.</p>
        </div>

        <div class="seo-dashboard-grid">
            <div class="wp-panel seo-stat-card">
                <span>Published posts</span>
                <strong><?= count($publishedPosts) ?></strong>
                <p><?= count($indexedPosts) ?> indexable posts in sitemap.</p>
            </div>
            <div class="wp-panel seo-stat-card">
                <span>Average SEO readiness</span>
                <strong><?= $averageSeo ?>%</strong>
                <p>Based on title, slug, meta, image, content, publish status, and indexability.</p>
            </div>
            <div class="wp-panel seo-stat-card">
                <span>Google tracking</span>
                <strong><?= $settings['GA_MEASUREMENT_ID'] !== '' ? 'On' : 'Off' ?></strong>
                <p><?= $settings['GA_MEASUREMENT_ID'] !== '' ? e($settings['GA_MEASUREMENT_ID']) : 'Add GA4 Measurement ID in Settings.' ?></p>
            </div>
        </div>

        <section class="wp-panel seo-section-panel">
            <h2>Setup Checklist</h2>
            <div class="seo-checklist-grid">
                <?php foreach ($setup as $label => $ok): ?>
                    <div class="seo-check-item <?= $ok ? 'ok' : 'todo' ?>">
                        <strong><?= $ok ? 'Done' : 'Needed' ?></strong>
                        <span><?= e($label) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="wp-panel seo-section-panel">
            <h2>Google Setup Shortcuts</h2>
            <div class="seo-two-col">
                <div>
                    <h3>Search Console</h3>
                    <ol>
                        <li>Open Google Search Console and add the URL-prefix property for <code><?= e(SITE_URL) ?></code>.</li>
                        <li>Choose HTML meta tag verification and copy only the content value.</li>
                        <li>Paste it in <a href="settings.php#google-seo-settings">Settings > Google SEO</a>.</li>
                        <li>Submit sitemap: <code><?= e(absolute_url('sitemap.php')) ?></code>.</li>
                    </ol>
                </div>
                <div>
                    <h3>Google Analytics 4</h3>
                    <ol>
                        <li>Create or open a GA4 web stream.</li>
                        <li>Copy the Measurement ID that starts with <code>G-</code>.</li>
                        <li>Paste it in Settings. The site outputs the Google tag automatically.</li>
                        <li>For admin report fetching later, add service account details without Composer.</li>
                    </ol>
                </div>
            </div>
        </section>

        <section class="wp-panel seo-section-panel">
            <h2>Content SEO</h2>
            <table class="wp-table">
                <thead>
                    <tr>
                        <th>Post</th>
                        <th>Status</th>
                        <th>SEO</th>
                        <th>Indexing</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <?php $score = seo_score_post($post); ?>
                        <tr>
                            <td>
                                <strong><?= e($post['title']) ?></strong>
                                <div class="row-actions"><?= e(post_seo_description($post) ?: 'No meta description yet.') ?></div>
                            </td>
                            <td><span class="wp-status <?= strtolower(e($post['status'])) ?>"><?= e($post['status']) ?></span></td>
                            <td><span class="seo-score-pill <?= $score['percent'] >= 80 ? 'good' : ($score['percent'] >= 55 ? 'warn' : 'bad') ?>"><?= $score['percent'] ?>%</span></td>
                            <td><?= post_noindex($post) ? 'Noindex' : 'Indexable' ?></td>
                            <td><a href="post-edit.php?id=<?= e($post['id']) ?>" class="wp-button">Improve</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($posts)): ?>
                        <tr><td colspan="5" class="empty-cell">No posts yet. Create posts, then improve their SEO here.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
