<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$csrf = csrf_token();
$topic = normalize_text($_POST['topic'] ?? 'custom diabetic shoes and offload insoles in Malaysia', 180);
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? null)) {
    $result = call_google_ai_studio(generate_ai_blog_prompt($topic));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Writer | Flexi Feet Admin</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <link rel="stylesheet" href="flexi-admin.css">
</head>
<body class="wp-admin">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="wp-main">
        <div class="wp-topbar">
            <div>
                <h1>AI Blog Writer</h1>
                <p>Generate SEO drafts with Google AI Studio Gemma, then review and publish manually.</p>
            </div>
            <a href="settings.php" class="wp-button">AI Settings</a>
        </div>
        <?php if (!google_ai_configured()): ?>
            <div class="wp-notice error">Add your Google AI Studio API key in Settings before generating.</div>
        <?php endif; ?>
        <form method="POST" class="settings-form wp-panel">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <label>Topic / Search Opportunity</label>
            <input type="text" name="topic" value="<?= e($topic) ?>" required>
            <button class="wp-button primary" type="submit">Generate Draft</button>
            <p class="field-help">Use this for weekly blog drafts and manual Reddit/Quora answer drafts. Do not auto-post comments.</p>
        </form>
        <?php if ($result): ?>
            <div class="wp-panel ai-output">
                <?php if ($result['ok']): ?>
                    <h2>Generated Draft</h2>
                    <textarea rows="24" onclick="this.select()"><?= e($result['text']) ?></textarea>
                <?php else: ?>
                    <div class="wp-notice error"><?= e($result['message']) ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
