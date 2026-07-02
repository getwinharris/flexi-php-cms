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
            if ($url === '' || !is_social_reel_url($url)) {
                $error = 'Enter a valid Instagram Reel or YouTube Shorts URL.';
            } elseif (reel_thumbnail_from_url($url) === '') {
                $error = 'Use a direct Instagram Reel URL so the thumbnail can be generated automatically.';
            } else {
                $editing = save_reel([
                    'url' => $url,
                    'status' => $_POST['status'] ?? 'Active',
                    'sort_order' => $_POST['sort_order'] ?? '',
                ], normalize_text($_POST['id'] ?? '', 80) ?: null);
                $message = 'Reel saved. Thumbnail was generated from the reel URL.';
            }
        }
    }
}

$reels = read_reels(false);
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
                <p>Paste an Instagram Reel URL and add it. The website thumbnail and title are generated automatically from the reel link.</p>
            </div>
            <a href="reels.php" class="wp-button">Add New</a>
        </div>

        <?php if ($message): ?><div class="wp-notice"><?= e($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="wp-notice error"><?= e($error) ?></div><?php endif; ?>

        <div class="editor-layout">
            <section class="editor-main">
                <h2><?= $editing['id'] ? 'Edit Reel' : 'Add Reel' ?></h2>
                <form method="POST">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= e($editing['id']) ?>">
                    <input type="hidden" name="sort_order" value="<?= e((string) $editing['sort_order']) ?>">
                    <label>Instagram Reel URL</label>
                    <input type="url" name="url" value="<?= e($editing['url']) ?>" placeholder="https://www.instagram.com/reel/... or https://www.youtube.com/shorts/..." required>
                    <p class="field-help">No upload is needed. Instagram thumbnails are pulled from Instagram; YouTube Shorts use the YouTube thumbnail URL.</p>
                    <?php if (!empty($editing['thumbnail'])): ?>
                        <div class="auto-reel-preview">
                            <img class="media-preview" src="<?= e(admin_media_src($editing['thumbnail'])) ?>" alt="">
                            <div>
                                <strong><?= e($editing['title'] ?: 'Auto-generated reel') ?></strong>
                                <span><?= e($editing['thumbnail']) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($editing['id']): ?>
                        <label>Status</label>
                        <select name="status">
                            <option value="Active" <?= $editing['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= $editing['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="status" value="Active">
                    <?php endif; ?>
                    <button class="wp-button primary" type="submit"><?= $editing['id'] ? 'Update Reel' : 'Add Reel' ?></button>
                </form>
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
                                    <img src="<?= e(admin_media_src($reel['thumbnail'])) ?>" alt="">
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
