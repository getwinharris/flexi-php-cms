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

    if (!file_exists(SUPPORT_TICKETS_FILE)) {
        file_put_contents(SUPPORT_TICKETS_FILE, json_encode([
            'tickets' => [],
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

function read_support_tickets(): array
{
    $tickets = read_json_file(SUPPORT_TICKETS_FILE, 'tickets');
    usort($tickets, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
    return $tickets;
}

function save_support_tickets(array $tickets): void
{
    save_json_file(SUPPORT_TICKETS_FILE, 'tickets', $tickets);
}

function create_support_ticket(array $payload): array
{
    $tickets = read_support_tickets();
    $ticket = [
        'id' => 'TICKET-' . date('Ymd') . '-' . str_pad((string) (count($tickets) + 1), 4, '0', STR_PAD_LEFT),
        'name' => normalize_text($payload['name'] ?? '', 100),
        'email' => normalize_text($payload['email'] ?? '', 120),
        'phone' => normalize_text($payload['phone'] ?? '', 40),
        'type' => normalize_text($payload['type'] ?? 'Issue', 60),
        'subject' => normalize_text($payload['subject'] ?? '', 160),
        'message' => normalize_text($payload['message'] ?? '', 1200),
        'status' => 'Open',
        'created_at' => date('Y-m-d H:i:s'),
    ];
    array_unshift($tickets, $ticket);
    save_support_tickets($tickets);
    return $ticket;
}

function update_support_ticket_status(string $id, string $status): bool
{
    $allowed = ['Open', 'In Progress', 'Closed'];
    if (!in_array($status, $allowed, true)) {
        return false;
    }
    $tickets = read_support_tickets();
    foreach ($tickets as &$ticket) {
        if (($ticket['id'] ?? '') === $id) {
            $ticket['status'] = $status;
            save_support_tickets($tickets);
            return true;
        }
    }
    return false;
}

function support_service_suggestions(string $message): array
{
    $message = strtolower($message);
    $posts = read_blog_posts(true);
    $matches = [];
    $topics = [
        'diabetic' => ['diabetic', 'neuropathy', 'ulcer', 'sugar', 'diabetes'],
        'insole' => ['insole', 'offload', 'orthotic', 'pressure'],
        'flat' => ['flat feet', 'arch', 'flatfoot'],
        'sock' => ['sock', 'socks'],
        'scan' => ['scan', '3d', 'assessment'],
        'amputation' => ['amputation', 'partial foot'],
        'charcot' => ['charcot'],
        'bunion' => ['bunion', 'hammer', 'wide'],
    ];
    foreach ($topics as $topic => $needles) {
        foreach ($needles as $needle) {
            if (strpos($message, $needle) !== false) {
                foreach ($posts as $post) {
                    $haystack = strtolower(($post['title'] ?? '') . ' ' . ($post['excerpt'] ?? '') . ' ' . ($post['slug'] ?? ''));
                    if (strpos($haystack, $topic) !== false || strpos($haystack, $needle) !== false) {
                        $matches[] = ['title' => $post['title'], 'url' => 'blog-post.php?slug=' . $post['slug']];
                        break 2;
                    }
                }
            }
        }
    }
    return array_slice($matches, 0, 3);
}

function support_bot_reply(string $message): array
{
    $text = strtolower($message);
    $bugWords = ['bug', 'issue', 'error', 'broken', 'not working', 'problem', 'complaint', 'wrong'];
    foreach ($bugWords as $word) {
        if (strpos($text, $word) !== false) {
            return [
                'intent' => 'ticket',
                'reply' => 'I can create a support ticket for this issue. Please share your name, email or phone, and what happened.',
            ];
        }
    }
    if (preg_match('/book|appointment|visit|fitting|consultation|schedule/', $text)) {
        return [
            'intent' => 'booking',
            'reply' => 'I can help book an appointment. Please provide your name, phone, email, preferred date, preferred time, and visit type.',
        ];
    }
    $serviceWords = ['service', 'shoe', 'diabetic', 'insole', 'sock', 'scan', 'flat', 'ulcer', 'charcot', 'bunion', 'amputation', 'foot', 'orthopaedic', 'orthotic'];
    foreach ($serviceWords as $word) {
        if (strpos($text, $word) !== false) {
            $suggestions = support_service_suggestions($message);
            return [
                'intent' => 'service',
                'reply' => 'Flexi Feet helps with custom diabetic shoes, custom offload insoles, flat feet insoles, diabetic socks, and 3D foot assessment. I can only answer Flexi Feet service questions or help with bookings and support tickets.',
                'suggestions' => $suggestions,
            ];
        }
    }
    return [
        'intent' => 'out_of_scope',
        'reply' => 'I can only help with Flexi Feet services, appointment booking, or support tickets. For other topics, please contact the team directly.',
    ];
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
        'seo_title' => normalize_text($payload['seo_title'] ?? '', 180),
        'seo_description' => normalize_text($payload['seo_description'] ?? '', 320),
        'focus_keyword' => normalize_text($payload['focus_keyword'] ?? '', 120),
        'canonical_url' => normalize_text($payload['canonical_url'] ?? '', 300),
        'social_image' => normalize_text($payload['social_image'] ?? '', 300),
        'noindex' => !empty($payload['noindex']) ? '1' : '',
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

function is_social_reel_url(string $url): bool
{
    if (is_instagram_url($url)) {
        return true;
    }
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    return in_array($host, ['youtube.com', 'www.youtube.com', 'youtu.be'], true);
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

function absolute_url(string $path = ''): string
{
    if ($path === '') {
        return rtrim(SITE_URL, '/');
    }
    if (preg_match('/^https?:\/\//', $path)) {
        return $path;
    }
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

function admin_media_src(string $path): string
{
    if (preg_match('/^https?:\/\//', $path)) {
        return $path;
    }
    return '../' . ltrim($path, '/');
}

function page_description(string $value, int $maxLength = 160): string
{
    $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');
    return mb_substr($value, 0, $maxLength);
}

function seo_field(array $source, string $key, string $fallback = ''): string
{
    $value = trim((string) ($source[$key] ?? ''));
    return $value !== '' ? $value : $fallback;
}

function post_seo_title(array $post): string
{
    return seo_field($post, 'seo_title', ($post['title'] ?? 'Flexi Feet Blog') . ' | Flexi Feet');
}

function post_seo_description(array $post): string
{
    return page_description(seo_field($post, 'seo_description', $post['excerpt'] ?? ($post['content'] ?? '')));
}

function post_social_image(array $post): string
{
    return seo_field($post, 'social_image', seo_field($post, 'featured_image', DEFAULT_SOCIAL_IMAGE));
}

function post_canonical_path(array $post): string
{
    return seo_field($post, 'canonical_url', 'blog-post.php?slug=' . ($post['slug'] ?? ''));
}

function post_noindex(array $post): bool
{
    return (($post['noindex'] ?? '') === '1' || ($post['noindex'] ?? false) === true);
}

function seo_score_post(array $post): array
{
    $checks = [
        'title' => trim((string) ($post['title'] ?? '')) !== '',
        'slug' => trim((string) ($post['slug'] ?? '')) !== '',
        'meta' => mb_strlen(post_seo_description($post)) >= 80,
        'image' => post_social_image($post) !== '',
        'content' => mb_strlen(strip_tags((string) ($post['content'] ?? ''))) >= 400,
        'published' => ($post['status'] ?? '') === 'Published',
        'indexable' => !post_noindex($post),
    ];
    $passed = count(array_filter($checks));
    return [
        'passed' => $passed,
        'total' => count($checks),
        'percent' => (int) round(($passed / max(1, count($checks))) * 100),
        'checks' => $checks,
    ];
}

function render_google_verification(): void
{
    if (GOOGLE_SITE_VERIFICATION !== '') {
        echo '    <meta name="google-site-verification" content="' . e(GOOGLE_SITE_VERIFICATION) . "\">\n";
    }
}

function render_seo_tags(string $title, string $description, string $canonicalPath = '', string $image = 'assets/images/flexi-feet-logo.png', string $type = 'website'): void
{
    $canonical = absolute_url($canonicalPath);
    $imageUrl = absolute_url($image);
    echo '<title>' . e($title) . "</title>\n";
    echo '    <meta name="description" content="' . e($description) . "\">\n";
    echo '    <link rel="canonical" href="' . e($canonical) . "\">\n";
    render_google_verification();
    echo '    <meta property="og:title" content="' . e($title) . "\">\n";
    echo '    <meta property="og:description" content="' . e($description) . "\">\n";
    echo '    <meta property="og:image" content="' . e($imageUrl) . "\">\n";
    echo '    <meta property="og:url" content="' . e($canonical) . "\">\n";
    echo '    <meta property="og:type" content="' . e($type) . "\">\n";
    echo '    <meta name="twitter:card" content="summary_large_image">' . "\n";
}

function render_json_ld(array $data): void
{
    echo '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

function render_google_analytics(): void
{
    if (GA_MEASUREMENT_ID === '') {
        return;
    }
    $id = e(GA_MEASUREMENT_ID);
    echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $id . '"></script>' . "\n";
    echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','" . $id . "');</script>\n";
}

function render_google_adsense(): void
{
    if (GOOGLE_ADSENSE_CLIENT_ID === '') {
        return;
    }
    echo '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . e(GOOGLE_ADSENSE_CLIENT_ID) . '" crossorigin="anonymous"></script>' . "\n";
}

function base64url_encode_string(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function google_service_account_ready(): bool
{
    return GOOGLE_SERVICE_ACCOUNT_EMAIL !== '' && GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY !== '';
}

function google_http_post(string $url, array $headers, string $body): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers) . "\r\n",
            'content' => $body,
            'timeout' => 30,
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    $status = 0;
    foreach (($http_response_header ?? []) as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
            $status = (int) $matches[1];
            break;
        }
    }
    $data = json_decode((string) $raw, true);
    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'data' => is_array($data) ? $data : [],
        'raw' => (string) $raw,
    ];
}

function google_http_get(string $url): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 30,
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    $status = 0;
    foreach (($http_response_header ?? []) as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
            $status = (int) $matches[1];
            break;
        }
    }
    $data = json_decode((string) $raw, true);
    return [
        'ok' => $status >= 200 && $status < 300 && is_array($data),
        'status' => $status,
        'data' => is_array($data) ? $data : [],
        'raw' => (string) $raw,
    ];
}

function google_service_account_token(array $scopes): array
{
    if (!google_service_account_ready()) {
        return ['ok' => false, 'message' => 'Add a Google service account email and private key in Settings.'];
    }
    if (!function_exists('openssl_sign')) {
        return ['ok' => false, 'message' => 'OpenSSL is not enabled on this hosting account.'];
    }

    $now = time();
    $header = base64url_encode_string(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_UNESCAPED_SLASHES));
    $claim = base64url_encode_string(json_encode([
        'iss' => GOOGLE_SERVICE_ACCOUNT_EMAIL,
        'scope' => implode(' ', $scopes),
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
    ], JSON_UNESCAPED_SLASHES));
    $unsigned = $header . '.' . $claim;
    $privateKey = str_replace('\\n', "\n", GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY);
    $signed = openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    if (!$signed) {
        return ['ok' => false, 'message' => 'Google private key could not sign the request.'];
    }

    $response = google_http_post(
        'https://oauth2.googleapis.com/token',
        ['Content-Type: application/x-www-form-urlencoded'],
        http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $unsigned . '.' . base64url_encode_string($signature),
        ])
    );
    if (!$response['ok']) {
        return ['ok' => false, 'message' => $response['data']['error_description'] ?? 'Google token request failed.'];
    }

    return ['ok' => true, 'token' => (string) ($response['data']['access_token'] ?? '')];
}

function google_api_json_post(string $url, array $payload, array $scopes): array
{
    $token = google_service_account_token($scopes);
    if (!$token['ok']) {
        return $token;
    }
    $response = google_http_post(
        $url,
        [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token['token'],
        ],
        json_encode($payload, JSON_UNESCAPED_SLASHES)
    );
    if (!$response['ok']) {
        $message = $response['data']['error']['message'] ?? 'Google API request failed.';
        return ['ok' => false, 'message' => $message, 'status' => $response['status']];
    }
    return ['ok' => true, 'data' => $response['data']];
}

function google_seo_report(bool $force = false): array
{
    $cacheFile = STORAGE_DIR . '/google-seo-cache.json';
    if (!$force && is_file($cacheFile) && (time() - filemtime($cacheFile)) < 21600) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            $cached['cached'] = true;
            return $cached;
        }
    }

    $report = [
        'ok' => google_service_account_ready(),
        'cached' => false,
        'message' => google_service_account_ready() ? '' : 'Connect Google in Settings to fetch Analytics and Search Console data.',
        'ga4' => ['ok' => false, 'metrics' => [], 'pages' => [], 'message' => 'GA4 Property ID is not configured.'],
        'search_console' => ['ok' => false, 'metrics' => [], 'queries' => [], 'pages' => [], 'message' => 'Search Console Site URL is not configured.'],
    ];
    if (!$report['ok']) {
        return $report;
    }

    $endDate = date('Y-m-d', strtotime('-3 days'));
    $startDate = date('Y-m-d', strtotime('-31 days'));

    if (GA4_PROPERTY_ID !== '') {
        $ga = google_api_json_post(
            'https://analyticsdata.googleapis.com/v1beta/properties/' . rawurlencode(GA4_PROPERTY_ID) . ':runReport',
            [
                'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
                'dimensions' => [['name' => 'pagePath']],
                'metrics' => [['name' => 'activeUsers'], ['name' => 'sessions'], ['name' => 'screenPageViews']],
                'orderBys' => [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]],
                'limit' => 8,
            ],
            ['https://www.googleapis.com/auth/analytics.readonly']
        );
        if ($ga['ok']) {
            $totals = $ga['data']['totals'][0]['metricValues'] ?? [];
            $report['ga4'] = [
                'ok' => true,
                'metrics' => [
                    'activeUsers' => (int) ($totals[0]['value'] ?? 0),
                    'sessions' => (int) ($totals[1]['value'] ?? 0),
                    'views' => (int) ($totals[2]['value'] ?? 0),
                ],
                'pages' => array_map(fn($row) => [
                    'path' => $row['dimensionValues'][0]['value'] ?? '',
                    'users' => (int) ($row['metricValues'][0]['value'] ?? 0),
                    'sessions' => (int) ($row['metricValues'][1]['value'] ?? 0),
                    'views' => (int) ($row['metricValues'][2]['value'] ?? 0),
                ], $ga['data']['rows'] ?? []),
                'message' => '',
            ];
        } else {
            $report['ga4']['message'] = $ga['message'];
        }
    }

    if (SEARCH_CONSOLE_SITE_URL !== '') {
        $siteUrl = rtrim(SEARCH_CONSOLE_SITE_URL, '/') . '/';
        $queryPayload = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dimensions' => ['query'],
            'rowLimit' => 8,
        ];
        $pagePayload = $queryPayload;
        $pagePayload['dimensions'] = ['page'];
        $queryData = google_api_json_post(
            'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($siteUrl) . '/searchAnalytics/query',
            $queryPayload,
            ['https://www.googleapis.com/auth/webmasters.readonly']
        );
        $pageData = google_api_json_post(
            'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($siteUrl) . '/searchAnalytics/query',
            $pagePayload,
            ['https://www.googleapis.com/auth/webmasters.readonly']
        );
        if ($queryData['ok']) {
            $rows = $queryData['data']['rows'] ?? [];
            $clicks = array_sum(array_map(fn($row) => (float) ($row['clicks'] ?? 0), $rows));
            $impressions = array_sum(array_map(fn($row) => (float) ($row['impressions'] ?? 0), $rows));
            $report['search_console'] = [
                'ok' => true,
                'metrics' => [
                    'clicks' => (int) $clicks,
                    'impressions' => (int) $impressions,
                    'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 1) : 0,
                    'position' => count($rows) ? round(array_sum(array_map(fn($row) => (float) ($row['position'] ?? 0), $rows)) / count($rows), 1) : 0,
                ],
                'queries' => array_map(fn($row) => [
                    'label' => $row['keys'][0] ?? '',
                    'clicks' => (int) ($row['clicks'] ?? 0),
                    'impressions' => (int) ($row['impressions'] ?? 0),
                    'position' => round((float) ($row['position'] ?? 0), 1),
                ], $rows),
                'pages' => array_map(fn($row) => [
                    'label' => $row['keys'][0] ?? '',
                    'clicks' => (int) ($row['clicks'] ?? 0),
                    'impressions' => (int) ($row['impressions'] ?? 0),
                    'position' => round((float) ($row['position'] ?? 0), 1),
                ], ($pageData['data']['rows'] ?? [])),
                'message' => '',
            ];
        } else {
            $report['search_console']['message'] = $queryData['message'];
        }
    }

    if ($report['ga4']['ok'] || $report['search_console']['ok']) {
        file_put_contents($cacheFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
    return $report;
}

function google_pagespeed_report(bool $force = false): array
{
    $cacheFile = STORAGE_DIR . '/google-pagespeed-cache.json';
    if (!$force && is_file($cacheFile) && (time() - filemtime($cacheFile)) < 21600) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            $cached['cached'] = true;
            return $cached;
        }
    }

    $report = [
        'ok' => false,
        'cached' => false,
        'message' => 'PageSpeed Insights has not returned data yet.',
        'strategies' => [],
    ];
    foreach (['mobile', 'desktop'] as $strategy) {
        $url = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=' . rawurlencode(SITE_URL) . '&strategy=' . $strategy . '&category=performance&category=seo&category=accessibility&category=best-practices';
        if (GOOGLE_PAGESPEED_API_KEY !== '') {
            $url .= '&key=' . rawurlencode(GOOGLE_PAGESPEED_API_KEY);
        }
        $response = google_http_get($url);
        if (!$response['ok']) {
            $report['strategies'][$strategy] = [
                'ok' => false,
                'message' => $response['data']['error']['message'] ?? 'PageSpeed request failed.',
            ];
            continue;
        }
        $categories = $response['data']['lighthouseResult']['categories'] ?? [];
        $report['strategies'][$strategy] = [
            'ok' => true,
            'performance' => isset($categories['performance']['score']) ? (int) round($categories['performance']['score'] * 100) : null,
            'seo' => isset($categories['seo']['score']) ? (int) round($categories['seo']['score'] * 100) : null,
            'accessibility' => isset($categories['accessibility']['score']) ? (int) round($categories['accessibility']['score'] * 100) : null,
            'best_practices' => isset($categories['best-practices']['score']) ? (int) round($categories['best-practices']['score'] * 100) : null,
            'message' => '',
        ];
    }

    $report['ok'] = count(array_filter($report['strategies'], fn($item) => $item['ok'] ?? false)) > 0;
    $report['message'] = $report['ok'] ? '' : 'Connect internet/API access or add a PageSpeed API key if quota is limited.';
    if ($report['ok']) {
        file_put_contents($cacheFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
    return $report;
}

function google_connection_checks(array $seoReport, array $pagespeedReport): array
{
    return [
        ['label' => 'Search Console verification tag', 'ok' => GOOGLE_SITE_VERIFICATION !== '', 'detail' => GOOGLE_SITE_VERIFICATION !== '' ? 'Meta verification is configured.' : 'Paste the verification content value.'],
        ['label' => 'Analytics web tag', 'ok' => GA_MEASUREMENT_ID !== '', 'detail' => GA_MEASUREMENT_ID !== '' ? GA_MEASUREMENT_ID : 'Add the GA4 Measurement ID.'],
        ['label' => 'AdSense publisher tag', 'ok' => GOOGLE_ADSENSE_CLIENT_ID !== '', 'detail' => GOOGLE_ADSENSE_CLIENT_ID !== '' ? GOOGLE_ADSENSE_CLIENT_ID : 'Add the AdSense Publisher Client ID.'],
        ['label' => 'Service account', 'ok' => google_service_account_ready(), 'detail' => google_service_account_ready() ? 'Private reporting auth is configured.' : 'Add service account email and private key.'],
        ['label' => 'GA4 reporting', 'ok' => $seoReport['ga4']['ok'] ?? false, 'detail' => ($seoReport['ga4']['ok'] ?? false) ? 'Analytics data can be fetched.' : ($seoReport['ga4']['message'] ?? 'GA4 not connected.')],
        ['label' => 'Search Console reporting', 'ok' => $seoReport['search_console']['ok'] ?? false, 'detail' => ($seoReport['search_console']['ok'] ?? false) ? 'Search data can be fetched.' : ($seoReport['search_console']['message'] ?? 'Search Console not connected.')],
        ['label' => 'Sitemap', 'ok' => is_file(__DIR__ . '/../sitemap.php'), 'detail' => absolute_url('sitemap.php')],
        ['label' => 'Robots file', 'ok' => is_file(__DIR__ . '/../robots.txt'), 'detail' => absolute_url('robots.txt')],
        ['label' => 'PageSpeed Insights', 'ok' => $pagespeedReport['ok'] ?? false, 'detail' => ($pagespeedReport['ok'] ?? false) ? 'Performance report is available.' : ($pagespeedReport['message'] ?? 'PageSpeed not ready.')],
    ];
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
        'GA_MEASUREMENT_ID' => GA_MEASUREMENT_ID,
        'GOOGLE_SITE_VERIFICATION' => GOOGLE_SITE_VERIFICATION,
        'DEFAULT_SEO_TITLE' => DEFAULT_SEO_TITLE,
        'DEFAULT_SEO_DESCRIPTION' => DEFAULT_SEO_DESCRIPTION,
        'DEFAULT_SOCIAL_IMAGE' => DEFAULT_SOCIAL_IMAGE,
        'GOOGLE_SERVICE_ACCOUNT_EMAIL' => GOOGLE_SERVICE_ACCOUNT_EMAIL,
        'GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY_SET' => GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY !== '',
        'GA4_PROPERTY_ID' => GA4_PROPERTY_ID,
        'SEARCH_CONSOLE_SITE_URL' => SEARCH_CONSOLE_SITE_URL,
        'GOOGLE_ADSENSE_CLIENT_ID' => GOOGLE_ADSENSE_CLIENT_ID,
        'GOOGLE_PAGESPEED_API_KEY_SET' => GOOGLE_PAGESPEED_API_KEY !== '',
        'GOOGLE_AI_API_KEY_SET' => GOOGLE_AI_API_KEY !== '',
        'GOOGLE_AI_MODEL' => GOOGLE_AI_MODEL,
        'AUTOMATION_TOKEN_SET' => AUTOMATION_TOKEN !== '',
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
        'GA_MEASUREMENT_ID' => normalize_text($payload['GA_MEASUREMENT_ID'] ?? '', 40),
        'GOOGLE_SITE_VERIFICATION' => normalize_text($payload['GOOGLE_SITE_VERIFICATION'] ?? '', 220),
        'DEFAULT_SEO_TITLE' => normalize_text($payload['DEFAULT_SEO_TITLE'] ?? DEFAULT_SEO_TITLE, 180),
        'DEFAULT_SEO_DESCRIPTION' => normalize_text($payload['DEFAULT_SEO_DESCRIPTION'] ?? DEFAULT_SEO_DESCRIPTION, 320),
        'DEFAULT_SOCIAL_IMAGE' => normalize_text($payload['DEFAULT_SOCIAL_IMAGE'] ?? DEFAULT_SOCIAL_IMAGE, 300),
        'GOOGLE_SERVICE_ACCOUNT_EMAIL' => normalize_text($payload['GOOGLE_SERVICE_ACCOUNT_EMAIL'] ?? '', 220),
        'GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY' => (string) ($payload['GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY'] ?? ''),
        'GA4_PROPERTY_ID' => normalize_text($payload['GA4_PROPERTY_ID'] ?? '', 80),
        'SEARCH_CONSOLE_SITE_URL' => normalize_text($payload['SEARCH_CONSOLE_SITE_URL'] ?? SITE_URL, 220),
        'GOOGLE_ADSENSE_CLIENT_ID' => normalize_text($payload['GOOGLE_ADSENSE_CLIENT_ID'] ?? '', 80),
        'GOOGLE_PAGESPEED_API_KEY' => (string) ($payload['GOOGLE_PAGESPEED_API_KEY'] ?? ''),
        'GOOGLE_AI_API_KEY' => (string) ($payload['GOOGLE_AI_API_KEY'] ?? ''),
        'GOOGLE_AI_MODEL' => normalize_text($payload['GOOGLE_AI_MODEL'] ?? 'gemma-4-31b-it', 80),
        'AUTOMATION_TOKEN' => (string) ($payload['AUTOMATION_TOKEN'] ?? ''),
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
    if ($settings['GOOGLE_AI_API_KEY'] === '') {
        $settings['GOOGLE_AI_API_KEY'] = GOOGLE_AI_API_KEY;
    }
    if ($settings['GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY'] === '') {
        $settings['GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY'] = GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY;
    }
    if ($settings['GOOGLE_PAGESPEED_API_KEY'] === '') {
        $settings['GOOGLE_PAGESPEED_API_KEY'] = GOOGLE_PAGESPEED_API_KEY;
    }
    if ($settings['AUTOMATION_TOKEN'] === '') {
        $settings['AUTOMATION_TOKEN'] = AUTOMATION_TOKEN;
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
    $content .= "define('GA_MEASUREMENT_ID', " . var_export($settings['GA_MEASUREMENT_ID'], true) . ");\n";
    $content .= "define('GOOGLE_SITE_VERIFICATION', " . var_export($settings['GOOGLE_SITE_VERIFICATION'], true) . ");\n";
    $content .= "define('DEFAULT_SEO_TITLE', " . var_export($settings['DEFAULT_SEO_TITLE'], true) . ");\n";
    $content .= "define('DEFAULT_SEO_DESCRIPTION', " . var_export($settings['DEFAULT_SEO_DESCRIPTION'], true) . ");\n";
    $content .= "define('DEFAULT_SOCIAL_IMAGE', " . var_export($settings['DEFAULT_SOCIAL_IMAGE'], true) . ");\n";
    $content .= "define('GOOGLE_SERVICE_ACCOUNT_EMAIL', " . var_export($settings['GOOGLE_SERVICE_ACCOUNT_EMAIL'], true) . ");\n";
    $content .= "define('GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY', " . var_export($settings['GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY'], true) . ");\n";
    $content .= "define('GA4_PROPERTY_ID', " . var_export($settings['GA4_PROPERTY_ID'], true) . ");\n";
    $content .= "define('SEARCH_CONSOLE_SITE_URL', " . var_export($settings['SEARCH_CONSOLE_SITE_URL'], true) . ");\n";
    $content .= "define('GOOGLE_ADSENSE_CLIENT_ID', " . var_export($settings['GOOGLE_ADSENSE_CLIENT_ID'], true) . ");\n";
    $content .= "define('GOOGLE_PAGESPEED_API_KEY', " . var_export($settings['GOOGLE_PAGESPEED_API_KEY'], true) . ");\n";
    $content .= "define('GOOGLE_AI_API_KEY', " . var_export($settings['GOOGLE_AI_API_KEY'], true) . ");\n";
    $content .= "define('GOOGLE_AI_MODEL', " . var_export($settings['GOOGLE_AI_MODEL'], true) . ");\n";
    $content .= "define('AUTOMATION_TOKEN', " . var_export($settings['AUTOMATION_TOKEN'], true) . ");\n";

    if (file_put_contents(CONFIG_LOCAL_FILE, $content, LOCK_EX) === false) {
        return ['ok' => false, 'message' => 'Could not write settings. Check file permissions.'];
    }

    return ['ok' => true, 'message' => 'Settings saved.'];
}

function google_ai_configured(): bool
{
    return GOOGLE_AI_API_KEY !== '' && GOOGLE_AI_MODEL !== '';
}

function generate_ai_blog_prompt(string $topic): string
{
    return "Write a medically careful SEO blog post for Flexi Feet Sdn Bhd in Malaysia about: {$topic}. Include a concise title, slug, meta description, excerpt, and 900-1200 words. Focus on custom diabetic shoes, offload insoles, flat feet insoles, diabetic socks, 3D foot assessment, booking a fitting in Sentul Kuala Lumpur. Do not claim cures. Add a short Reddit/Quora style helpful answer draft for manual review, not automated posting.";
}

function call_google_ai_studio(string $prompt): array
{
    if (!google_ai_configured()) {
        return ['ok' => false, 'message' => 'Google AI Studio API key is not configured.'];
    }
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode(GOOGLE_AI_MODEL) . ':generateContent?key=' . rawurlencode(GOOGLE_AI_API_KEY);
    $payload = json_encode(['contents' => [['parts' => [['text' => $prompt]]]]], JSON_UNESCAPED_SLASHES);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 45,
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    $data = json_decode((string) $raw, true);
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if ($text === '') {
        return ['ok' => false, 'message' => 'Google AI returned no content.', 'raw' => $raw];
    }
    return ['ok' => true, 'text' => $text];
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
