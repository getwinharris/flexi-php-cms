<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$appointments = read_appointments();
$csrf = csrf_token();

usort($appointments, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

$total = count($appointments);
$new = count(array_filter($appointments, fn($appointment) => ($appointment['status'] ?? '') === 'New'));
$confirmed = count(array_filter($appointments, fn($appointment) => ($appointment['status'] ?? '') === 'Confirmed'));
$nextSlots = recommended_appointment_slots(date('Y-m-d'), 8);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Flexi Feet Admin</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <link rel="stylesheet" href="flexi-admin.css">
</head>
<body class="wp-admin">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="wp-main">
        <div class="wp-topbar">
            <div>
                <h1>Appointments</h1>
                <p>Review booking requests and update their status.</p>
            </div>
            <a href="export.php" class="wp-button primary">Export CSV</a>
        </div>

        <div class="seo-dashboard-grid">
            <div class="wp-panel seo-stat-card">
                <span>Total requests</span>
                <strong><?= $total ?></strong>
                <p>All appointment submissions.</p>
            </div>
            <div class="wp-panel seo-stat-card">
                <span>New requests</span>
                <strong><?= $new ?></strong>
                <p>Waiting for review.</p>
            </div>
            <div class="wp-panel seo-stat-card">
                <span>Confirmed</span>
                <strong><?= $confirmed ?></strong>
                <p>Appointments marked confirmed.</p>
            </div>
        </div>

        <section class="wp-panel seo-section-panel">
            <div class="section-heading-row">
                <div>
                    <h2>Next Available Slots</h2>
                    <p>Used by the website support agent and MCP booking endpoint to recommend appointment times.</p>
                </div>
            </div>
            <div class="availability-slot-list">
                <?php foreach ($nextSlots as $slot): ?>
                    <span><?= e($slot['date']) ?> at <?= e($slot['time']) ?></span>
                <?php endforeach; ?>
                <?php if (empty($nextSlots)): ?>
                    <span>No available slots found in the next 21 days.</span>
                <?php endif; ?>
            </div>
        </section>

        <section class="wp-panel seo-section-panel" id="appointments">
            <div class="section-heading-row">
                <div>
                    <h2>Latest Submissions</h2>
                    <p>Showing <?= count($appointments) ?> entries.</p>
                </div>
            </div>
            <table class="wp-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Preferred Schedule</th>
                        <th>Visit Type</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td>
                                <strong><?= e($appointment['name'] ?? '') ?></strong>
                                <div class="row-actions"><?= e($appointment['phone'] ?? '') ?> · <?= e($appointment['email'] ?? '') ?></div>
                            </td>
                            <td>
                                <strong><?= e($appointment['preferred_date'] ?? '') ?></strong>
                                <div class="row-actions"><?= e($appointment['preferred_time'] ?? '') ?></div>
                            </td>
                            <td><?= e($appointment['visit_type'] ?? '') ?></td>
                            <td><span class="wp-status <?= strtolower(e($appointment['status'] ?? '')) ?>"><?= e($appointment['status'] ?? '') ?></span></td>
                            <td>
                                <select class="action-select" data-appointment-id="<?= e($appointment['id'] ?? '') ?>">
                                    <?php foreach (['New', 'Confirmed', 'Completed', 'Cancelled'] as $status): ?>
                                        <option value="<?= e($status) ?>" <?= ($appointment['status'] ?? '') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($appointments)): ?>
                        <tr><td colspan="5" class="empty-cell">No appointments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
    <script>
        document.querySelectorAll('[data-appointment-id]').forEach((select) => {
            select.addEventListener('change', async () => {
                const formData = new FormData();
                formData.append('id', select.dataset.appointmentId);
                formData.append('status', select.value);
                formData.append('csrf', '<?= $csrf ?>');

                try {
                    const response = await fetch('../api/appointments.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.ok) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    alert('Connection failed. Please try again.');
                }
            });
        });
    </script>
</body>
</html>
