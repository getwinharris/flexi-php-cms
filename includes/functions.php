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

    if (!file_exists(SUPPORT_FEEDBACK_FILE)) {
        file_put_contents(SUPPORT_FEEDBACK_FILE, json_encode([
            'feedback' => [],
            'updated_at' => gmdate(DATE_ATOM),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    if (!file_exists(SUPPORT_TTT_MEMORY_FILE)) {
        file_put_contents(SUPPORT_TTT_MEMORY_FILE, json_encode([
            'documents' => [],
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

function appointment_hours_for_date(string $date): array
{
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return [];
    }
    $day = (int) date('w', $timestamp);
    if ($day === 0) {
        return [];
    }
    return $day === 6 ? ['09:00', '13:00'] : ['09:00', '18:00'];
}

function appointment_time_slots(string $date): array
{
    $hours = appointment_hours_for_date($date);
    if (empty($hours)) {
        return [];
    }
    [$start, $end] = $hours;
    $slots = [];
    $cursor = strtotime($date . ' ' . $start);
    $limit = strtotime($date . ' ' . $end);
    while ($cursor !== false && $limit !== false && $cursor <= $limit) {
        $slots[] = date('H:i', $cursor);
        $cursor = strtotime('+30 minutes', $cursor);
    }
    return $slots;
}

function booked_appointment_times(string $date): array
{
    $booked = [];
    foreach (read_appointments() as $appointment) {
        $status = $appointment['status'] ?? 'New';
        if ($status === 'Cancelled' || ($appointment['preferred_date'] ?? '') !== $date) {
            continue;
        }
        $time = substr((string) ($appointment['preferred_time'] ?? ''), 0, 5);
        if ($time !== '') {
            $booked[] = $time;
        }
    }
    return array_values(array_unique($booked));
}

function available_appointment_slots(string $date): array
{
    $booked = booked_appointment_times($date);
    return array_values(array_filter(appointment_time_slots($date), function ($slot) use ($booked, $date) {
        if (in_array($slot, $booked, true)) {
            return false;
        }
        if ($date === date('Y-m-d') && $slot <= date('H:i')) {
            return false;
        }
        return true;
    }));
}

function recommended_appointment_slots(?string $date = null, int $limit = 5): array
{
    $date = normalize_text($date ?? '', 20);
    $start = $date !== '' ? strtotime($date) : time();
    if ($start === false) {
        $start = time();
    }
    $results = [];
    for ($offset = 0; $offset < 21 && count($results) < $limit; $offset++) {
        $candidateDate = date('Y-m-d', strtotime('+' . $offset . ' day', $start));
        foreach (available_appointment_slots($candidateDate) as $slot) {
            if ($candidateDate === date('Y-m-d') && $slot <= date('H:i')) {
                continue;
            }
            $results[] = ['date' => $candidateDate, 'time' => $slot];
            if (count($results) >= $limit) {
                break;
            }
        }
    }
    return $results;
}

function appointment_availability_summary(string $date, string $preferredTime = ''): array
{
    $date = normalize_text($date, 20);
    $preferredTime = substr(normalize_text($preferredTime, 20), 0, 5);
    $available = available_appointment_slots($date);
    $isOpen = !empty(appointment_hours_for_date($date));
    return [
        'date' => $date,
        'open' => $isOpen,
        'available_slots' => $available,
        'booked_slots' => booked_appointment_times($date),
        'preferred_available' => $preferredTime !== '' && in_array($preferredTime, $available, true),
        'recommended' => array_slice($available, 0, 5),
    ];
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

function read_support_feedback(): array
{
    return read_json_file(SUPPORT_FEEDBACK_FILE, 'feedback');
}

function save_support_feedback(array $feedback): void
{
    save_json_file(SUPPORT_FEEDBACK_FILE, 'feedback', array_slice($feedback, -500));
}

function read_support_ttt_documents(): array
{
    return array_values(array_filter(read_json_file(SUPPORT_TTT_MEMORY_FILE, 'documents'), function ($document) {
        $text = (string) ($document['text'] ?? '');
        if ($text === '') {
            return false;
        }
        if (preg_match('/Who registered the most sacks|answer_start:|Metadata:|Customer instruction pattern: I cannot use the website form/i', $text)) {
            return false;
        }
        return true;
    }));
}

function save_support_ttt_documents(array $documents): void
{
    save_json_file(SUPPORT_TTT_MEMORY_FILE, 'documents', array_slice($documents, -160));
}

function create_support_feedback(array $payload): array
{
    $rating = normalize_text($payload['rating'] ?? '', 24);
    if (!in_array($rating, ['like', 'dislike'], true)) {
        return ['ok' => false, 'message' => 'Choose like or dislike.'];
    }

    $feedback = read_support_feedback();
    $entry = [
        'id' => 'FB-' . date('YmdHis') . '-' . bin2hex(random_bytes(2)),
        'response_id' => normalize_text($payload['response_id'] ?? '', 80),
        'rating' => $rating,
        'intent' => normalize_text($payload['intent'] ?? '', 40),
        'language' => normalize_text($payload['language'] ?? 'en', 12),
        'query_terms' => array_slice(flexifeet_support_tokenize((string) ($payload['message'] ?? '')), 0, 18),
        'created_at' => date('Y-m-d H:i:s'),
    ];
    $feedback[] = $entry;
    save_support_feedback($feedback);

    if ($rating === 'like') {
        flexifeet_support_store_feedback_memory($entry);
    }

    return ['ok' => true, 'message' => $rating === 'like' ? 'Thanks, I will keep using this style.' : 'Thanks, I will use that signal to improve future answers.'];
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
    $seen = [];
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
                        $url = 'blog-post.php?slug=' . $post['slug'];
                        if (!isset($seen[$url])) {
                            $matches[] = ['title' => $post['title'], 'url' => $url];
                            $seen[$url] = true;
                        }
                        break 2;
                    }
                }
            }
        }
    }
    return array_slice($matches, 0, 3);
}

function flexifeet_support_snippet(string $text, int $maxLength = 260): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    if (mb_strlen($text) <= $maxLength) {
        return $text;
    }
    $snippet = mb_substr($text, 0, $maxLength);
    $snippet = preg_replace('/\s+\S*$/', '', $snippet) ?? $snippet;
    return rtrim($snippet, ' .,;:') . '.';
}

function flexifeet_support_tokenize(string $text): array
{
    $text = mb_strtolower(strip_tags($text), 'UTF-8');
    preg_match_all('/[\p{L}\p{N}]{2,}/u', $text, $matches);
    $stopWords = array_flip([
        'the', 'and', 'for', 'with', 'that', 'this', 'you', 'your', 'are', 'can', 'our', 'from',
        'will', 'have', 'has', 'about', 'what', 'when', 'where', 'how', 'why', 'into', 'using',
        'use', 'not', 'all', 'any', 'please', 'need', 'want', 'does', 'flexi', 'feet',
        'dan', 'yang', 'untuk', 'saya', 'anda', 'boleh', 'dengan', 'apa', 'bagaimana',
    ]);
    $tokens = [];
    foreach ($matches[0] ?? [] as $token) {
        if (!isset($stopWords[$token])) {
            $tokens[] = $token;
        }
    }
    return $tokens;
}

function flexifeet_support_project_text(string $file): string
{
    $path = __DIR__ . '/../' . ltrim($file, '/');
    if (!is_file($path)) {
        return '';
    }
    $text = (string) file_get_contents($path);
    $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $text) ?? $text;
    $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $text) ?? $text;
    $text = preg_replace('/<\?php|\?>|=>|\\$[A-Za-z0-9_]+/', ' ', $text) ?? $text;
    $text = str_replace(['[', ']', '=>', '::'], ' ', $text);
    return trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
}

function flexifeet_support_raw_block_files(): array
{
    $files = [
        'index.md',
        'AGENTS.md',
        'llms.txt',
        'README.md',
        'includes/config.php',
        'includes/functions.php',
        'api/support-bot.php',
        'api/booking.php',
        'mcp.php',
        'index.php',
        'blog.php',
        'blog-post.php',
        'admin/settings.php',
        'admin/reels.php',
        'admin/post-edit.php',
        'admin/media.php',
        'admin/tickets.php',
        'assets/app.js',
        'assets/styles.css',
        'tests/run.php',
        'storage/blog-posts.json',
    ];

    foreach (glob(__DIR__ . '/../model/*.md') ?: [] as $path) {
        $files[] = 'model/' . basename($path);
    }

    return array_values(array_unique(array_filter($files, function ($file) {
        $path = realpath(__DIR__ . '/../' . $file);
        $root = realpath(__DIR__ . '/..');
        return $path !== false && $root !== false && strpos($path, $root) === 0 && is_file($path);
    })));
}

