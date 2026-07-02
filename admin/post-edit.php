<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = normalize_text($_GET['id'] ?? '', 80);
$post = $id !== '' ? find_blog_post($id, false) : null;
$error = '';
$saved = false;
$mediaFiles = list_media_files();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'Security token expired. Please refresh and try again.';
    } elseif (trim((string) ($_POST['title'] ?? '')) === '') {
        $error = 'Title is required.';
    } else {
        $featuredImage = normalize_text($_POST['featured_image'] ?? '', 300);
        if (isset($_FILES['featured_upload'])) {
            $upload = handle_media_upload($_FILES['featured_upload']);
            if (!$upload['ok']) {
                $error = $upload['message'];
            } elseif ($upload['path'] !== '') {
                $featuredImage = $upload['path'];
            }
        }
    }

    if ($error === '') {
        $post = save_blog_post([
            'title' => $_POST['title'] ?? '',
            'slug' => $_POST['slug'] ?? '',
            'excerpt' => $_POST['excerpt'] ?? '',
            'content' => $_POST['content'] ?? '',
            'status' => $_POST['status'] ?? 'Draft',
            'featured_image' => $featuredImage,
            'seo_title' => $_POST['seo_title'] ?? '',
            'seo_description' => $_POST['seo_description'] ?? '',
            'focus_keyword' => $_POST['focus_keyword'] ?? '',
            'canonical_url' => $_POST['canonical_url'] ?? '',
            'social_image' => $_POST['social_image'] ?? '',
            'noindex' => $_POST['noindex'] ?? '',
        ], $post['id'] ?? null);
        header('Location: post-edit.php?id=' . rawurlencode($post['id']) . '&saved=1');
        exit;
    }
}

$saved = ($_GET['saved'] ?? '') === '1';

