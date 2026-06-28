<?php
require_once __DIR__ . '/includes/functions.php';
$posts = read_blog_posts(true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog | Flexi Feet</title>
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
    <section class="blog-archive">
        <div class="container">
            <h1 class="section-title">Flexi Feet Blog</h1>
            <p class="section-subtitle">Foot care guidance, product education, and clinic updates from Flexi Feet.</p>
            <div class="blog-grid">
                <?php foreach ($posts as $post): ?>
                    <article class="blog-card hover-lift">
                        <?php if (!empty($post['featured_image'])): ?>
                            <img src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>">
                        <?php endif; ?>
                        <div class="blog-card-body">
                            <span class="blog-date"><?= e(date('M j, Y', strtotime($post['published_at'] ?: $post['created_at']))) ?></span>
                            <h2><a href="blog-post.php?slug=<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h2>
                            <p><?= e($post['excerpt']) ?></p>
                            <a class="read-more" href="blog-post.php?slug=<?= e($post['slug']) ?>">Read More</a>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (empty($posts)): ?>
                    <div class="empty-blog">
                        <h2>No posts published yet.</h2>
                        <p>Please check back soon for Flexi Feet updates.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
</body>
</html>
