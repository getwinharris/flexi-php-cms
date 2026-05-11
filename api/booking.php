<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!verify_csrf($_POST['csrf'] ?? null)) {
    echo json_encode(['ok' => false, 'message' => 'Security token expired. Please refresh.']);
    exit;
}

$payload = [
    'name' => normalize_text($_POST['name'] ?? '', 100),
    'phone' => normalize_text($_POST['phone'] ?? '', 40),
    'email' => normalize_text($_POST['email'] ?? '', 100),
    'preferred_date' => normalize_text($_POST['preferred_date'] ?? '', 20),
    'preferred_time' => normalize_text($_POST['preferred_time'] ?? '', 20),
    'visit_type' => normalize_text($_POST['visit_type'] ?? '', 100),
    'notes' => normalize_text($_POST['notes'] ?? '', 1000),
];

if (empty($payload['name']) || empty($payload['phone']) || empty($payload['preferred_date'])) {
    echo json_encode(['ok' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

try {
    $appointment = create_appointment($payload);
    echo json_encode(['ok' => true, 'message' => 'Success', 'appointment_id' => $appointment['id']]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Storage error.']);
}
