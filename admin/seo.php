<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$settings = current_seo_settings();
$report = google_seo_report(($_GET['refresh'] ?? '') === '1');
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
                <p>Google verification, live reports, sitemap status, and blog SEO quality.</p>
            </div>
            <a href="settings.php#google-seo-settings" class="wp-button primary">Configure Google</a>
        </div>

        <div class="admin-help-card">
            <h2>SEO command center</h2>
            <p>Use this page to see what Google can read, what is connected, and which posts need stronger snippets. Configuration stays in Settings to avoid duplicated controls.</p>
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
            <div class="section-heading-row">
                <div>
                    <h2>Google Report</h2>
                    <p>Last 28 available days from GA4 and Search Console. Uses service account auth, no Composer package.</p>
                </div>
                <a href="seo.php?refresh=1" class="wp-button">Refresh</a>
            </div>
            <?php if (!$report['ok']): ?>
                <div class="empty-cell compact"><?= e($report['message']) ?></div>
            <?php else: ?>
                <div class="seo-dashboard-grid compact">
                    <div class="seo-stat-card soft">
                        <span>GA4 users</span>
                        <strong><?= e((string) ($report['ga4']['metrics']['activeUsers'] ?? 0)) ?></strong>
                        <p><?= $report['ga4']['ok'] ? 'Connected' : e($report['ga4']['message']) ?></p>
                    </div>
                    <div class="seo-stat-card soft">
                        <span>Search clicks</span>
                        <strong><?= e((string) ($report['search_console']['metrics']['clicks'] ?? 0)) ?></strong>
                        <p><?= $report['search_console']['ok'] ? e(($report['search_console']['metrics']['impressions'] ?? 0) . ' impressions') : e($report['search_console']['message']) ?></p>
                    </div>
                    <div class="seo-stat-card soft">
                        <span>Average CTR</span>
                        <strong><?= e((string) ($report['search_console']['metrics']['ctr'] ?? 0)) ?>%</strong>
                        <p><?= $report['cached'] ? 'Cached report' : 'Fresh report' ?></p>
                    </div>
                </div>
                <div class="seo-two-col">
                    <div>
                        <h3>Top Search Queries</h3>
                        <table class="wp-table compact-table">
                            <tbody>
                                <?php foreach (($report['search_console']['queries'] ?? []) as $row): ?>
                                    <tr><td><?= e($row['label']) ?></td><td><?= e((string) $row['clicks']) ?> clicks</td><td><?= e((string) $row['position']) ?> pos.</td></tr>
                                <?php endforeach; ?>
                                <?php if (empty($report['search_console']['queries'])): ?><tr><td class="empty-cell compact" colspan="3">No Search Console rows yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <h3>Top Pages</h3>
                        <table class="wp-table compact-table">
                            <tbody>
                                <?php foreach (($report['ga4']['pages'] ?? []) as $row): ?>
                                    <tr><td><?= e($row['path']) ?></td><td><?= e((string) $row['views']) ?> views</td><td><?= e((string) $row['users']) ?> users</td></tr>
                                <?php endforeach; ?>
                                <?php if (empty($report['ga4']['pages'])): ?><tr><td class="empty-cell compact" colspan="3">No GA4 rows yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </section>

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
