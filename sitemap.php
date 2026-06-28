<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=UTF-8');
$urls = [
    ['loc' => absolute_url(''), 'priority' => '1.0'],
    ['loc' => absolute_url('blog.php'), 'priority' => '0.8'],
];
foreach (read_blog_posts(true) as $post) {
    if (post_noindex($post)) {
        continue;
    }
    $urls[] = [
        'loc' => absolute_url('blog-post.php?slug=' . $post['slug']),
        'priority' => '0.7',
        'lastmod' => date('Y-m-d', strtotime($post['updated_at'] ?? $post['published_at'] ?? $post['created_at'])),
    ];
}
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
    <url>
        <loc><?= e($url['loc']) ?></loc>
        <?php if (!empty($url['lastmod'])): ?><lastmod><?= e($url['lastmod']) ?></lastmod><?php endif; ?>
        <priority><?= e($url['priority']) ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
