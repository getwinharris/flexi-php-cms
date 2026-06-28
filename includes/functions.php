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

    if (!is_dir(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0755, true);
    }

    if (!file_exists(APPOINTMENTS_FILE)) {
        file_put_contents(APPOINTMENTS_FILE, json_encode([
            'appointments' => [],
            'updated_at' => gmdate(DATE_ATOM),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    if (!file_exists(BLOG_POSTS_FILE)) {
        file_put_contents(BLOG_POSTS_FILE, json_encode([
            'posts' => [],
            'updated_at' => gmdate(DATE_ATOM),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    if (!file_exists(REELS_FILE)) {
        file_put_contents(REELS_FILE, json_encode([
            'reels' => [],
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

function read_json_file(string $file, string $key): array
{
    storage_bootstrap();
    $raw = is_file($file) ? file_get_contents($file) : '';
    $data = json_decode((string) $raw, true);
    if (!is_array($data) || !isset($data[$key]) || !is_array($data[$key])) {
        return [];
    }
    return $data[$key];
}

function save_json_file(string $file, string $key, array $items): void
{
    storage_bootstrap();
    file_put_contents($file, json_encode([
        $key => array_values($items),
        'updated_at' => gmdate(DATE_ATOM),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
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

function read_blog_posts(bool $publishedOnly = false): array
{
    $posts = read_json_file(BLOG_POSTS_FILE, 'posts');
    if ($publishedOnly) {
        $posts = array_values(array_filter($posts, fn($post) => ($post['status'] ?? '') === 'Published'));
    }
    usort($posts, fn($a, $b) => strcmp($b['published_at'] ?? $b['created_at'] ?? '', $a['published_at'] ?? $a['created_at'] ?? ''));
    return $posts;
}

function save_blog_posts(array $posts): void
{
    save_json_file(BLOG_POSTS_FILE, 'posts', $posts);
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');
    return $value !== '' ? $value : 'post';
}

function sanitize_blog_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9-]+/', '-', $value) ?? '';
    $value = preg_replace('/-+/', '-', $value) ?? '';
    return trim($value, '-');
}

function unique_blog_slug(string $title, ?string $currentId = null): string
{
    $base = slugify($title);
    $slug = $base;
    $counter = 2;
    $existingSlugs = [];
    foreach (read_blog_posts(false) as $post) {
        if (($post['id'] ?? '') !== $currentId && !empty($post['slug'])) {
            $existingSlugs[$post['slug']] = true;
        }
    }
    while (isset($existingSlugs[$slug])) {
        $slug = $base . '-' . $counter;
        $counter++;
    }
    return $slug;
}

function find_blog_post(string $idOrSlug, bool $publishedOnly = false): ?array
{
    foreach (read_blog_posts($publishedOnly) as $post) {
        if (($post['id'] ?? '') === $idOrSlug || ($post['slug'] ?? '') === $idOrSlug) {
            return $post;
        }
    }
    return null;
}

function save_blog_post(array $payload, ?string $id = null): array
{
    $posts = read_blog_posts(false);
    $now = date('Y-m-d H:i:s');
    $statusValue = $payload['status'] ?? 'Draft';
    $status = in_array($statusValue, ['Draft', 'Published'], true) ? $statusValue : 'Draft';
    $slugSource = $payload['slug'] ?? ($payload['title'] ?? 'post');
    $post = [
        'id' => $id ?: 'POST-' . date('YmdHis') . '-' . bin2hex(random_bytes(2)),
        'title' => normalize_text($payload['title'] ?? '', 180),
        'slug' => unique_blog_slug($slugSource, $id),
        'excerpt' => normalize_text($payload['excerpt'] ?? '', 300),
        'content' => trim((string) ($payload['content'] ?? '')),
        'status' => $status,
        'featured_image' => normalize_text($payload['featured_image'] ?? '', 300),
        'updated_at' => $now,
    ];

    $existingIndex = null;
    foreach ($posts as $index => $existing) {
        if (($existing['id'] ?? '') === $post['id']) {
            $existingIndex = $index;
            $post['created_at'] = $existing['created_at'] ?? $now;
            $post['published_at'] = $status === 'Published' ? ($existing['published_at'] ?? $now) : '';
            break;
        }
    }

    if ($existingIndex === null) {
        $post['created_at'] = $now;
        $post['published_at'] = $status === 'Published' ? $now : '';
        array_unshift($posts, $post);
    } else {
        $posts[$existingIndex] = $post;
    }

    save_blog_posts($posts);
    return $post;
}

function delete_blog_post(string $id): bool
{
    $posts = read_blog_posts(false);
    $filtered = array_values(array_filter($posts, fn($post) => ($post['id'] ?? '') !== $id));
    if (count($filtered) === count($posts)) {
        return false;
    }
    save_blog_posts($filtered);
    return true;
}

function sanitize_external_url(string $url): string
{
    $url = trim($url);
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
        return '';
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true) ? $url : '';
}

function is_instagram_url(string $url): bool
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    return $host === 'instagram.com' || $host === 'www.instagram.com';
}

function read_reels(bool $activeOnly = false): array
{
    $reels = read_json_file(REELS_FILE, 'reels');
    if ($activeOnly) {
        $reels = array_values(array_filter($reels, fn($reel) => ($reel['status'] ?? '') === 'Active'));
    }
    usort($reels, fn($a, $b) => ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0)));
    return $reels;
}

function find_reel(string $id): ?array
{
    foreach (read_reels(false) as $reel) {
        if (($reel['id'] ?? '') === $id) {
            return $reel;
        }
    }
    return null;
}

function save_reels(array $reels): void
{
    save_json_file(REELS_FILE, 'reels', $reels);
}

function save_reel(array $payload, ?string $id = null): array
{
    $reels = read_reels(false);
    $now = date('Y-m-d H:i:s');
    $status = ($payload['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';
    $reel = [
        'id' => $id ?: 'REEL-' . date('YmdHis') . '-' . bin2hex(random_bytes(2)),
        'title' => normalize_text($payload['title'] ?? '', 140),
        'url' => sanitize_external_url((string) ($payload['url'] ?? '')),
        'thumbnail' => normalize_text($payload['thumbnail'] ?? '', 300),
        'status' => $status,
        'sort_order' => (int) ($payload['sort_order'] ?? (count($reels) + 1)),
        'updated_at' => $now,
    ];

    $existingIndex = null;
    foreach ($reels as $index => $existing) {
        if (($existing['id'] ?? '') === $reel['id']) {
            $existingIndex = $index;
            $reel['created_at'] = $existing['created_at'] ?? $now;
            break;
        }
    }

    if ($existingIndex === null) {
        $reel['created_at'] = $now;
        $reels[] = $reel;
    } else {
        $reels[$existingIndex] = $reel;
    }

    save_reels($reels);
    return $reel;
}

function delete_reel(string $id): bool
{
    $reels = read_reels(false);
    $filtered = array_values(array_filter($reels, fn($reel) => ($reel['id'] ?? '') !== $id));
    if (count($filtered) === count($reels)) {
        return false;
    }
    save_reels($filtered);
    return true;
}

function update_reel_order(array $orderedIds): void
{
    $positions = array_flip($orderedIds);
    $reels = read_reels(false);
    foreach ($reels as &$reel) {
        $id = $reel['id'] ?? '';
        if (isset($positions[$id])) {
            $reel['sort_order'] = $positions[$id] + 1;
        }
    }
    unset($reel);
    save_reels($reels);
}

function handle_media_upload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => ''];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'Upload failed.'];
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return ['ok' => false, 'message' => 'Image must be smaller than 5MB.'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $imageInfo = @getimagesize($tmp);
    $allowed = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_GIF => 'gif',
    ];
    if (!is_array($imageInfo) || !isset($allowed[$imageInfo[2]])) {
        return ['ok' => false, 'message' => 'Only JPG, PNG, WEBP, or GIF images are allowed.'];
    }

    $subdir = date('Y/m');
    $targetDir = UPLOADS_DIR . '/' . $subdir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $baseName = slugify(pathinfo((string) ($file['name'] ?? 'image'), PATHINFO_FILENAME));
    $filename = $baseName . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$imageInfo[2]];
    $target = $targetDir . '/' . $filename;
    if (!move_uploaded_file($tmp, $target)) {
        return ['ok' => false, 'message' => 'Could not save uploaded image.'];
    }

    return ['ok' => true, 'path' => UPLOADS_URL . '/' . $subdir . '/' . $filename];
}

function list_media_files(): array
{
    storage_bootstrap();
    if (!is_dir(UPLOADS_DIR)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(UPLOADS_DIR, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $extension = strtolower($file->getExtension());
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(UPLOADS_DIR) + 1));
        $files[] = [
            'path' => UPLOADS_URL . '/' . $relative,
            'name' => $file->getFilename(),
            'size' => $file->getSize(),
            'modified' => date('Y-m-d H:i:s', $file->getMTime()),
        ];
    }
    usort($files, fn($a, $b) => strcmp($b['modified'], $a['modified']));
    return $files;
}

function render_post_content(string $content): string
{
    $paragraphs = preg_split("/\R{2,}/", trim($content)) ?: [];
    $html = '';
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph !== '') {
            if (preg_match('/^\[image:([^\]]+)\]$/', $paragraph, $matches)) {
                $src = normalize_text($matches[1], 300);
                $html .= '<figure class="blog-inline-image"><img src="' . e($src) . '" alt=""></figure>';
            } else {
                $html .= '<p>' . nl2br(e($paragraph)) . '</p>';
            }
        }
    }
    return $html;
}

function smtp_configured(): bool
{
    return SMTP_PASSWORD !== '' && SMTP_USERNAME !== '' && SMTP_HOST !== '';
}

function smtp_read($socket): string
{
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    return $response;
}

function smtp_command($socket, string $command, array $expectedCodes): string
{
    fwrite($socket, $command . "\r\n");
    $response = smtp_read($socket);
    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('SMTP command failed.');
    }
    return $response;
}

function send_smtp_mail(string $to, string $subject, string $html, string $replyTo = ''): bool
{
    if (!smtp_configured() || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $transport = strtolower(SMTP_ENCRYPTION) === 'ssl' ? 'ssl://' : '';
    $socket = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        return false;
    }

    stream_set_timeout($socket, 20);
    try {
        smtp_read($socket);
        smtp_command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'flexifeet.net'), [250]);
        if (in_array(strtolower(SMTP_ENCRYPTION), ['tls', 'starttls'], true)) {
            smtp_command($socket, 'STARTTLS', [220]);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            smtp_command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'flexifeet.net'), [250]);
        }
        smtp_command($socket, 'AUTH LOGIN', [334]);
        smtp_command($socket, base64_encode(SMTP_USERNAME), [334]);
        smtp_command($socket, base64_encode(SMTP_PASSWORD), [235]);
        smtp_command($socket, 'MAIL FROM:<' . SMTP_FROM_EMAIL . '>', [250]);
        smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtp_command($socket, 'DATA', [354]);

        $headers = [
            'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>',
            'To: <' . $to . '>',
            'Subject: ' . mb_encode_mimeheader($subject, 'UTF-8'),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
        ];
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: <' . $replyTo . '>';
        }

        fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . $html . "\r\n.\r\n");
        $response = smtp_read($socket);
        smtp_command($socket, 'QUIT', [221]);
        fclose($socket);
        return (int) substr($response, 0, 3) === 250;
    } catch (Throwable $e) {
        fclose($socket);
        return false;
    }
}

