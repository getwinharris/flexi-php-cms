<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    start_app_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(?string $token): bool
{
    start_app_session();
    return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function storage_bootstrap(): void
{
    if (!is_dir(STORAGE_DIR)) {
        mkdir(STORAGE_DIR, 0755, true);
    }

    if (!file_exists(APPOINTMENTS_FILE)) {
        file_put_contents(APPOINTMENTS_FILE, json_encode([
            'appointments' => [],
            'updated_at' => gmdate(DATE_ATOM),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

function normalize_text(?string $value, int $maxLength = 300): string
{
    $value = trim((string) $value);
    return mb_substr($value, 0, $maxLength);
}

function read_appointments(): array
{
    storage_bootstrap();
    $raw = file_get_contents(APPOINTMENTS_FILE);
    $data = json_decode($raw, true);
    return $data['appointments'] ?? [];
}

function save_appointments(array $appointments): void
{
    storage_bootstrap();
    $data = [
        'appointments' => $appointments,
        'updated_at' => gmdate(DATE_ATOM),
    ];
    file_put_contents(APPOINTMENTS_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function create_appointment(array $payload): array
{
    $appointments = read_appointments();
    $id = 'FF-' . date('Ymd') . '-' . str_pad((string)(count($appointments) + 1), 4, '0', STR_PAD_LEFT);
    $appointment = array_merge(['id' => $id, 'status' => 'New', 'created_at' => date('Y-m-d H:i:s')], $payload);
    array_unshift($appointments, $appointment);
    save_appointments($appointments);
    return $appointment;
}

function update_appointment_status(string $id, string $status): bool
{
    $appointments = read_appointments();
    foreach ($appointments as &$a) {
        if ($a['id'] === $id) {
            $a['status'] = $status;
            save_appointments($appointments);
            return true;
        }
    }
    return false;
}

function is_admin(): bool
{
    start_app_session();
    return ($_SESSION['admin'] ?? false) === true;
}

function require_admin(): void
{
    if (!is_admin()) {
        header('Location: login.php');
        exit;
    }
}
