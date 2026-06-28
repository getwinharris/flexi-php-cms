<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$csrf = csrf_token();
$message = '';
$error = '';
$editingId = normalize_text($_GET['id'] ?? '', 80);
$editing = $editingId !== '' ? find_reel($editingId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'Security token expired. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? 'save';
        if ($action === 'delete') {
            if (delete_reel(normalize_text($_POST['id'] ?? '', 80))) {
                $message = 'Reel removed.';
                $editing = null;
            }
        } elseif ($action === 'order') {
            $order = array_filter(array_map('trim', explode(',', (string) ($_POST['order'] ?? ''))));
            update_reel_order($order);
            $message = 'Reel order saved.';
        } else {
            $url = sanitize_external_url((string) ($_POST['url'] ?? ''));
            if ($url === '' || !is_instagram_url($url)) {
                $error = 'Enter a valid Instagram Reel URL.';
            } else {
                $thumbnail = normalize_text($_POST['thumbnail'] ?? '', 300);
                if (isset($_FILES['thumbnail_upload'])) {
                    $upload = handle_media_upload($_FILES['thumbnail_upload']);
                    if (!$upload['ok']) {
                        $error = $upload['message'];
                    } elseif ($upload['path'] !== '') {
                        $thumbnail = $upload['path'];
                    }
                }
                if ($error === '' && $thumbnail === '') {
                    $error = 'Attach a thumbnail for this Reel.';
                }
                if ($error === '') {
                    $editing = save_reel([
                        'title' => $_POST['title'] ?? '',
                        'url' => $url,
                        'thumbnail' => $thumbnail,
                        'status' => $_POST['status'] ?? 'Active',
                        'sort_order' => $_POST['sort_order'] ?? '',
                    ], normalize_text($_POST['id'] ?? '', 80) ?: null);
                    $message = 'Reel saved.';
                }
            }
        }
    }
}

$reels = read_reels(false);
$mediaFiles = list_media_files();
$editing = $editing ?: [
    'id' => '',
    'title' => '',
    'url' => '',
    'thumbnail' => '',
    'status' => 'Active',
    'sort_order' => count($reels) + 1,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram Reels | Flexi Feet Admin</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <link rel="stylesheet" href="flexi-admin.css">
</head>
<body class="wp-admin">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="wp-main">
        <div class="wp-topbar">
            <div>
                <h1>Instagram Reels</h1>
                <p>Add Reel URLs, attach thumbnails, and drag to control website order.</p>
            </div>
            <a href="reels.php" class="wp-button">Add New</a>
        </div>

        <?php if ($message): ?><div class="wp-notice"><?= e($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="wp-notice error"><?= e($error) ?></div><?php endif; ?>

        <div class="editor-layout">
            <section class="editor-main">
                <h2><?= $editing['id'] ? 'Edit Reel' : 'Add Reel' ?></h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= e($editing['id']) ?>">
                    <input type="hidden" name="sort_order" value="<?= e((string) $editing['sort_order']) ?>">
                    <label>Title</label>
                    <input type="text" name="title" value="<?= e($editing['title']) ?>" placeholder="Patient story, product demo, workshop reel">
                    <label>Instagram Reel URL</label>
                    <input type="url" name="url" value="<?= e($editing['url']) ?>" placeholder="https://www.instagram.com/reel/..." required>
                    <label>Thumbnail Path</label>
                    <input type="text" name="thumbnail" value="<?= e($editing['thumbnail']) ?>" placeholder="assets/uploads/2026/06/reel.jpg">
                    <label>Upload Thumbnail</label>
                    <input type="file" name="thumbnail_upload" accept="image/*">
                    <?php if (!empty($editing['thumbnail'])): ?>
                        <img class="media-preview" src="../<?= e($editing['thumbnail']) ?>" alt="">
                    <?php endif; ?>
                    <label>Status</label>
                    <select name="status">
                        <option value="Active" <?= $editing['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $editing['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <button class="wp-button primary" type="submit">Save Reel</button>
                </form>
                <?php if (!empty($mediaFiles)): ?>
                    <h2>Pick Thumbnail from Media</h2>
                    <div class="media-quick-grid wide">
                        <?php foreach (array_slice($mediaFiles, 0, 12) as $media): ?>
                            <button type="button" class="media-pick" data-media-path="<?= e($media['path']) ?>">
                                <img src="../<?= e($media['path']) ?>" alt="<?= e($media['name']) ?>">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="editor-side">
                <form method="POST" class="wp-panel side-panel" id="reel-order-form">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="order">
                    <input type="hidden" name="order" id="reel-order">
                    <h2>Website Order</h2>
                    <div class="sortable-list" id="reel-sort-list">
                        <?php foreach ($reels as $reel): ?>
                            <div class="sortable-item" draggable="true" data-id="<?= e($reel['id']) ?>">
                                <span class="drag-handle">::</span>
                                <?php if (!empty($reel['thumbnail'])): ?>
                                    <img src="../<?= e($reel['thumbnail']) ?>" alt="">
                                <?php endif; ?>
                                <div>
                                    <strong><?= e($reel['title'] ?: 'Untitled Reel') ?></strong>
                                    <small><?= e($reel['status']) ?></small>
                                    <a href="reels.php?id=<?= e($reel['id']) ?>">Edit</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="wp-button primary full" type="submit">Save Order</button>
                </form>
                <?php if (!empty($editing['id'])): ?>
                    <form method="POST" class="wp-panel side-panel" onsubmit="return confirm('Delete this reel?');">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= e($editing['id']) ?>">
                        <button class="link-danger" type="submit">Delete Reel</button>
                    </form>
                <?php endif; ?>
            </aside>
        </div>
    </main>
    <script>
        document.querySelectorAll('[data-media-path]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.querySelector('input[name="thumbnail"]');
                if (input) input.value = button.dataset.mediaPath;
            });
        });

        const list = document.getElementById('reel-sort-list');
        let dragged = null;
        list?.addEventListener('dragstart', (event) => {
            dragged = event.target.closest('[draggable="true"]');
            dragged?.classList.add('is-dragging');
        });
        list?.addEventListener('dragend', () => {
            dragged?.classList.remove('is-dragging');
            dragged = null;
        });
        list?.addEventListener('dragover', (event) => {
            event.preventDefault();
            const item = event.target.closest('.sortable-item');
            if (!dragged || !item || item === dragged) return;
            const box = item.getBoundingClientRect();
            const after = event.clientY > box.top + box.height / 2;
            list.insertBefore(dragged, after ? item.nextSibling : item);
        });
        document.getElementById('reel-order-form')?.addEventListener('submit', () => {
            document.getElementById('reel-order').value = Array.from(list.querySelectorAll('[data-id]')).map((item) => item.dataset.id).join(',');
        });
    </script>
</body>
</html>
