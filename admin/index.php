<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$appointments = read_appointments();
$csrf = csrf_token();

// Sort by date created (newest first)
usort($appointments, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

// Basic stats
$total = count($appointments);
$new = count(array_filter($appointments, fn($a) => $a['status'] === 'New'));
$confirmed = count(array_filter($appointments, fn($a) => $a['status'] === 'Confirmed'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Flexi Feet CRM</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        :root {
            --sidebar-width: 260px;
        }
        
        body {
            background: #f8f9fb;
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: var(--logo-navy);
            color: white;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 100;
        }
        
        .sidebar-logo {
            height: 30px;
            margin-bottom: 60px;
            filter: brightness(0) invert(1);
        }
        
        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .nav-item {
            margin-bottom: 8px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: 0.3s;
            font-size: 15px;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-link.active {
            background: var(--logo-cyan);
            color: var(--logo-navy);
            font-weight: 600;
        }
        
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 60px;
        }
        
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        
        .header-bar h1 {
            font-size: 28px;
            color: var(--logo-navy);
            margin: 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--apple-gray-200);
            transition: 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .stat-card .label {
            font-size: 13px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 15px;
            display: block;
        }
        
        .stat-card .value {
            font-size: 36px;
            font-weight: 800;
            color: var(--logo-navy);
        }
        
        .data-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--apple-gray-200);
            overflow: hidden;
        }
        
        .table-header {
            padding: 20px 30px;
            border-bottom: 1px solid var(--apple-gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 18px 30px;
            background: #fafbfc;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--apple-gray-200);
        }
        
        td {
            padding: 20px 30px;
            border-bottom: 1px solid var(--apple-gray-200);
            font-size: 14px;
            vertical-align: middle;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .client-info div:first-child {
            font-weight: 700;
            color: var(--logo-navy);
            margin-bottom: 4px;
        }
        
        .client-info div:last-child {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .status-new { background: #e5f1ff; color: #0066cc; }
        .status-confirmed { background: #eaffef; color: #34c759; }
        .status-completed { background: #f5f5f7; color: #86868b; }
        .status-cancelled { background: #fff2f2; color: #ff3b30; }
        
        .action-select {
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--apple-gray-200);
            font-size: 13px;
            background: white;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .action-select:hover {
            border-color: var(--logo-cyan);
        }
        
        .export-btn {
            background: var(--logo-cyan);
            color: var(--logo-navy);
            padding: 10px 20px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            transition: 0.3s;
        }
        
        .export-btn:hover {
            background: var(--logo-cyan-hover);
            transform: translateY(-2px);
        }
        
        .logout-link {
            margin-top: auto;
            color: rgba(255,255,255,0.5);
            font-size: 14px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            transition: 0.3s;
        }
        
        .logout-link:hover {
            color: white;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <img src="../assets/images/flexi-feet-logo.png" alt="Flexi Feet" class="sidebar-logo">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="#" class="nav-link active">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="#appointments" class="nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Appointments
                </a>
            </li>
            <li class="nav-item">
                <a href="posts.php" class="nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    Posts
                </a>
            </li>
            <li class="nav-item">
                <a href="post-edit.php" class="nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    Add New
                </a>
            </li>
            <li class="nav-item">
                <a href="../" target="_blank" class="nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    View Website
                </a>
            </li>
        </ul>
        <a href="logout.php" class="logout-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </a>
    </aside>

    <main class="main-content">
        <div class="header-bar">
            <h1>Appointments Overview</h1>
            <a href="export.php" class="export-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export CSV
            </a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="label">Total Requests</span>
                <div class="value"><?= $total ?></div>
            </div>
            <div class="stat-card">
                <span class="label">New Requests</span>
                <div class="value" style="color: var(--apple-blue);"><?= $new ?></div>
            </div>
            <div class="stat-card">
                <span class="label">Confirmed</span>
                <div class="value" style="color: #34c759;"><?= $confirmed ?></div>
            </div>
        </div>

        <div class="data-card" id="appointments">
            <div class="table-header">
                <h3 style="margin: 0; font-size: 18px; color: var(--logo-navy);">Latest Submissions</h3>
                <div style="font-size: 13px; color: var(--text-muted);">Showing last <?= count($appointments) ?> entries</div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Client Details</th>
                        <th>Preferred Schedule</th>
                        <th>Visit Type</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $a): ?>
                        <tr>
                            <td>
                                <div class="client-info">
                                    <div><?= e($a['name']) ?></div>
                                    <div><?= e($a['phone']) ?> • <?= e($a['email']) ?></div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--logo-navy);"><?= e($a['preferred_date']) ?></div>
                                <div style="font-size: 12px; color: var(--text-muted);"><?= e($a['preferred_time']) ?></div>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: var(--text);"><?= e($a['visit_type']) ?></span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower($a['status']) ?>">
                                    <?= e($a['status']) ?>
                                </span>
                            </td>
                            <td>
                                <select class="action-select" onchange="updateStatus('<?= $a['id'] ?>', this.value)">
                                    <option value="New" <?= $a['status'] === 'New' ? 'selected' : '' ?>>New</option>
                                    <option value="Confirmed" <?= $a['status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="Completed" <?= $a['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="Cancelled" <?= $a['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 60px; color: var(--text-muted);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 20px; opacity: 0.3;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <p>No appointments found in the system.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        async function updateStatus(id, status) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', status);
            formData.append('csrf', '<?= $csrf ?>');

            try {
                const response = await fetch('../api/appointments.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.ok) {
                    // Smooth reload
                    window.location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (e) {
                alert('Connection failed. Please try again.');
            }
        }
    </script>
</body>
</html>
