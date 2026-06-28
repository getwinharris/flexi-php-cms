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
        ], $post['id'] ?? null);
        $saved = true;
        $id = $post['id'];
        $mediaFiles = list_media_files();
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
                    <label>Upload New</label>
                    <input type="file" name="featured_upload" accept="image/*">
                    <?php if (!empty($post['featured_image'])): ?>
                        <img class="media-preview" src="../<?= e($post['featured_image']) ?>" alt="">
                    <?php endif; ?>
                    <p class="field-help">Upload a new image or choose a path from Media Library.</p>
                    <?php if (!empty($mediaFiles)): ?>
                        <div class="media-quick-grid">
                            <?php foreach (array_slice($mediaFiles, 0, 8) as $media): ?>
                                <button type="button" class="media-pick" data-featured-path="<?= e($media['path']) ?>" title="Set as featured image">
                                    <img src="../<?= e($media['path']) ?>" alt="<?= e($media['name']) ?>">
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
                                    <img src="../<?= e($media['path']) ?>" alt="">
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
