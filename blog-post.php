<?php
require_once __DIR__ . '/includes/functions.php';
$slug = normalize_text($_GET['slug'] ?? '', 180);
$post = $slug !== '' ? find_blog_post($slug, true) : null;
http_response_code($post ? 200 : 404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $post ? e($post['title']) : 'Post Not Found' ?> | Flexi Feet</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
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