function flexifeet_support_byte_block_documents(): array
{
    $documents = [];
    $blockSize = max(512, min(8192, SUPPORT_BLOCK_BYTES));
    $limit = max(12, min(240, SUPPORT_BLOCK_LIMIT));

    foreach (flexifeet_support_raw_block_files() as $file) {
        $path = __DIR__ . '/../' . $file;
        $raw = (string) file_get_contents($path);
        $byteLength = strlen($raw);
        if ($byteLength === 0) {
            continue;
        }
        for ($offset = 0, $blockIndex = 0; $offset < $byteLength && count($documents) < $limit; $offset += $blockSize, $blockIndex++) {
            $chunk = substr($raw, $offset, $blockSize);
            $text = trim(preg_replace('/\s+/', ' ', strip_tags($chunk)) ?? '');
            if ($text === '' || mb_strlen($text) < 40) {
                continue;
            }
            $hash = substr(hash('sha256', $file . '|' . $offset . '|' . $chunk), 0, 16);
            $documents[] = [
                'id' => 'byte-block-' . slugify($file . '-' . $blockIndex . '-' . $hash),
                'title' => 'Raw byte block: ' . $file . ' #' . $blockIndex,
                'url' => $file,
                'dataset' => 'Raw repository byte block',
                'modality' => 'bytes:text',
                'source_file' => $file,
                'byte_start' => $offset,
                'byte_end' => min($byteLength, $offset + strlen($chunk)),
                'byte_length' => strlen($chunk),
                'byte_hash' => $hash,
                'text' => 'Raw file byte block from ' . $file . ' bytes ' . $offset . '-' . min($byteLength, $offset + strlen($chunk)) . '. Content: ' . normalize_text($text, 1800),
            ];
        }
        if (count($documents) >= $limit) {
            break;
        }
    }

    return $documents;
}

function flexifeet_support_training_documents(): array
{
    $documents = [
        [
            'id' => 'business',
            'title' => BUSINESS_NAME . ' contact and location',
            'url' => './#location',
            'text' => BUSINESS_NAME . ' provides custom footwear, orthopaedic insoles, offload insoles, flat feet insoles, diabetic socks, compression socks, 3D foot scanning, pressure assessment, fittings, follow-ups, and appointment-based service in Sentul, Kuala Lumpur, Malaysia. Phone ' . BUSINESS_PHONE . '. Email ' . BUSINESS_EMAIL . '. Address ' . BUSINESS_ADDRESS . '.',
        ],
        [
            'id' => 'booking',
            'title' => 'Appointment booking',
            'url' => './#booking',
            'text' => 'Appointments are recommended to save time. Customers can request a Foot Assessment, Custom Shoes or Footwear Fitting, Customised Insole Assessment, Pressure Sensor Scan, or Follow-up. The team reviews requests and confirms the slot. Monday to Friday opening hours are 9:00 AM to 6:00 PM. Saturday opening hours are 9:00 AM to 1:00 PM. Sunday is closed unless prior appointment and staff availability.',
        ],
        [
            'id' => 'payment',
            'title' => 'Payment and custom product policy',
            'url' => './#faq',
            'text' => 'Payment methods include card, QR, and account transfer. Payment terms are 50 percent deposit while placing order and balance 50 percent while delivery. Custom products are tailored and do not have standard returns for change of mind. If fit is off, Flexi Feet can help with adjustments or remake according to policy.',
        ],
        [
            'id' => 'agents',
            'title' => 'Agent instructions',
            'url' => 'llms.txt',
            'text' => flexifeet_support_project_text('llms.txt'),
        ],
        [
            'id' => 'homepage',
            'title' => 'Website service content',
            'url' => './',
            'text' => flexifeet_support_project_text('index.php'),
        ],
    ];

    foreach (read_blog_posts(true) as $post) {
        $documents[] = [
            'id' => 'blog-' . ($post['slug'] ?? $post['id'] ?? count($documents)),
            'title' => (string) ($post['title'] ?? 'Flexi Feet blog post'),
            'url' => 'blog-post.php?slug=' . ($post['slug'] ?? ''),
            'text' => trim(($post['title'] ?? '') . ' ' . ($post['excerpt'] ?? '') . ' ' . ($post['content'] ?? '')),
        ];
    }

    return array_merge(
        array_values(array_filter($documents, fn($doc) => trim((string) ($doc['text'] ?? '')) !== '')),
        flexifeet_support_dataset_documents(),
        flexifeet_support_multilingual_documents(),
        flexifeet_support_wikidata_documents(),
        flexifeet_support_local_ttt_documents(),
        flexifeet_support_byte_block_documents(),
        flexifeet_support_remote_hf_documents()
    );
}

function flexifeet_support_seed_dataset_rows(): array
{
    return [
        ['category' => 'FEEDBACK', 'intent' => 'complaint', 'instruction' => 'I have a complaint or something is not working', 'response' => 'Acknowledge the issue, ask for name, phone or email, subject, and details, then create a support ticket.'],
        ['category' => 'FEEDBACK', 'intent' => 'review', 'instruction' => 'I want to leave feedback about the service', 'response' => 'Thank the customer and invite them to share feedback or a support ticket if follow-up is needed.'],
        ['category' => 'CONTACT', 'intent' => 'contact_customer_service', 'instruction' => 'I need to contact a human support agent', 'response' => 'Provide Flexi Feet phone, email, WhatsApp, and appointment booking options.'],
        ['category' => 'ORDER', 'intent' => 'place_order', 'instruction' => 'I want to place an order or book a fitting', 'response' => 'Treat ordering as an appointment request and collect booking type, name, phone, email, date, and available time.'],
        ['category' => 'ORDER', 'intent' => 'change_order', 'instruction' => 'I need to change my appointment or fitting request', 'response' => 'Ask for the appointment reference or contact details and create a support ticket or new booking request.'],
        ['category' => 'ORDER', 'intent' => 'cancel_order', 'instruction' => 'I need to cancel my appointment', 'response' => 'Ask for appointment reference, name, phone, and preferred cancellation details, then route to support ticket.'],
        ['category' => 'PAYMENT', 'intent' => 'check_payment_methods', 'instruction' => 'What payment methods do you accept', 'response' => 'Answer that Flexi Feet accepts card, QR, and account transfer based on the FAQ.'],
        ['category' => 'PAYMENT', 'intent' => 'payment_issue', 'instruction' => 'I have a payment problem', 'response' => 'Ask for safe non-sensitive payment details and create a support ticket; never request card numbers.'],
        ['category' => 'REFUND', 'intent' => 'check_refund_policy', 'instruction' => 'What is the return or refund policy', 'response' => 'Explain custom products do not have standard returns for change of mind and fit issues can be reviewed.'],
        ['category' => 'DELIVERY', 'intent' => 'delivery_period', 'instruction' => 'How long does it take to receive custom diabetic shoes', 'response' => 'Answer that custom-made diabetic shoes usually take 3 to 4 weeks based on the FAQ.'],
        ['category' => 'DELIVERY', 'intent' => 'delivery_options', 'instruction' => 'Do you offer home visits or service outside KL', 'response' => 'Answer home visits may be possible by prior appointment with travel cost inside KL, and monthly travel may include Ipoh and JB Kulai.'],
        ['category' => 'ACCOUNT', 'intent' => 'registration_problems', 'instruction' => 'I cannot use the website form or support bot', 'response' => 'Offer phone, WhatsApp, and support ticket fallback.'],
    ];
}

function flexifeet_support_dataset_documents(int $limit = 400): array
{
    $rows = [];
    $datasetFiles = [
        SUPPORT_TRAINING_DATASET_FILE,
        STORAGE_DIR . '/Bitext_Sample_Customer_Support_Training_Dataset_27K_responses-v11.csv',
    ];
    foreach ($datasetFiles as $file) {
        if (!is_file($file)) {
            continue;
        }
        $handle = fopen($file, 'r');
        if (!$handle) {
            continue;
        }
        $headers = fgetcsv($handle);
        if (!is_array($headers)) {
            fclose($handle);
            continue;
        }
        $headers = array_map(fn($header) => strtolower(trim((string) $header)), $headers);
        while (($row = fgetcsv($handle)) !== false && count($rows) < $limit) {
            $item = [];
            foreach ($headers as $index => $header) {
                $item[$header] = (string) ($row[$index] ?? '');
            }
            if (($item['instruction'] ?? '') !== '' || ($item['response'] ?? '') !== '') {
                $rows[] = $item;
            }
        }
        fclose($handle);
        break;
    }
    if (empty($rows)) {
        $rows = flexifeet_support_seed_dataset_rows();
    }

    $documents = [];
    foreach (array_slice($rows, 0, $limit) as $index => $row) {
        $category = normalize_text($row['category'] ?? 'CUSTOMER_SUPPORT', 80);
        $intent = normalize_text($row['intent'] ?? 'support_intent', 100);
        $instruction = normalize_text($row['instruction'] ?? '', 600);
        $response = normalize_text($row['response'] ?? '', 900);
        if ($instruction === '' && $response === '') {
            continue;
        }
        $documents[] = [
            'id' => 'dataset-' . slugify($category . '-' . $intent . '-' . $index),
            'title' => 'Support dataset: ' . $category . ' / ' . $intent,
            'url' => 'dataset:bitext-customer-support',
            'dataset' => 'Bitext customer support intent pattern',
            'category' => $category,
            'intent' => $intent,
            'text' => 'Customer instruction pattern: ' . $instruction . ' Assistant behavior pattern: ' . $response,
        ];
    }

    return array_values(array_filter($documents, fn($doc) => trim((string) ($doc['text'] ?? '')) !== ''));
}