function booking_email_template(string $heading, array $appointment): string
{
    $rows = '';
    foreach ([
        'Reference' => $appointment['id'] ?? '',
        'Name' => $appointment['name'] ?? '',
        'Phone' => $appointment['phone'] ?? '',
        'Email' => $appointment['email'] ?? '',
        'Preferred Date' => $appointment['preferred_date'] ?? '',
        'Preferred Time' => $appointment['preferred_time'] ?? '',
        'Visit Type' => $appointment['visit_type'] ?? '',
        'Notes' => $appointment['notes'] ?? '',
    ] as $label => $value) {
        if ((string) $value !== '') {
            $rows .= '<tr><th style="text-align:left;padding:8px;border-bottom:1px solid #e8e8ed;color:#1e1b5d;">' . e($label) . '</th><td style="padding:8px;border-bottom:1px solid #e8e8ed;">' . nl2br(e((string) $value)) . '</td></tr>';
        }
    }
    return '<div style="font-family:Arial,sans-serif;color:#1d1d1f;line-height:1.5;"><h2 style="color:#1e1b5d;">' . e($heading) . '</h2><table style="border-collapse:collapse;width:100%;max-width:680px;">' . $rows . '</table></div>';
}

function notify_booking_emails(array $appointment): array
{
    $ownerSent = send_smtp_mail(
        BOOKING_OWNER_EMAIL,
        'New Flexi Feet appointment request ' . ($appointment['id'] ?? ''),
        booking_email_template('New appointment request', $appointment),
        $appointment['email'] ?? ''
    );

    $userSent = false;
    if (!empty($appointment['email']) && filter_var($appointment['email'], FILTER_VALIDATE_EMAIL)) {
        $userSent = send_smtp_mail(
            $appointment['email'],
            'We received your Flexi Feet appointment request',
            booking_email_template('Thank you. We received your appointment request.', $appointment)
        );
    }

    return ['owner' => $ownerSent, 'user' => $userSent];
}

