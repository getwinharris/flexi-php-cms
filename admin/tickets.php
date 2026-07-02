<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$tickets = read_support_tickets();
$csrf = csrf_token();
$open = count(array_filter($tickets, fn($ticket) => ($ticket['status'] ?? '') === 'Open'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets | Flexi Feet Admin</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <link rel="stylesheet" href="flexi-admin.css">
</head>
<body class="wp-admin">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="wp-main">
        <div class="wp-topbar">
            <div>
                <h1>Support Tickets</h1>
                <p><?= count($tickets) ?> total, <?= $open ?> open</p>
            </div>
        </div>
        <div class="wp-panel">
            <table class="wp-table">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Contact</th>
                        <th>Message</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td>
                                <strong><?= e($ticket['subject'] ?: $ticket['id']) ?></strong>
                                <div class="row-actions"><?= e($ticket['id']) ?> • <?= e($ticket['type']) ?> • <?= e($ticket['created_at']) ?></div>
                            </td>
                            <td><?= e($ticket['name']) ?><br><small><?= e($ticket['email']) ?> <?= e($ticket['phone']) ?></small></td>
                            <td><?= nl2br(e($ticket['message'])) ?></td>
                            <td>
                                <select class="action-select" onchange="updateTicketStatus('<?= e($ticket['id']) ?>', this.value)">
                                    <?php foreach (['Open', 'In Progress', 'Closed'] as $status): ?>
                                        <option value="<?= e($status) ?>" <?= $ticket['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="4" class="empty-cell">No support tickets yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <script>
        async function updateTicketStatus(id, status) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', status);
            formData.append('csrf', '<?= $csrf ?>');
            const response = await fetch('../api/tickets.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (!result.ok) alert(result.message || 'Update failed');
        }
    </script>
</body>
</html>
