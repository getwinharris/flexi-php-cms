<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$csrf = csrf_token();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? null)) {
    $action = $_POST['action'] ?? '';
    $id = normalize_text($_POST['id'] ?? '', 80);
    if ($action === 'delete' && $id !== '' && delete_blog_post($id)) {
        $message = 'Post moved to trash.';
    }
}

$posts = read_blog_posts(false);
$published = count(array_filter($posts, fn($post) => ($post['status'] ?? '') === 'Published'));
$drafts = count(array_filter($posts, fn($post) => ($post['status'] ?? '') === 'Draft'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts | Flexi Feet Admin</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="wp-admin">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="wp-main">
        <div class="wp-topbar">
            <div>
                <h1>Posts</h1>
                <p><?= count($posts) ?> total, <?= $published ?> published, <?= $drafts ?> drafts</p>
            </div>
            <a href="post-edit.php" class="wp-button primary">Add New</a>
        </div>

        <?php if ($message): ?><div class="wp-notice"><?= e($message) ?></div><?php endif; ?>

        <div class="wp-panel">
            <table class="wp-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Slug</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td>
                                <strong><a href="post-edit.php?id=<?= e($post['id']) ?>"><?= e($post['title']) ?></a></strong>
                                <div class="row-actions">
                                    <?php if (($post['status'] ?? '') === 'Published'): ?>
                                        <a href="../blog-post.php?slug=<?= e($post['slug']) ?>" target="_blank">View</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><span class="wp-status <?= strtolower(e($post['status'])) ?>"><?= e($post['status']) ?></span></td>
                            <td><?= e($post['slug']) ?></td>
                            <td><?= e($post['updated_at'] ?? '') ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Delete this post?');">
                                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= e($post['id']) ?>">
                                    <button class="link-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($posts)): ?>
                        <tr><td colspan="5" class="empty-cell">No posts yet. Create your first blog post.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
