<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_admin()) {
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Invalid method']);
    exit;
}

$id = $_POST['id'] ?? '';
$status = $_POST['status'] ?? '';

if (update_appointment_status($id, $status)) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'message' => 'Update failed']);
}
