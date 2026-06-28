<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$csrf = csrf_token();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'Security token expired. Please refresh and try again.';
    } else {
        $upload = handle_media_upload($_FILES['media_upload'] ?? []);
        if ($upload['ok'] && $upload['path'] !== '') {
            $message = 'Media uploaded.';
        } elseif (!$upload['ok']) {
            $error = $upload['message'];
        }
    }
}

$mediaFiles = list_media_files();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media | Flexi Feet Admin</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <link rel="stylesheet" href="flexi-admin.css">
</head>
<body class="wp-admin">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="wp-main">
        <div class="wp-topbar">
            <div>
                <h1>Media Library</h1>
                <p>Upload images for blog posts, reel thumbnails, and website content.</p>
            </div>
        </div>

        <?php if ($message): ?><div class="wp-notice"><?= e($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="wp-notice error"><?= e($error) ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="wp-panel upload-panel">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="file" name="media_upload" accept="image/*" required>
            <button class="wp-button primary" type="submit">Upload Media</button>
        </form>

        <div class="media-library-grid">
            <?php foreach ($mediaFiles as $media): ?>
                <article class="media-card">
                    <img src="../<?= e($media['path']) ?>" alt="<?= e($media['name']) ?>">
                    <div>
                        <strong><?= e($media['name']) ?></strong>
                        <input type="text" value="<?= e($media['path']) ?>" readonly onclick="this.select()">
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (empty($mediaFiles)): ?>
                <div class="empty-cell">No media uploaded yet.</div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