function flexifeet_support_multilingual_seed_rows(): array
{
    return [
        ['language' => 'ms', 'intent' => 'booking', 'instruction' => 'Saya mahu buat temujanji untuk kasut diabetes dan imbasan kaki 3D', 'response' => 'Bantu pelanggan membuat temujanji Flexi Feet dan minta nama, telefon, emel, tarikh, masa, dan jenis perkhidmatan.'],
        ['language' => 'ms', 'intent' => 'service', 'instruction' => 'Adakah Flexi Feet ada insole ortopedik dan kasut khas untuk kaki diabetes', 'response' => 'Terangkan perkhidmatan kasut diabetes, insole ortopedik, offload insole, stoking diabetes, dan pemeriksaan kaki 3D di Sentul.'],
        ['language' => 'ta', 'intent' => 'booking', 'instruction' => 'நான் நீரிழிவு காலணிகளுக்காக நேரம் பதிவு செய்ய வேண்டும்', 'response' => 'Flexi Feet சந்திப்பை பதிவு செய்ய பெயர், தொலைபேசி, மின்னஞ்சல், தேதி, நேரம், மற்றும் சேவை வகையை கேளுங்கள்.'],
        ['language' => 'ta', 'intent' => 'service', 'instruction' => 'நீரிழிவு காலணிகள் மற்றும் 3D கால் ஸ்கேன் கிடைக்குமா', 'response' => 'Sentul, Kuala Lumpur இல் Flexi Feet custom diabetic shoes, orthopaedic insoles, மற்றும் 3D foot assessment வழங்குகிறது என்று பதிலளிக்கவும்.'],
        ['language' => 'zh', 'intent' => 'booking', 'instruction' => '我想预约糖尿病鞋和3D足部扫描', 'response' => '帮助客户预约 Flexi Feet，并询问姓名、电话、电邮、日期、时间和服务类型。'],
        ['language' => 'zh', 'intent' => 'service', 'instruction' => 'Flexi Feet 有糖尿病鞋和矫形鞋垫吗', 'response' => '说明 Flexi Feet 在吉隆坡 Sentul 提供糖尿病鞋、矫形鞋垫、减压鞋垫、糖尿病袜和3D足部评估。'],
        ['language' => 'hi', 'intent' => 'booking', 'instruction' => 'मुझे डायबिटिक शूज़ और 3D फुट स्कैन के लिए अपॉइंटमेंट चाहिए', 'response' => 'Flexi Feet appointment के लिए नाम, फोन, ईमेल, तारीख, समय और सेवा प्रकार मांगें।'],
        ['language' => 'ar', 'intent' => 'service', 'instruction' => 'هل توفرون أحذية مرضى السكري وفحص القدم ثلاثي الأبعاد', 'response' => 'اشرح أن Flexi Feet تقدم أحذية مخصصة لمرضى السكري، فرشات تقويمية، وجهاز تقييم القدم ثلاثي الأبعاد في سنتول كوالالمبور.'],
        ['language' => 'es', 'intent' => 'service', 'instruction' => 'Tienen zapatos para diabetes y plantillas ortopedicas', 'response' => 'Explique que Flexi Feet ofrece zapatos diabeticos a medida, plantillas ortopedicas, plantillas de descarga, medias diabeticas y evaluacion 3D del pie.'],
        ['language' => 'fr', 'intent' => 'booking', 'instruction' => 'Je veux prendre rendez-vous pour des chaussures diabetiques et un scan 3D du pied', 'response' => 'Aidez le client a demander un rendez-vous Flexi Feet avec nom, telephone, email, date, heure et type de service.'],
    ];
}

function flexifeet_support_multilingual_documents(int $limit = 300): array
{
    $rows = [];
    if (is_file(SUPPORT_MULTILINGUAL_DATASET_FILE)) {
        $handle = fopen(SUPPORT_MULTILINGUAL_DATASET_FILE, 'r');
        if ($handle) {
            $headers = fgetcsv($handle);
            if (is_array($headers)) {
                $headers = array_map(fn($header) => strtolower(trim((string) $header)), $headers);
                while (($row = fgetcsv($handle)) !== false && count($rows) < $limit) {
                    $item = [];
                    foreach ($headers as $index => $header) {
                        $item[$header] = (string) ($row[$index] ?? '');
                    }
                    if (($item['instruction'] ?? '') !== '' || ($item['response'] ?? '') !== '') {
                        $rows[] = $item;
                    }
                }
            }
            fclose($handle);
        }
    }
    if (empty($rows)) {
        $rows = flexifeet_support_multilingual_seed_rows();
    }

    $documents = [];
    foreach (array_slice($rows, 0, $limit) as $index => $row) {
        $language = normalize_text($row['language'] ?? $row['lang'] ?? 'multi', 12);
        $intent = normalize_text($row['intent'] ?? 'multilingual_support', 100);
        $instruction = normalize_text($row['instruction'] ?? $row['question'] ?? $row['query'] ?? '', 700);
        $response = normalize_text($row['response'] ?? $row['answer'] ?? $row['completion'] ?? '', 900);
        if ($instruction === '' && $response === '') {
            continue;
        }
        $documents[] = [
            'id' => 'multilingual-' . slugify($language . '-' . $intent . '-' . $index),
            'title' => 'Multilingual support pattern: ' . strtoupper($language) . ' / ' . $intent,
            'url' => 'dataset:multilingual-support',
            'dataset' => 'Multilingual support and QA pattern',
            'language' => $language,
            'intent' => $intent,
            'text' => 'Language: ' . $language . '. Customer pattern: ' . $instruction . ' Assistant behavior pattern: ' . $response,
        ];
    }
    return array_values(array_filter($documents, fn($doc) => trim((string) ($doc['text'] ?? '')) !== ''));
}

function flexifeet_support_wikidata_seed_rows(): array
{
    return [
        ['id' => 'flexifeet-business', 'label' => 'Flexi Feet Sdn Bhd', 'description' => 'custom diabetic footwear, orthopaedic insoles, offload insoles, diabetic socks, compression socks, 3D foot scanning, and pressure assessment provider in Sentul, Kuala Lumpur, Malaysia', 'aliases' => ['Flexi Feet', 'Flexifeet']],
        ['id' => 'sentul-kl', 'label' => 'Sentul', 'description' => 'district in Kuala Lumpur where Flexi Feet serves appointment-based foot care customers', 'aliases' => ['Sentul Kuala Lumpur', 'Kampung Batu Muda']],
        ['id' => 'diabetic-shoe', 'label' => 'Diabetic shoe', 'description' => 'protective footwear designed to reduce pressure and rubbing for people with diabetes-related foot risk', 'aliases' => ['diabetes shoes', 'therapeutic shoes']],
        ['id' => 'orthotic-insole', 'label' => 'Orthotic insole', 'description' => 'custom or prefabricated foot support used to improve alignment, comfort, pressure distribution, and walking support', 'aliases' => ['orthopaedic insole', 'custom insole']],
        ['id' => 'offloading', 'label' => 'Offloading', 'description' => 'foot care approach that redistributes pressure away from high-risk or painful areas', 'aliases' => ['pressure relief', 'offload insole']],
        ['id' => 'plantar-pressure', 'label' => 'Plantar pressure', 'description' => 'pressure under the foot that can be measured during assessment to guide insole and footwear choices', 'aliases' => ['pressure scan', 'foot pressure assessment']],
        ['id' => 'diabetes', 'label' => 'Diabetes', 'description' => 'medical condition that can increase foot risk and may require protective footwear, monitoring, and professional care', 'aliases' => ['diabetes mellitus']],
        ['id' => 'foot-ulcer', 'label' => 'Diabetic foot ulcer', 'description' => 'wound risk related to pressure, sensation, circulation, and diabetes that needs healthcare attention', 'aliases' => ['foot wound', 'ulcer prevention']],
    ];
}

function flexifeet_support_wikidata_documents(int $limit = 200): array
{
    $rows = [];
    if (is_file(SUPPORT_WIKIDATA_DATASET_FILE)) {
        $handle = fopen(SUPPORT_WIKIDATA_DATASET_FILE, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false && count($rows) < $limit) {
                $item = json_decode(trim($line), true);
                if (is_array($item)) {
                    $rows[] = $item;
                }
            }
            fclose($handle);
        }
    }
    if (empty($rows)) {
        $rows = flexifeet_support_wikidata_seed_rows();
    }

    $documents = [];
    foreach (array_slice($rows, 0, $limit) as $index => $row) {
        $labels = $row['labels'] ?? [];
        $descriptions = $row['descriptions'] ?? [];
        $label = is_array($labels) ? ($labels['en'] ?? reset($labels) ?: '') : ($row['label'] ?? '');
        $description = is_array($descriptions) ? ($descriptions['en'] ?? reset($descriptions) ?: '') : ($row['description'] ?? '');
        $aliases = $row['aliases'] ?? [];
        if (is_array($aliases)) {
            $aliases = implode(', ', array_map(fn($alias) => is_array($alias) ? (string) reset($alias) : (string) $alias, $aliases));
        }
        $label = normalize_text((string) $label, 160);
        $description = normalize_text((string) $description, 900);
        $aliases = normalize_text((string) $aliases, 500);
        if ($label === '' && $description === '') {
            continue;
        }
        $documents[] = [
            'id' => 'wikidata-' . slugify((string) ($row['id'] ?? $label ?? $index)),
            'title' => 'Wikidata entity: ' . ($label !== '' ? $label : 'entity ' . $index),
            'url' => 'dataset:wikidata',
            'dataset' => 'Wikidata multilingual entity grounding',
            'entity_id' => normalize_text((string) ($row['id'] ?? ''), 80),
            'text' => 'Entity label: ' . $label . '. Description: ' . $description . '. Aliases: ' . $aliases,
        ];
    }
    return array_values(array_filter($documents, fn($doc) => trim((string) ($doc['text'] ?? '')) !== ''));
}

