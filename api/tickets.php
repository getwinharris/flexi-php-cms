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

if (!verify_csrf($_POST['csrf'] ?? null)) {
    echo json_encode(['ok' => false, 'message' => 'Security token expired.']);
    exit;
}

if (update_support_ticket_status(normalize_text($_POST['id'] ?? '', 80), normalize_text($_POST['status'] ?? '', 40))) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'message' => 'Update failed']);
}
