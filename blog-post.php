<?php
require_once __DIR__ . '/includes/functions.php';
$slug = sanitize_blog_slug((string) ($_GET['slug'] ?? ''));
$post = $slug !== '' ? find_blog_post($slug, true) : null;
http_response_code($post ? 200 : 404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <?php if ($post): ?>
        <?php render_seo_tags(
            post_seo_title($post),
            post_seo_description($post),
            post_canonical_path($post),
            post_social_image($post),
            'article'
        ); ?>
        <?php if (post_noindex($post)): ?>
            <meta name="robots" content="noindex,follow">
        <?php endif; ?>
        <?php render_json_ld([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post['title'],
            'description' => post_seo_description($post),
            'image' => absolute_url(post_social_image($post)),
            'datePublished' => $post['published_at'] ?: $post['created_at'],
            'dateModified' => $post['updated_at'] ?? ($post['published_at'] ?: $post['created_at']),
            'author' => ['@type' => 'Organization', 'name' => BUSINESS_NAME],
            'publisher' => ['@type' => 'Organization', 'name' => BUSINESS_NAME, 'logo' => ['@type' => 'ImageObject', 'url' => absolute_url('assets/images/flexi-feet-logo.png')]],
            'mainEntityOfPage' => absolute_url('blog-post.php?slug=' . $post['slug'])
        ]); ?>
    <?php else: ?>
        <?php render_seo_tags('Post Not Found | Flexi Feet', 'This Flexi Feet blog post is unavailable.', 'blog.php'); ?>
    <?php endif; ?>
    <?php render_google_analytics(); ?>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<header>
    <div class="nav-container">
        <a href="./" class="logo"><img src="assets/images/flexi-feet-logo.png" alt="Flexi Feet"></a>
        <nav>
            <ul>
                <li><a href="./#about">About</a></li>
                <li><a href="./#products">Products</a></li>
                <li><a href="./#technology">Technology</a></li>
                <li><a href="./#conditions">Conditions</a></li>
                <li><a href="blog.php">Blog</a></li>
                <li><a href="./#process">Process</a></li>
            </ul>
        </nav>
        <a href="./#booking" class="cta-button">Book a Fitting</a>
    </div>
</header>

<main style="padding-top: var(--header-height);">
    <section class="blog-single">
        <div class="container blog-single-container">
            <?php if ($post): ?>
                <a href="blog.php" class="back-to-blog">&larr; Blog</a>
                <h1><?= e($post['title']) ?></h1>
                <div class="blog-date"><?= e(date('M j, Y', strtotime($post['published_at'] ?: $post['created_at']))) ?></div>
                <?php if (!empty($post['featured_image'])): ?>
                    <img class="blog-hero-image" src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>">
                <?php endif; ?>
                <article class="blog-content"><?= render_post_content($post['content']) ?></article>
            <?php else: ?>
                <h1>Post not found</h1>
                <p>This post is unavailable or has not been published.</p>
                <a href="blog.php" class="read-more">Back to Blog</a>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