function flexifeet_support_local_ttt_documents(): array
{
    $documents = [];
    foreach (read_support_ttt_documents() as $index => $row) {
        $text = normalize_text((string) ($row['text'] ?? ''), 1200);
        if ($text === '') {
            continue;
        }
        $documents[] = [
            'id' => 'ttt-memory-' . slugify((string) ($row['id'] ?? $index)),
            'title' => 'TTT local learning: ' . normalize_text((string) ($row['intent'] ?? 'support'), 80),
            'url' => 'storage:support-ttt-memory',
            'dataset' => 'Local test-time training memory',
            'intent' => normalize_text((string) ($row['intent'] ?? ''), 60),
            'language' => normalize_text((string) ($row['language'] ?? 'en'), 12),
            'text' => $text,
        ];
    }
    return $documents;
}

function flexifeet_support_store_ttt_memory(string $message, array $documents, string $intent, string $language): void
{
    if (empty($documents)) {
        return;
    }
    $queryTokens = flexifeet_support_tokenize($message);
    $productTokens = array_intersect($queryTokens, flexifeet_support_scope_tokens_for_language($language));
    if (empty($productTokens) && !in_array($intent, ['greeting', 'booking', 'ticket', 'service'], true)) {
        return;
    }

    $memory = read_support_ttt_documents();
    $key = sha1($intent . '|' . $language . '|' . implode(' ', array_slice($queryTokens, 0, 12)));
    foreach ($memory as $existing) {
        if (($existing['key'] ?? '') === $key) {
            return;
        }
    }

    $snippets = [];
    foreach (array_slice($documents, 0, 2) as $document) {
        $snippet = flexifeet_support_public_snippet((string) ($document['text'] ?? ''), (string) ($document['dataset'] ?? ''), 220);
        if ($snippet !== '') {
            $snippets[] = $snippet;
        }
    }
    if (empty($snippets)) {
        return;
    }
    $memory[] = [
        'id' => 'TTT-' . date('YmdHis') . '-' . bin2hex(random_bytes(2)),
        'key' => $key,
        'intent' => $intent,
        'language' => $language,
        'query_terms' => array_slice($queryTokens, 0, 18),
        'text' => 'For similar Flexi Feet queries, prefer these dataset-backed details: ' . implode(' ', array_filter($snippets)),
        'created_at' => date('Y-m-d H:i:s'),
    ];
    save_support_ttt_documents($memory);
}

function flexifeet_support_store_feedback_memory(array $feedback): void
{
    $terms = array_filter($feedback['query_terms'] ?? []);
    if (empty($terms)) {
        return;
    }
    $memory = read_support_ttt_documents();
    $key = sha1('feedback|' . ($feedback['intent'] ?? '') . '|' . implode(' ', $terms));
    foreach ($memory as $existing) {
        if (($existing['key'] ?? '') === $key) {
            return;
        }
    }
    $memory[] = [
        'id' => 'TTT-FB-' . date('YmdHis') . '-' . bin2hex(random_bytes(2)),
        'key' => $key,
        'intent' => normalize_text((string) ($feedback['intent'] ?? 'support'), 60),
        'language' => normalize_text((string) ($feedback['language'] ?? 'en'), 12),
        'query_terms' => array_slice($terms, 0, 18),
        'text' => 'A visitor liked this answer style for Flexi Feet query terms: ' . implode(', ', array_slice($terms, 0, 18)) . '. Keep future answers concise, grounded in Flexi Feet services, and action-oriented.',
        'created_at' => date('Y-m-d H:i:s'),
    ];
    save_support_ttt_documents($memory);
}

function flexifeet_support_hf_dataset_specs(): array
{
    return [
        [
            'dataset' => 'bitext/Bitext-customer-support-llm-chatbot-training-dataset',
            'config' => 'default',
            'split' => 'train',
            'kind' => 'customer_support',
        ],
        [
            'dataset' => 'AmazonScience/mintaka',
            'config' => 'default',
            'split' => 'train',
            'kind' => 'multilingual_wikidata_qa',
        ],
        [
            'dataset' => 'facebook/mlqa',
            'config' => 'mlqa.en.en',
            'split' => 'validation',
            'kind' => 'multilingual_qa',
        ],
        [
            'dataset' => 'google/xquad',
            'config' => 'xquad.en',
            'split' => 'validation',
            'kind' => 'cross_lingual_qa',
        ],
        [
            'dataset' => 'SEACrowd/tydiqa',
            'config' => 'tydiqa_primary_task',
            'split' => 'train',
            'kind' => 'typologically_diverse_qa',
        ],
        [
            'dataset' => 'unicamp-dl/mmarco',
            'config' => 'english',
            'split' => 'train',
            'kind' => 'multilingual_retrieval',
        ],
        [
            'dataset' => 'philippesaade/wikidata',
            'config' => 'default',
            'split' => 'train',
            'kind' => 'wikidata_entities',
        ],
    ];
}

