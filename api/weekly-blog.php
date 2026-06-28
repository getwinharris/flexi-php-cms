<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$token = $_GET['token'] ?? $_POST['token'] ?? '';
if (AUTOMATION_TOKEN === '' || !hash_equals(AUTOMATION_TOKEN, (string) $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

$topic = normalize_text($_POST['topic'] ?? $_GET['topic'] ?? 'weekly diabetic foot care SEO opportunity for Malaysia', 180);
$result = call_google_ai_studio(generate_ai_blog_prompt($topic));
echo json_encode($result, JSON_UNESCAPED_SLASHES);
