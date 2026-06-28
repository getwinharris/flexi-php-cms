<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Invalid request method.']);
    exit;
}

$action = normalize_text($_POST['action'] ?? 'message', 40);

if ($action === 'message') {
    $message = normalize_text($_POST['message'] ?? '', 1000);
    if ($message === '') {
        echo json_encode(['ok' => false, 'message' => 'Please enter a message.']);
        exit;
    }
    echo json_encode(['ok' => true] + support_bot_reply($message), JSON_UNESCAPED_SLASHES);
    exit;
}

if ($action === 'booking') {
    $payload = [
        'name' => normalize_text($_POST['name'] ?? '', 100),
        'phone' => normalize_text($_POST['phone'] ?? '', 40),
        'email' => normalize_text($_POST['email'] ?? '', 100),
        'preferred_date' => normalize_text($_POST['preferred_date'] ?? '', 20),
        'preferred_time' => normalize_text($_POST['preferred_time'] ?? '', 20),
        'visit_type' => normalize_text($_POST['visit_type'] ?? 'Foot Assessment', 100),
        'notes' => normalize_text($_POST['notes'] ?? 'Booked through support bot.', 1000),
    ];
    if ($payload['name'] === '' || $payload['phone'] === '' || $payload['email'] === '' || $payload['preferred_date'] === '' || $payload['preferred_time'] === '') {
        echo json_encode(['ok' => false, 'message' => 'Name, phone, email, preferred date and preferred time are required.']);
        exit;
    }
    if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }
    $appointment = create_appointment($payload);
    $mail = notify_booking_emails($appointment);
    echo json_encode(['ok' => true, 'message' => 'Appointment request created.', 'appointment_id' => $appointment['id'], 'mail' => $mail], JSON_UNESCAPED_SLASHES);
    exit;
}

if ($action === 'ticket') {
    $ticket = create_support_ticket([
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'type' => $_POST['type'] ?? 'Issue',
        'subject' => $_POST['subject'] ?? '',
        'message' => $_POST['message'] ?? '',
    ]);
    echo json_encode(['ok' => true, 'message' => 'Support ticket created.', 'ticket_id' => $ticket['id']], JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode(['ok' => false, 'message' => 'Unknown support action.']);