function current_mail_settings(): array
{
    return [
        'SMTP_HOST' => SMTP_HOST,
        'SMTP_PORT' => (string) SMTP_PORT,
        'SMTP_ENCRYPTION' => SMTP_ENCRYPTION,
        'SMTP_USERNAME' => SMTP_USERNAME,
        'SMTP_FROM_EMAIL' => SMTP_FROM_EMAIL,
        'SMTP_FROM_NAME' => SMTP_FROM_NAME,
        'BOOKING_OWNER_EMAIL' => BOOKING_OWNER_EMAIL,
        'SMTP_PASSWORD_SET' => SMTP_PASSWORD !== '',
    ];
}

function save_mail_settings(array $payload): array
{
    $settings = [
        'SMTP_HOST' => normalize_text($payload['SMTP_HOST'] ?? '', 120),
        'SMTP_PORT' => (int) ($payload['SMTP_PORT'] ?? 465),
        'SMTP_ENCRYPTION' => strtolower(normalize_text($payload['SMTP_ENCRYPTION'] ?? 'ssl', 20)),
        'SMTP_USERNAME' => normalize_text($payload['SMTP_USERNAME'] ?? '', 180),
        'SMTP_PASSWORD' => (string) ($payload['SMTP_PASSWORD'] ?? ''),
        'SMTP_FROM_EMAIL' => normalize_text($payload['SMTP_FROM_EMAIL'] ?? '', 180),
        'SMTP_FROM_NAME' => normalize_text($payload['SMTP_FROM_NAME'] ?? BUSINESS_NAME, 180),
        'BOOKING_OWNER_EMAIL' => normalize_text($payload['BOOKING_OWNER_EMAIL'] ?? '', 180),
    ];

    if ($settings['SMTP_HOST'] === '') {
        return ['ok' => false, 'message' => 'SMTP host is required.'];
    }
    if ($settings['SMTP_PORT'] < 1 || $settings['SMTP_PORT'] > 65535) {
        return ['ok' => false, 'message' => 'SMTP port must be between 1 and 65535.'];
    }
    if (!in_array($settings['SMTP_ENCRYPTION'], ['ssl', 'tls', 'starttls'], true)) {
        return ['ok' => false, 'message' => 'Choose SSL, TLS, or STARTTLS.'];
    }
    foreach (['SMTP_USERNAME', 'SMTP_FROM_EMAIL', 'BOOKING_OWNER_EMAIL'] as $emailKey) {
        if (!filter_var($settings[$emailKey], FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => $emailKey . ' must be a valid email address.'];
        }
    }
    if ($settings['SMTP_PASSWORD'] === '') {
        $settings['SMTP_PASSWORD'] = SMTP_PASSWORD;
    }

    $content = "<?php\n";
    $content .= "declare(strict_types=1);\n\n";
    $content .= "define('SMTP_HOST', " . var_export($settings['SMTP_HOST'], true) . ");\n";
    $content .= "define('SMTP_PORT', " . $settings['SMTP_PORT'] . ");\n";
    $content .= "define('SMTP_ENCRYPTION', " . var_export($settings['SMTP_ENCRYPTION'], true) . ");\n";
    $content .= "define('SMTP_USERNAME', " . var_export($settings['SMTP_USERNAME'], true) . ");\n";
    $content .= "define('SMTP_PASSWORD', " . var_export($settings['SMTP_PASSWORD'], true) . ");\n";
    $content .= "define('SMTP_FROM_EMAIL', " . var_export($settings['SMTP_FROM_EMAIL'], true) . ");\n";
    $content .= "define('SMTP_FROM_NAME', " . var_export($settings['SMTP_FROM_NAME'], true) . ");\n";
    $content .= "define('BOOKING_OWNER_EMAIL', " . var_export($settings['BOOKING_OWNER_EMAIL'], true) . ");\n";

    if (file_put_contents(CONFIG_LOCAL_FILE, $content, LOCK_EX) === false) {
        return ['ok' => false, 'message' => 'Could not write SMTP settings. Check file permissions.'];
    }

    return ['ok' => true, 'message' => 'SMTP settings saved.'];
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