function flexifeet_support_http_json(string $url): ?array
{
    if (!SUPPORT_HF_REMOTE_ENABLED || !filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 2.5,
            'header' => "User-Agent: FlexiFeetSupport/1.0\r\nAccept: application/json\r\n",
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    if (!is_string($raw) || $raw === '') {
        return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function flexifeet_support_hf_rows(array $spec, int $limit): array
{
    static $cache = [];

    $dataset = (string) ($spec['dataset'] ?? '');
    $config = (string) ($spec['config'] ?? 'default');
    $split = (string) ($spec['split'] ?? 'train');
    $length = max(1, min(100, $limit));
    $cacheKey = $dataset . '|' . $config . '|' . $split . '|' . $length;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    $query = http_build_query([
        'dataset' => $dataset,
        'config' => $config,
        'split' => $split,
        'offset' => 0,
        'length' => $length,
    ]);
    $data = flexifeet_support_http_json('https://datasets-server.huggingface.co/rows?' . $query);
    $rows = [];
    foreach (($data['rows'] ?? []) as $item) {
        if (isset($item['row']) && is_array($item['row'])) {
            $rows[] = $item['row'];
        }
    }
    $cache[$cacheKey] = $rows;
    return $rows;
}

function flexifeet_support_hf_search_rows(array $spec, string $query, int $limit): array
{
    static $cache = [];

    $dataset = (string) ($spec['dataset'] ?? '');
    $config = (string) ($spec['config'] ?? 'default');
    $split = (string) ($spec['split'] ?? 'train');
    $query = trim(implode(' ', array_slice(flexifeet_support_tokenize($query), 0, 5)));
    if ($dataset === '' || $query === '') {
        return [];
    }

    $length = max(1, min(20, $limit));
    $cacheKey = $dataset . '|' . $config . '|' . $split . '|' . $query . '|' . $length;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    $params = http_build_query([
        'dataset' => $dataset,
        'config' => $config,
        'split' => $split,
        'query' => $query,
        'offset' => 0,
        'length' => $length,
    ]);
    $data = flexifeet_support_http_json('https://datasets-server.huggingface.co/search?' . $params);
    $rows = [];
    foreach (($data['rows'] ?? []) as $item) {
        if (isset($item['row']) && is_array($item['row'])) {
            $rows[] = $item['row'];
        }
    }
    $cache[$cacheKey] = $rows;
    return $rows;
}

function flexifeet_support_flatten_value($value, int $depth = 0): string
{
    if ($depth > 3) {
        return '';
    }
    if (is_scalar($value) || $value === null) {
        return trim((string) $value);
    }
    if (!is_array($value)) {
        return '';
    }
    $parts = [];
    foreach ($value as $key => $item) {
        $flat = flexifeet_support_flatten_value($item, $depth + 1);
        if ($flat !== '') {
            $parts[] = is_string($key) ? $key . ': ' . $flat : $flat;
        }
    }
    return trim(implode(' ', array_slice($parts, 0, 20)));
}

function flexifeet_support_remote_hf_documents(): array
{
    if (!SUPPORT_HF_REMOTE_ENABLED) {
        return [];
    }

    $documents = [];
    $perDatasetLimit = max(2, min(24, SUPPORT_HF_REMOTE_LIMIT));
    foreach (flexifeet_support_hf_dataset_specs() as $spec) {
        $rows = flexifeet_support_hf_rows($spec, $perDatasetLimit);
        foreach ($rows as $index => $row) {
            $question = flexifeet_support_flatten_value($row['instruction'] ?? $row['question'] ?? $row['query'] ?? $row['title'] ?? '');
            $answer = flexifeet_support_flatten_value($row['response'] ?? $row['answer'] ?? $row['answers'] ?? $row['context'] ?? $row['text'] ?? '');
            $metadata = flexifeet_support_flatten_value(array_diff_key($row, array_flip(['instruction', 'question', 'query', 'title', 'response', 'answer', 'answers', 'context', 'text'])));
            $text = trim('Remote HF pattern: ' . $question . ' Response/context/entity data: ' . $answer . ' Metadata: ' . $metadata);
            if (mb_strlen($text) < 24) {
                continue;
            }
            $documents[] = [
                'id' => 'hf-' . slugify((string) ($spec['dataset'] ?? 'dataset') . '-' . $index),
                'title' => 'HF remote ' . ($spec['kind'] ?? 'dataset') . ': ' . ($spec['dataset'] ?? 'dataset'),
                'url' => 'https://huggingface.co/datasets/' . ($spec['dataset'] ?? ''),
                'dataset' => 'Hugging Face remote HTTP dataset row',
                'source_dataset' => $spec['dataset'] ?? '',
                'source_kind' => $spec['kind'] ?? '',
                'text' => normalize_text($text, 1400),
            ];
        }
    }
    return $documents;
}

function flexifeet_support_remote_hf_documents_for_query(string $message, int $limit = 8): array
{
    if (!SUPPORT_HF_REMOTE_ENABLED) {
        return [];
    }

    $documents = [];
    $perDatasetLimit = max(1, min(4, $limit));
    foreach (flexifeet_support_hf_dataset_specs() as $spec) {
        $rows = flexifeet_support_hf_search_rows($spec, $message, $perDatasetLimit);
        if (empty($rows)) {
            continue;
        }
        foreach ($rows as $index => $row) {
            $question = flexifeet_support_flatten_value($row['instruction'] ?? $row['question'] ?? $row['query'] ?? $row['title'] ?? '');
            $answer = flexifeet_support_flatten_value($row['response'] ?? $row['answer'] ?? $row['answers'] ?? $row['context'] ?? $row['text'] ?? '');
            $metadata = flexifeet_support_flatten_value(array_diff_key($row, array_flip(['instruction', 'question', 'query', 'title', 'response', 'answer', 'answers', 'context', 'text'])));
            $text = trim('Remote HF query match: ' . $question . ' Response/context/entity data: ' . $answer . ' Metadata: ' . $metadata);
            if (mb_strlen($text) < 24) {
                continue;
            }
            $documents[] = [
                'score' => 0.15,
                'id' => 'hf-query-' . slugify((string) ($spec['dataset'] ?? 'dataset') . '-' . $index),
                'title' => 'HF query match ' . ($spec['kind'] ?? 'dataset') . ': ' . ($spec['dataset'] ?? 'dataset'),
                'url' => 'https://huggingface.co/datasets/' . ($spec['dataset'] ?? ''),
                'dataset' => 'Hugging Face remote HTTP search row',
                'source_dataset' => $spec['dataset'] ?? '',
                'source_kind' => $spec['kind'] ?? '',
                'text' => normalize_text($text, 1400),
            ];
        }
    }
    return array_slice($documents, 0, $limit);
}

function flexifeet_support_dataset_sources(): array
{
    $sources = ['flexifeet_project_files'];
    if (is_file(SUPPORT_TRAINING_DATASET_FILE)) {
        $sources[] = 'storage/support-training-dataset.csv';
    } elseif (is_file(STORAGE_DIR . '/Bitext_Sample_Customer_Support_Training_Dataset_27K_responses-v11.csv')) {
        $sources[] = 'storage/Bitext_Sample_Customer_Support_Training_Dataset_27K_responses-v11.csv';
    } else {
        $sources[] = 'built_in_bitext_style_customer_support_seed';
    }
    $sources[] = is_file(SUPPORT_MULTILINGUAL_DATASET_FILE)
        ? 'storage/support-multilingual-dataset.csv'
        : 'built_in_multilingual_support_seed';
    $sources[] = is_file(SUPPORT_WIKIDATA_DATASET_FILE)
        ? 'storage/wikidata-flexifeet.jsonl'
        : 'built_in_wikidata_entity_seed';
    $sources[] = 'raw_repository_byte_blocks';
    if (SUPPORT_HF_REMOTE_ENABLED) {
        foreach (flexifeet_support_hf_dataset_specs() as $spec) {
            $sources[] = 'hf_remote_http:' . ($spec['dataset'] ?? 'dataset');
        }
    } else {
        $sources[] = 'hf_remote_http_disabled';
    }
    return $sources;
}

function flexifeet_support_model(): array
{
    $documents = [];
    $documentFrequency = [];
    $bigrams = [];
    $trigrams = [];
    foreach (flexifeet_support_training_documents() as $document) {
        $tokens = flexifeet_support_tokenize((string) ($document['title'] ?? '') . ' ' . (string) ($document['text'] ?? ''));
        $counts = array_count_values($tokens);
        foreach (array_keys($counts) as $token) {
            $documentFrequency[$token] = ($documentFrequency[$token] ?? 0) + 1;
        }
        for ($i = 0, $count = count($tokens); $i < $count - 1; $i++) {
            $key = $tokens[$i] . ' ' . $tokens[$i + 1];
            $bigrams[$key] = ($bigrams[$key] ?? 0) + 1;
        }
        for ($i = 0, $count = count($tokens); $i < $count - 2; $i++) {
            $key = $tokens[$i] . ' ' . $tokens[$i + 1] . ' ' . $tokens[$i + 2];
            $trigrams[$key] = ($trigrams[$key] ?? 0) + 1;
        }
        $documents[] = $document + [
            'tokens' => $counts,
            'token_count' => count($tokens),
        ];
    }

    $documentCount = max(1, count($documents));
    $semanticWeights = [];
    foreach ($documentFrequency as $token => $frequency) {
        $semanticWeights[$token] = round(log(($documentCount + 1) / ($frequency + 1)) + 1, 4);
    }
    arsort($semanticWeights);

    $fileWeights = [];
    foreach ($documents as &$document) {
        $vector = [];
        foreach ($document['tokens'] as $token => $count) {
            $tf = $count / max(1, (int) $document['token_count']);
            $vector[$token] = round($tf * ($semanticWeights[$token] ?? 1.0), 6);
        }
        arsort($vector);
        $document['vector'] = array_slice($vector, 0, 80, true);
        $fileWeights[(string) $document['id']] = [
            'title' => $document['title'],
            'url' => $document['url'],
            'top_tokens' => array_slice(array_keys($document['vector']), 0, 12),
            'token_count' => $document['token_count'],
        ];
    }
    unset($document);
    arsort($bigrams);
    arsort($trigrams);

    return [
        'name' => 'FlexiFeetSupport',
        'version' => '1.0-local',
        'training_mode' => 'raw_files_as_block_weights',
        'trained_at' => gmdate(DATE_ATOM),
        'dataset_sources' => flexifeet_support_dataset_sources(),
        'block_index' => [
            'objective' => 'raw_file_byte_block_semantics',
            'block_bytes' => max(512, min(8192, SUPPORT_BLOCK_BYTES)),
            'block_limit' => max(12, min(240, SUPPORT_BLOCK_LIMIT)),
            'source_files' => flexifeet_support_raw_block_files(),
        ],
        'semantic_weights' => $semanticWeights,
        'file_weights' => $fileWeights,
        'language_model' => [
            'objective' => 'php_local_corpus_ngram_semantics',
            'corpus' => 'Flexi Feet project files and JSON content',
            'bigrams' => array_slice($bigrams, 0, 250, true),
            'trigrams' => array_slice($trigrams, 0, 250, true),
        ],
        'documents' => $documents,
    ];
}

function flexifeet_support_learned_terms(string $message, int $limit = 6): array
{
    $model = flexifeet_support_model();
    $queryTokens = flexifeet_support_tokenize($message);
    $terms = [];
    foreach ($model['language_model']['trigrams'] as $phrase => $weight) {
        if (!flexifeet_support_public_phrase($phrase)) {
            continue;
        }
        foreach ($queryTokens as $token) {
            if (strpos($phrase, $token) !== false) {
                $terms[$phrase] = $weight;
                break;
            }
        }
        if (count($terms) >= $limit) {
            break;
        }
    }
    if (count($terms) < $limit) {
        foreach ($model['language_model']['bigrams'] as $phrase => $weight) {
            if (!flexifeet_support_public_phrase($phrase)) {
                continue;
            }
            foreach ($queryTokens as $token) {
                if (strpos($phrase, $token) !== false && !isset($terms[$phrase])) {
                    $terms[$phrase] = $weight;
                    break;
                }
            }
            if (count($terms) >= $limit) {
                break;
            }
        }
    }
    return array_keys($terms);
}

function flexifeet_support_public_phrase(string $phrase): bool
{
    if (preg_match('/\b(ok false|ok true|return false|return true|function|json|array|isset|foreach|endif|csrf|token|password|private key|bin2hex|random bytes|render markdown|includes functions|raw file|byte block|support ttt documents|customer instruction pattern|customer instruction|assistant behavior pattern|assistant behavior|assistant|if preg match|https|googleapis|instagram|assets images|php bytes|type offer|itemoffered|continue documents|documents flexifeet|flexifeet support response|flexifeet support flatten|support flatten value|booking owner email|details customer)\b/i', $phrase)) {
        return false;
    }
    if (preg_match('/[a-z\p{L}]/iu', $phrase) !== 1) {
        return false;
    }
    foreach (flexifeet_support_scope_tokens_for_language('en') as $token) {
        if (strpos(mb_strtolower($phrase, 'UTF-8'), mb_strtolower($token, 'UTF-8')) !== false) {
            return true;
        }
    }
    return false;
}

function flexifeet_support_public_snippet(string $text, string $dataset = '', int $maxLength = 240): string
{
    $original = $text;
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    $text = preg_replace('/^Raw file byte block from [^.]+\. Content:\s*/i', '', $text) ?? $text;
    $text = preg_replace('/^For similar Flexi Feet queries, prefer these dataset-backed details:\s*/i', '', $text) ?? $text;

    if (preg_match('/Customer instruction pattern:\s*(.*?)\s*Assistant behavior pattern:\s*(.*)/i', $text, $matches)) {
        $behavior = trim($matches[2]);
        $behavior = preg_replace('/^Answer that\s+/i', '', $behavior) ?? $behavior;
        $behavior = preg_replace('/^Explain\s+/i', '', $behavior) ?? $behavior;
        $behavior = preg_replace('/^Provide\s+/i', '', $behavior) ?? $behavior;
        $behavior = preg_replace('/^Offer\s+/i', '', $behavior) ?? $behavior;
        $behavior = preg_replace('/^Ask for\s+/i', 'Ask for ', $behavior) ?? $behavior;
        $text = $behavior;
    }

    if (stripos($dataset, 'Hugging Face remote') !== false) {
        return '';
    }
    if (preg_match('/\b(id:|answer_start:|Metadata:|Response\/context\/entity data:|\\$[A-Za-z_]|=>|<\\?php|SELECT|INSERT)\b/i', $text)) {
        return '';
    }
    if (preg_match('/\bfunction\s+[A-Za-z_]|<\\?php|\\$_|csrf|private key|password_hash/i', $original)) {
        return '';
    }

    return flexifeet_support_snippet($text, $maxLength);
}

function flexifeet_support_search(string $message, int $limit = 3): array
{
    $model = flexifeet_support_model();
    $language = flexifeet_support_detect_language($message);
    $queryTokens = array_count_values(flexifeet_support_tokenize($message));
    if (empty($queryTokens)) {
        return [];
    }

    $results = [];
    foreach ($model['documents'] as $document) {
        $score = 0.0;
        foreach ($queryTokens as $token => $queryWeight) {
            if (isset($document['vector'][$token])) {
                $score += $document['vector'][$token] * ($model['semantic_weights'][$token] ?? 1.0) * $queryWeight;
            }
        }
        $documentLanguage = (string) ($document['language'] ?? '');
        if ($documentLanguage !== '' && $documentLanguage !== 'multi' && $documentLanguage !== $language) {
            $score *= 0.28;
        }
        $dataset = (string) ($document['dataset'] ?? '');
        $id = (string) ($document['id'] ?? '');
        if ($id === 'business' || $id === 'booking' || $id === 'payment' || $id === 'homepage') {
            $score *= 1.35;
        }
        if ($dataset === 'Local test-time training memory') {
            $score *= 0.42;
        } elseif ($dataset === 'Raw repository byte block') {
            $score *= 0.32;
        } elseif (stripos($dataset, 'Hugging Face remote') !== false) {
            $score *= 0.22;
        }
        if ($score > 0) {
            $results[] = ['score' => $score] + $document;
        }
    }
    foreach (flexifeet_support_remote_hf_documents_for_query($message, 6) as $document) {
        $docTokens = array_count_values(flexifeet_support_tokenize((string) ($document['title'] ?? '') . ' ' . (string) ($document['text'] ?? '')));
        $score = 0.0;
        foreach ($queryTokens as $token => $queryWeight) {
            if (isset($docTokens[$token])) {
                $score += min(3, $docTokens[$token]) * ($model['semantic_weights'][$token] ?? 1.0) * $queryWeight * 0.035;
            }
        }
        if ($score > 0) {
            $results[] = ['score' => max($score, (float) ($document['score'] ?? 0.1))] + $document;
        }
    }
    usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
    return array_slice($results, 0, $limit);
}

function flexifeet_support_direct_facts(string $message): array
{
    $text = mb_strtolower($message, 'UTF-8');
    $facts = [];

    if (preg_match('/service|offer|provide|what do you do|available/i', $text)) {
        $facts[] = 'Flexi Feet provides custom diabetic shoes, orthopaedic footwear, custom offload insoles, flat feet insoles, diabetic socks, compression socks, 3D foot scanning, pressure assessment, fittings, and follow-ups.';
    }
    if (preg_match('/deposit|payment|pay|price|cost/i', $text)) {
        $facts[] = 'Payment methods include card, QR, and account transfer. Custom orders use a 50 percent deposit when placing the order and the remaining 50 percent on delivery.';
    }
    if (preg_match('/deliver|delivery|how long|ready|receive|take/i', $text)) {
        $facts[] = 'Custom-made diabetic shoes usually take about 3 to 4 weeks, based on the current FAQ/support dataset.';
    }
    if (preg_match('/return|refund|change mind|cancel/i', $text)) {
        $facts[] = 'Custom products are tailored, so there is no standard return for change of mind. Fit issues can be reviewed for adjustment or remake according to policy.';
    }
    if (preg_match('/open|hours|sunday|saturday|working hour|business hour/i', $text)) {
        $facts[] = 'Opening hours are Monday to Friday 9:00 AM to 6:00 PM and Saturday 9:00 AM to 1:00 PM. Sunday is closed unless there is a prior appointment and staff availability.';
    }
    if (preg_match('/where|address|location|shop|store|clinic|sentul/i', $text)) {
        $facts[] = 'Flexi Feet is at ' . BUSINESS_ADDRESS . '.';
    }
    if (preg_match('/sock|socks|compression/i', $text)) {
        $facts[] = 'Flexi Feet supplies diabetic socks and compression socks as part of the footwear and foot protection plan.';
    }
    if (preg_match('/home visit|outside kl|ipoh|jb|kulai/i', $text)) {
        $facts[] = 'Home visits may be possible by prior appointment with travel cost inside KL, and monthly travel may include Ipoh and JB Kulai.';
    }
    if (preg_match('/book|appointment|fitting|scan/i', $text)) {
        $facts[] = 'For booking, choose the service type, then provide name, phone, email, preferred date, and an available time.';
    }

    return array_values(array_unique($facts));
}

function flexifeet_support_detect_language(string $message): string
{
    $lower = mb_strtolower($message, 'UTF-8');
    if (preg_match('/\p{Tamil}/u', $message)) {
        return 'ta';
    }
    if (preg_match('/\p{Han}/u', $message)) {
        return 'zh';
    }
    if (preg_match('/\p{Arabic}/u', $message)) {
        return 'ar';
    }
    if (preg_match('/\p{Devanagari}/u', $message)) {
        return 'hi';
    }
    if (preg_match('/\b(temujanji|kasut|kaki|boleh|sokongan|imbasan|diabetes)\b/u', $lower)) {
        return 'ms';
    }
    if (preg_match('/\b(zapatos|diabeticos|plantillas|cita|servicio|ayuda)\b/u', $lower)) {
        return 'es';
    }
    if (preg_match('/\b(rendez|chaussures|semelles|diabetiques|service|aide)\b/u', $lower)) {
        return 'fr';
    }
    return 'en';
}

function flexifeet_support_scope_tokens_for_language(string $language): array
{
    $tokens = ['service', 'services', 'offer', 'provide', 'shoe', 'shoes', 'diabetic', 'diabetes', 'insole', 'insoles', 'sock', 'socks', 'scan', 'scanning', '3d', 'flat', 'ulcer', 'charcot', 'bunion', 'amputation', 'foot', 'feet', 'orthopaedic', 'orthotic', 'pressure', 'sentul', 'payment', 'deposit', 'refund', 'return', 'delivery', 'order', 'hours', 'open', 'time', 'home', 'custom', 'booking', 'appointment', 'fitting', 'where', 'address', 'location', 'shop', 'store'];
    $localized = [
        'ms' => ['kasut', 'diabetes', 'insole', 'kaki', 'imbasan', 'temujanji', 'bayaran', 'stoking', 'ortopedik'],
        'ta' => ['நீரிழிவு', 'காலணி', 'கால்', 'ஸ்கேன்', 'சந்திப்பு'],
        'zh' => ['糖尿病', '鞋', '鞋垫', '足部', '扫描', '预约'],
        'hi' => ['डायबिटिक', 'जूते', 'फुट', 'स्कैन', 'अपॉइंटमेंट'],
        'ar' => ['السكري', 'أحذية', 'القدم', 'فحص', 'موعد'],
        'es' => ['zapatos', 'diabeticos', 'plantillas', 'pie', 'cita'],
        'fr' => ['chaussures', 'diabetiques', 'semelles', 'pied', 'rendez'],
    ];
    return array_merge($tokens, $localized[$language] ?? []);
}

function flexifeet_support_localized_prefix(string $language, string $intent): string
{
    $prefixes = [
        'ms' => [
            'booking' => 'Boleh. Saya boleh bantu minta temujanji Flexi Feet.',
            'ticket' => 'Boleh. Saya boleh bantu rekod isu ini untuk pasukan sokongan Flexi Feet.',
            'out_of_scope' => 'Saya hanya boleh bantu topik Flexi Feet, tempahan temujanji, dan sokongan pelanggan.',
            'service' => 'Flexi Feet boleh membantu dengan kasut diabetes, insole ortopedik, offload insole, stoking diabetes, dan pemeriksaan kaki 3D di Sentul, Kuala Lumpur.',
        ],
        'ta' => [
            'booking' => 'ஆம். Flexi Feet சந்திப்பை கோர உதவுகிறேன்.',
            'ticket' => 'ஆம். இந்த பிரச்சனையை Flexi Feet support ticket ஆக பதிவு செய்ய உதவுகிறேன்.',
            'out_of_scope' => 'நான் Flexi Feet சேவைகள், appointment booking, மற்றும் support tickets மட்டும் உதவ முடியும்.',
            'service' => 'Flexi Feet diabetic shoes, orthopaedic insoles, offload insoles, socks, மற்றும் 3D foot assessment வழங்குகிறது.',
        ],
        'zh' => [
            'booking' => '可以。我可以帮您申请 Flexi Feet 预约。',
            'ticket' => '可以。我可以帮您把这个问题记录成 Flexi Feet 支持工单。',
            'out_of_scope' => '我只能帮助 Flexi Feet 服务、预约和客户支持问题。',
            'service' => 'Flexi Feet 在吉隆坡 Sentul 提供糖尿病鞋、矫形鞋垫、减压鞋垫、袜子和3D足部评估。',
        ],
        'hi' => [
            'booking' => 'हाँ। मैं Flexi Feet appointment request में मदद कर सकता हूँ।',
            'ticket' => 'हाँ। मैं इस समस्या के लिए Flexi Feet support ticket बनाने में मदद कर सकता हूँ।',
            'out_of_scope' => 'मैं केवल Flexi Feet services, appointment booking, और support tickets में मदद कर सकता हूँ।',
            'service' => 'Flexi Feet custom diabetic shoes, orthopaedic insoles, offload insoles, socks, और 3D foot assessment में मदद करता है।',
        ],
        'ar' => [
            'booking' => 'نعم. يمكنني مساعدتك في طلب موعد مع Flexi Feet.',
            'ticket' => 'نعم. يمكنني تسجيل هذه المشكلة كتذكرة دعم لدى Flexi Feet.',
            'out_of_scope' => 'يمكنني فقط المساعدة في خدمات Flexi Feet والمواعيد وتذاكر الدعم.',
            'service' => 'تساعد Flexi Feet في الأحذية المخصصة لمرضى السكري، الفرشات التقويمية، الجوارب، وتقييم القدم ثلاثي الأبعاد في سنتول كوالالمبور.',
        ],
        'es' => [
            'booking' => 'Si. Puedo ayudar a solicitar una cita con Flexi Feet.',
            'ticket' => 'Si. Puedo registrar este problema como ticket de soporte de Flexi Feet.',
            'out_of_scope' => 'Solo puedo ayudar con servicios de Flexi Feet, citas y soporte al cliente.',
            'service' => 'Flexi Feet ayuda con zapatos diabeticos a medida, plantillas ortopedicas, plantillas de descarga, medias y evaluacion 3D del pie.',
        ],
        'fr' => [
            'booking' => 'Oui. Je peux aider a demander un rendez-vous Flexi Feet.',
            'ticket' => 'Oui. Je peux enregistrer ce probleme comme ticket de support Flexi Feet.',
            'out_of_scope' => 'Je peux seulement aider avec les services Flexi Feet, les rendez-vous et le support client.',
            'service' => 'Flexi Feet aide avec les chaussures diabetiques sur mesure, les semelles orthopediques, les semelles de decharge, les chaussettes et le scan 3D du pied.',
        ],
    ];
    return $prefixes[$language][$intent] ?? '';
}

function flexifeet_support_apply_language(string $reply, string $language, string $intent): string
{
    if ($language === 'en') {
        return $reply;
    }
    $prefix = flexifeet_support_localized_prefix($language, $intent);
    if ($prefix === '') {
        return $reply;
    }
    return normalize_text($prefix . ' ' . $reply, 1000);
}

function flexifeet_support_is_greeting(string $message): bool
{
    $text = trim(mb_strtolower($message, 'UTF-8'));
    return preg_match('/^(hi|hello|hey|hiya|good morning|good afternoon|good evening|salam|vanakkam|வணக்கம்|你好|您好|नमस्ते|مرحبا|hola|bonjour)[!. ]*$/u', $text) === 1;
}

function flexifeet_support_response_id(string $message, string $intent): string
{
    return 'FFS-' . substr(hash('sha256', $intent . '|' . $message . '|' . microtime(true) . '|' . random_int(1000, 9999)), 0, 18);
}

function flexifeet_support_reply(string $message): array
{
    $language = flexifeet_support_detect_language($message);
    $text = mb_strtolower($message, 'UTF-8');
    if (flexifeet_support_is_greeting($message)) {
        $matches = flexifeet_support_search('Flexi Feet services booking support diabetic shoes orthopaedic insoles 3D foot scan', 3);
        $reply = 'Hi, I am the Flexi Feet support agent. I can help with diabetic shoes, orthopaedic insoles, offload insoles, 3D foot scanning, appointment booking, payment basics, or creating a support ticket. Tell me what you need, or tap Book Fitting or Create Ticket.';
        flexifeet_support_store_ttt_memory($message, $matches, 'greeting', $language);
        return [
            'model' => 'FlexiFeetSupport',
            'intent' => 'greeting',
            'language' => $language,
            'response_id' => flexifeet_support_response_id($message, 'greeting'),
            'reply' => flexifeet_support_apply_language($reply, $language, 'service'),
            'learned_terms' => flexifeet_support_learned_terms('Flexi Feet services booking support'),
            'suggestions' => [
                ['title' => 'Book a fitting', 'url' => '#booking'],
                ['title' => 'Read foot care guides', 'url' => 'blog.php'],
            ],
            'sources' => array_map(fn($match) => [
                'title' => $match['title'],
                'url' => $match['url'],
                'score' => round((float) $match['score'], 2),
            ], $matches),
        ];
    }

    $bugWords = ['bug', 'issue', 'error', 'broken', 'not working', 'problem', 'complaint', 'wrong'];
    foreach ($bugWords as $word) {
        if (strpos($text, $word) !== false) {
            $reply = 'I can create a support ticket for this issue. Please share your name, email or phone, and what happened.';
            return [
                'model' => 'FlexiFeetSupport',
                'intent' => 'ticket',
                'language' => $language,
                'response_id' => flexifeet_support_response_id($message, 'ticket'),
                'reply' => flexifeet_support_apply_language($reply, $language, 'ticket'),
            ];
        }
    }

    if (preg_match('/book|appointment|visit|fitting|consultation|schedule|slot|available|temujanji|预约|சந்திப்பு|अपॉइंटमेंट|موعد|cita|rendez/u', $text)) {
        $reply = 'I can help request a Flexi Feet appointment step by step. First, what is the booking for: Foot Assessment, Custom Shoes / Footwear Fitting, Customised Insole Assessment, Pressure Sensor Scan, or Follow-up?';
        return [
            'model' => 'FlexiFeetSupport',
            'intent' => 'booking',
            'language' => $language,
            'response_id' => flexifeet_support_response_id($message, 'booking'),
            'reply' => flexifeet_support_apply_language($reply, $language, 'booking'),
        ];
    }

    $scopeTokens = flexifeet_support_scope_tokens_for_language($language);
    $inScope = false;
    foreach ($scopeTokens as $token) {
        if (strpos($text, $token) !== false) {
            $inScope = true;
            break;
        }
    }
    if (!$inScope) {
        $reply = 'I can only help with Flexi Feet services, appointment booking, or support tickets. For other topics, please contact the team directly.';
        return [
            'model' => 'FlexiFeetSupport',
            'intent' => 'out_of_scope',
            'language' => $language,
            'response_id' => flexifeet_support_response_id($message, 'out_of_scope'),
            'reply' => flexifeet_support_apply_language($reply, $language, 'out_of_scope'),
        ];
    }

    $matches = flexifeet_support_search($message, 3);
    $learnedTerms = flexifeet_support_learned_terms($message);
    $suggestions = support_service_suggestions($message);
    $primary = $matches[0] ?? null;
    $reply = 'Flexi Feet helps with custom diabetic shoes, orthopaedic footwear, offload insoles, flat feet insoles, diabetic and compression socks, and 3D foot assessment in Sentul, Kuala Lumpur.';
    $directFacts = flexifeet_support_direct_facts($message);
    if (!empty($directFacts)) {
        $reply .= ' ' . implode(' ', array_slice($directFacts, 0, 3));
    }
    if ($primary && empty($directFacts)) {
        $snippets = [];
        foreach (array_slice($matches, 0, 3) as $match) {
            $snippet = flexifeet_support_public_snippet((string) ($match['text'] ?? ''), (string) ($match['dataset'] ?? ''), 220);
            if ($snippet !== '' && !in_array($snippet, $snippets, true)) {
                $snippets[] = $snippet;
            }
        }
        if (!empty($snippets)) {
            $newSnippets = array_values(array_filter($snippets, function ($snippet) use ($directFacts) {
                foreach ($directFacts as $fact) {
                    similar_text(mb_strtolower($snippet), mb_strtolower($fact), $percent);
                    if ($percent > 62) {
                        return false;
                    }
                }
                return true;
            }));
            if (!empty($newSnippets)) {
                $reply .= ' Based on Flexi Feet content: ' . implode(' Also: ', array_slice($newSnippets, 0, 2));
            }
        }
    }
    $reply .= ' I can answer Flexi Feet service questions, help request a booking, or create a support ticket. For urgent medical concerns, please contact a qualified healthcare professional.';
    flexifeet_support_store_ttt_memory($message, array_values(array_filter($matches, fn($match) => isset($match['dataset']))), 'service', $language);

    return [
        'model' => 'FlexiFeetSupport',
        'intent' => 'service',
        'language' => $language,
        'response_id' => flexifeet_support_response_id($message, 'service'),
        'reply' => normalize_text(flexifeet_support_apply_language($reply, $language, 'service'), 1000),
        'learned_terms' => $learnedTerms,
        'suggestions' => $suggestions,
        'sources' => array_map(fn($match) => [
            'title' => $match['title'],
            'url' => $match['url'],
            'score' => round((float) $match['score'], 2),
        ], $matches),
    ];
}

function support_bot_reply(string $message): array
{
    return flexifeet_support_reply($message);
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
    $slugSource = trim((string) ($payload['slug'] ?? ''));
    if ($slugSource === '') {
        $slugSource = (string) ($payload['title'] ?? 'post');
    }
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
    return $host === 'instagram.com' || preg_match('/(^|\.)instagram\.com$/', $host) === 1;
}

function is_social_reel_url(string $url): bool
{
    if (is_instagram_url($url)) {
        return true;
    }
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    return in_array($host, ['youtube.com', 'www.youtube.com', 'youtu.be'], true);
}

function instagram_content_url(string $url): string
{
    if (!is_instagram_url($url)) {
        return '';
    }

    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === 'l.instagram.com') {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $redirectUrl = sanitize_external_url((string) ($query['u'] ?? ''));
        if ($redirectUrl !== '' && is_instagram_url($redirectUrl)) {
            return $redirectUrl;
        }
    }

    return $url;
}

function instagram_shortcode_from_url(string $url): array
{
    $url = instagram_content_url($url);
    $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
    if (preg_match('/^(reel|p|tv)\/([A-Za-z0-9_-]+)/', $path, $matches)) {
        return ['type' => $matches[1], 'code' => $matches[2]];
    }
    return ['type' => '', 'code' => ''];
}

function canonical_instagram_url(string $url): string
{
    $shortcode = instagram_shortcode_from_url($url);
    if ($shortcode['type'] !== '' && $shortcode['code'] !== '') {
        return 'https://www.instagram.com/' . $shortcode['type'] . '/' . $shortcode['code'] . '/';
    }
    return instagram_content_url($url);
}

function canonical_reel_url(string $url): string
{
    $url = sanitize_external_url($url);
    if ($url === '') {
        return '';
    }
    if (is_instagram_url($url)) {
        return canonical_instagram_url($url);
    }
    return $url;
}

function youtube_video_id_from_url(string $url): string
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
    if ($host === 'youtu.be') {
        return preg_match('/^[A-Za-z0-9_-]{6,}$/', $path) ? $path : '';
    }
    if (preg_match('/^(shorts|embed)\/([A-Za-z0-9_-]{6,})/', $path, $matches)) {
        return $matches[2];
    }
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
    $id = (string) ($query['v'] ?? '');
    return preg_match('/^[A-Za-z0-9_-]{6,}$/', $id) ? $id : '';
}

function reel_thumbnail_from_url(string $url): string
{
    if (is_instagram_url($url)) {
        $shortcode = instagram_shortcode_from_url($url);
        if ($shortcode['type'] !== '' && $shortcode['code'] !== '') {
            return 'https://www.instagram.com/' . $shortcode['type'] . '/' . $shortcode['code'] . '/media/?size=l';
        }
        return '';
    }

    $youtubeId = youtube_video_id_from_url($url);
    return $youtubeId !== '' ? 'https://i.ytimg.com/vi/' . $youtubeId . '/hqdefault.jpg' : '';
}

function reel_title_from_url(string $url, int $position): string
{
    if (is_instagram_url($url)) {
        $shortcode = instagram_shortcode_from_url($url);
        return $shortcode['code'] !== '' ? 'Instagram Reel ' . $shortcode['code'] : 'Instagram Reel ' . $position;
    }

    $youtubeId = youtube_video_id_from_url($url);
    return $youtubeId !== '' ? 'YouTube Short ' . $youtubeId : 'Flexi Feet Reel ' . $position;
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
    $url = canonical_reel_url((string) ($payload['url'] ?? ''));
    $sortOrder = (int) ($payload['sort_order'] ?? 0);
    if ($sortOrder < 1) {
        $sortOrder = count($reels) + 1;
    }
    $title = normalize_text($payload['title'] ?? '', 140);
    if ($title === '') {
        $title = reel_title_from_url($url, $sortOrder);
    }
    $thumbnail = normalize_text($payload['thumbnail'] ?? '', 300);
    if ($thumbnail === '') {
        $thumbnail = reel_thumbnail_from_url($url);
    }
    $reel = [
        'id' => $id ?: 'REEL-' . date('YmdHis') . '-' . bin2hex(random_bytes(2)),
        'title' => $title,
        'url' => $url,
        'thumbnail' => $thumbnail,
        'status' => $status,
        'sort_order' => $sortOrder,
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

function markdown_href(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (preg_match('/^https?:\/\//i', $url)) {
        return sanitize_external_url($url);
    }
    if (preg_match('/^(mailto:|tel:|#|\/|[A-Za-z0-9._~\-\/?#=&%+]+$)/', $url) === 1 && stripos($url, 'javascript:') !== 0) {
        return $url;
    }
    return '';
}

function render_markdown_inline(string $text): string
{
    $codes = [];
    $text = preg_replace_callback('/`([^`]+)`/', function ($matches) use (&$codes) {
        $key = "\x1A" . count($codes) . "\x1A";
        $codes[$key] = '<code>' . e($matches[1]) . '</code>';
        return $key;
    }, $text) ?? $text;

    $text = e($text);
    $text = preg_replace_callback('/\[(.+?)\]\(([^)\s]+)\)/', function ($matches) {
        $href = markdown_href(html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8'));
        if ($href === '') {
            return $matches[1];
        }
        return '<a href="' . e($href) . '" target="_blank" rel="noopener">' . $matches[1] . '</a>';
    }, $text) ?? $text;
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text) ?? $text;
    $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $text) ?? $text;

    return strtr($text, $codes);
}

function render_markdown_block(array $lines): string
{
    $lines = array_values(array_filter(array_map('trim', $lines), fn($line) => $line !== ''));
    if (empty($lines)) {
        return '';
    }

    if (count($lines) === 1) {
        $line = $lines[0];
        if (preg_match('/^\[image:([^\]]+)\]$/', $line, $matches)) {
            $src = normalize_text($matches[1], 300);
            return '<figure class="blog-inline-image"><img src="' . e($src) . '" alt=""></figure>';
        }
        if (preg_match('/^!\[([^\]]*)\]\(([^)]+)\)$/', $line, $matches)) {
            $src = markdown_href($matches[2]);
            if ($src !== '') {
                return '<figure class="blog-inline-image"><img src="' . e($src) . '" alt="' . e($matches[1]) . '"></figure>';
            }
        }
        if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $matches)) {
            $level = strlen($matches[1]);
            return '<h' . $level . '>' . render_markdown_inline($matches[2]) . '</h' . $level . '>';
        }
    }

    if (count(array_filter($lines, fn($line) => preg_match('/^[-*]\s+.+$/', $line))) === count($lines)) {
        $items = array_map(fn($line) => '<li>' . render_markdown_inline(preg_replace('/^[-*]\s+/', '', $line) ?? $line) . '</li>', $lines);
        return '<ul>' . implode('', $items) . '</ul>';
    }

    if (count(array_filter($lines, fn($line) => preg_match('/^\d+\.\s+.+$/', $line))) === count($lines)) {
        $items = array_map(fn($line) => '<li>' . render_markdown_inline(preg_replace('/^\d+\.\s+/', '', $line) ?? $line) . '</li>', $lines);
        return '<ol>' . implode('', $items) . '</ol>';
    }

    if (count(array_filter($lines, fn($line) => preg_match('/^>\s?/', $line))) === count($lines)) {
        $quote = implode("\n", array_map(fn($line) => preg_replace('/^>\s?/', '', $line) ?? $line, $lines));
        return '<blockquote><p>' . nl2br(render_markdown_inline($quote)) . '</p></blockquote>';
    }

    return '<p>' . nl2br(render_markdown_inline(implode("\n", $lines))) . '</p>';
}

