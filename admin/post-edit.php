<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = normalize_text($_GET['id'] ?? '', 80);
$post = $id !== '' ? find_blog_post($id, false) : null;
$error = '';
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'Security token expired. Please refresh and try again.';
    } elseif (trim((string) ($_POST['title'] ?? '')) === '') {
        $error = 'Title is required.';
    } else {
        $post = save_blog_post([
            'title' => $_POST['title'] ?? '',
            'slug' => $_POST['slug'] ?? '',
            'excerpt' => $_POST['excerpt'] ?? '',
            'content' => $_POST['content'] ?? '',
            'status' => $_POST['status'] ?? 'Draft',
            'featured_image' => $_POST['featured_image'] ?? '',
        ], $post['id'] ?? null);
        $saved = true;
        $id = $post['id'];
    }
}

$csrf = csrf_token();
$post = $post ?: [
    'title' => '',
    'slug' => '',
    'excerpt' => '',
    'content' => '',
    'status' => 'Draft',
    'featured_image' => '',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Edit Post' : 'Add New Post' ?> | Flexi Feet Admin</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <link rel="stylesheet" href="admin.css">
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

        <form method="POST" class="editor-layout">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <section class="editor-main">
                <input class="title-input" type="text" name="title" value="<?= e($post['title']) ?>" placeholder="Add title" required>
                <label>Permalink Slug</label>
                <input type="text" name="slug" value="<?= e($post['slug']) ?>" placeholder="auto-generated-from-title">
                <label>Excerpt</label>
                <textarea name="excerpt" rows="3" placeholder="Short blog summary for the archive page"><?= e($post['excerpt']) ?></textarea>
                <label>Content</label>
                <textarea class="content-editor" name="content" rows="18" placeholder="Write the post content here..."><?= e($post['content']) ?></textarea>
            </section>
            <aside class="editor-side">
                <div class="wp-panel side-panel">
                    <h2>Publish</h2>
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
                    <p class="field-help">Use an uploaded image URL or an existing asset path.</p>
                </div>
            </aside>
        </form>
    </main>
</body>
</html>
