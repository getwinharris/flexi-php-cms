<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$appointments = read_appointments();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="flexifeet_appointments_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Status', 'Name', 'Phone', 'Email', 'Date', 'Time', 'Visit Type', 'Notes', 'Created At']);

foreach ($appointments as $a) {
    fputcsv($output, [
        $a['id'],
        $a['status'],
        $a['name'],
        $a['phone'],
        $a['email'],
        $a['preferred_date'],
        $a['preferred_time'],
        $a['visit_type'],
        $a['notes'],
        $a['created_at']
    ]);
}

fclose($output);
exit;