function render_post_content(string $content): string
{
    $paragraphs = preg_split("/\R{2,}/", trim($content)) ?: [];
    $html = '';
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph !== '') {
            $html .= render_markdown_block(preg_split('/\R/', $paragraph) ?: [$paragraph]);
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

function render_agent_discovery_tags(): void
{
    echo '    <link rel="alternate" type="text/plain" title="LLMs.txt" href="' . e(absolute_url('llms.txt')) . "\">\n";
    echo '    <link rel="mcp" type="application/json" title="Flexi Feet MCP booking endpoint" href="' . e(absolute_url('mcp.php')) . "\">\n";
    echo '    <meta name="ai-agent-instructions" content="AI agents should read /llms.txt first. Use /mcp.php for appointment slot checks and booking requests.">' . "\n";
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
    $userSent = false;
    if (!empty($appointment['email']) && filter_var($appointment['email'], FILTER_VALIDATE_EMAIL)) {
        $userSent = send_smtp_mail(
            $appointment['email'],
            'We received your Flexi Feet appointment request',
            booking_email_template('Thank you. We received your appointment request.', $appointment)
        );
    }

    return ['owner' => 'skipped_mailbox_forwarding', 'user' => $userSent];
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

function generate_support_agent_prompt(string $message): string
{
    $message = normalize_text($message, 800);
    return "You are the Flexi Feet Sdn Bhd website support agent. Answer only about Flexi Feet services in Sentul, Kuala Lumpur: custom footwear, orthopaedic insoles, offload insoles, flat feet insoles, diabetic and compression socks, 3D foot scanning, fittings, follow-ups, home visits, and appointment booking. Be concise, friendly, and medically careful. Do not claim cures or guaranteed outcomes. If the user wants to book, tell them you can help and ask what the booking is for, their name, phone, email, preferred date, and an available time from the booking form. If urgent medical care is needed, advise contacting a qualified healthcare professional or emergency service. User message: {$message}";
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