$csrf = csrf_token();
$post = $post ?: [
    'title' => '',
    'slug' => '',
    'excerpt' => '',
    'content' => '',
    'status' => 'Draft',
    'featured_image' => '',
    'seo_title' => '',
    'seo_description' => '',
    'focus_keyword' => '',
    'canonical_url' => '',
    'social_image' => '',
    'noindex' => '',
];
$seoScore = seo_score_post($post);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Edit Post' : 'Add New Post' ?> | Flexi Feet Admin</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <link rel="stylesheet" href="flexi-admin.css">
</head>
<body class="wp-admin">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="wp-main">
        <div class="wp-topbar">
            <div>
                <h1><?= $id ? 'Edit Post' : 'Add New Post' ?></h1>
                <p>Write and publish Flexi Feet blog content.</p>
            </div>
            <a href="posts.php" class="wp-button">All Posts</a>
        </div>

        <?php if ($saved): ?><div class="wp-notice">Post saved.</div><?php endif; ?>
        <?php if ($error): ?><div class="wp-notice error"><?= e($error) ?></div><?php endif; ?>

        <form method="POST" class="editor-layout" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <section class="editor-main">
                <input class="title-input" type="text" name="title" value="<?= e($post['title']) ?>" placeholder="Add title" required>
                <label>Permalink Slug</label>
                <input type="text" name="slug" value="<?= e($post['slug']) ?>" placeholder="auto-generated-from-title">
                <label>Excerpt</label>
                <textarea name="excerpt" rows="3" placeholder="Short blog summary for the archive page"><?= e($post['excerpt']) ?></textarea>
                <label>Content</label>
                <textarea class="content-editor" name="content" rows="18" placeholder="# Heading&#10;&#10;Write with **bold**, lists, links, quotes, and images like GitHub README Markdown."><?= e($post['content']) ?></textarea>
                <p class="field-help">Supports GitHub-style Markdown: headings, bold/italic, links, lists, quotes, inline code, and images. Existing <code>[image:path]</code> blocks still work.</p>
                <div class="seo-editor-panel">
                    <div class="seo-editor-heading">
                        <div>
                            <h2>Google SEO</h2>
                            <p>Control how this post is understood by Google and previewed in search/social results.</p>
                        </div>
                        <span class="seo-score-badge <?= $seoScore['percent'] >= 80 ? 'good' : ($seoScore['percent'] >= 55 ? 'warn' : 'bad') ?>"><?= $seoScore['percent'] ?>%</span>
                    </div>
                    <label>SEO Title</label>
                    <input type="text" name="seo_title" value="<?= e($post['seo_title']) ?>" maxlength="180" placeholder="<?= e(($post['title'] ?: 'Post title') . ' | Flexi Feet') ?>">
                    <p class="field-help">Aim for a clear title around 50-65 characters. Put the main topic first.</p>
                    <label>Meta Description</label>
                    <textarea name="seo_description" rows="3" maxlength="320" placeholder="Write a useful Google snippet for this post."><?= e($post['seo_description']) ?></textarea>
                    <p class="field-help">Google may rewrite snippets, but a helpful description can improve click-through.</p>
                    <label>Focus Keyword</label>
                    <input type="text" name="focus_keyword" value="<?= e($post['focus_keyword']) ?>" placeholder="example: diabetic shoes malaysia">
                    <label>Canonical URL Override</label>
                    <input type="text" name="canonical_url" value="<?= e($post['canonical_url']) ?>" placeholder="Leave blank to use the post URL">
                    <label>Social Image</label>
                    <input type="text" name="social_image" value="<?= e($post['social_image']) ?>" placeholder="Leave blank to use featured image">
                    <label class="checkbox-row">
                        <input type="checkbox" name="noindex" value="1" <?= post_noindex($post) ? 'checked' : '' ?>>
                        Hide this post from Google indexing
                    </label>
                    <div class="google-preview">
                        <span><?= e(parse_url(SITE_URL, PHP_URL_HOST) ?: 'flexifeet.net') ?> › blog</span>
                        <strong><?= e(post_seo_title($post)) ?></strong>
                        <p><?= e(post_seo_description($post) ?: 'Add a meta description to control the search preview.') ?></p>
                    </div>
                </div>
            </section>
            <aside class="editor-side">
                <div class="wp-panel side-panel">
                    <h2>Publish</h2>
                    <div class="seo-checklist-mini">
                        <strong>SEO readiness: <?= $seoScore['passed'] ?>/<?= $seoScore['total'] ?></strong>
                        <ul>
                            <li class="<?= $seoScore['checks']['meta'] ? 'ok' : '' ?>">Meta description</li>
                            <li class="<?= $seoScore['checks']['image'] ? 'ok' : '' ?>">Social image</li>
                            <li class="<?= $seoScore['checks']['content'] ? 'ok' : '' ?>">Useful content length</li>
                            <li class="<?= $seoScore['checks']['indexable'] ? 'ok' : '' ?>">Indexable</li>
                        </ul>
                    </div>
                    <label>Status</label>
                    <select name="status">
                        <option value="Draft" <?= $post['status'] === 'Draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="Published" <?= $post['status'] === 'Published' ? 'selected' : '' ?>>Published</option>
                    </select>
                    <button class="wp-button primary full" type="submit">Save Post</button>
                    <?php if (!empty($post['slug']) && $post['status'] === 'Published'): ?>
                        <a class="wp-button full" href="../blog-post.php?slug=<?= e($post['slug']) ?>" target="_blank">View Post</a>
                    <?php endif; ?>
                </div>
                <div class="wp-panel side-panel">
                    <h2>Featured Image</h2>
                    <input type="text" name="featured_image" value="<?= e($post['featured_image']) ?>" placeholder="assets/images/example.jpg">
                    <label>Upload New</label>
                    <input type="file" name="featured_upload" accept="image/*">
                    <?php if (!empty($post['featured_image'])): ?>
                        <img class="media-preview" src="<?= e(admin_media_src($post['featured_image'])) ?>" alt="">
                    <?php endif; ?>
                    <p class="field-help">Upload a new image or choose a path from Media Library.</p>
                    <?php if (!empty($mediaFiles)): ?>
                        <div class="media-quick-grid">
                            <?php foreach (array_slice($mediaFiles, 0, 8) as $media): ?>
                                <button type="button" class="media-pick" data-featured-path="<?= e($media['path']) ?>" title="Set as featured image">
                                    <img src="<?= e(admin_media_src($media['path'])) ?>" alt="<?= e($media['name']) ?>">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($mediaFiles)): ?>
                    <div class="wp-panel side-panel">
                        <h2>Insert Media</h2>
                        <div class="media-action-list">
                            <?php foreach (array_slice($mediaFiles, 0, 10) as $media): ?>
                                <button type="button" data-insert-path="<?= e($media['path']) ?>">
                                    <img src="<?= e(admin_media_src($media['path'])) ?>" alt="">
                                    <span><?= e($media['name']) ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </aside>
        </form>
        <script>
            document.querySelectorAll('[data-featured-path]').forEach((button) => {
                button.addEventListener('click', () => {
                    const input = document.querySelector('input[name="featured_image"]');
                    if (input) input.value = button.dataset.featuredPath;
                });
            });
            document.querySelectorAll('[data-insert-path]').forEach((button) => {
                button.addEventListener('click', () => {
                    const editor = document.querySelector('textarea[name="content"]');
                    if (!editor) return;
                    const shortcode = `\n\n[image:${button.dataset.insertPath}]\n\n`;
                    const start = editor.selectionStart || editor.value.length;
                    const end = editor.selectionEnd || editor.value.length;
                    editor.value = editor.value.slice(0, start) + shortcode + editor.value.slice(end);
                    editor.focus();
                });
            });
        </script>
    </main>
</body>
</html>
