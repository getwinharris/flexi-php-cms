<?php
declare(strict_types=1);

define('BUSINESS_NAME', 'Flexi Feet Sdn Bhd');
define('BUSINESS_PHONE', '+60 16-605 5477');
define('BUSINESS_EMAIL', 'flexifeetsdnbhd@gmail.com');
define('BUSINESS_ADDRESS', 'G17, Residency Awani 2, 1A, Jalan 2/12, Kampung Batu Muda, Sentul, 51100 Kuala Lumpur, Malaysia');

define('STORAGE_DIR', __DIR__ . '/../storage');
define('APPOINTMENTS_FILE', STORAGE_DIR . '/appointments.json');
define('BLOG_POSTS_FILE', STORAGE_DIR . '/blog-posts.json');
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$12$Vrfd.NSjAkMh3uMjE76Cful4SkAmKVr9DOCIt1C0kObmPAErBfZTm'); // FlexiFeet@2026

$local_config = __DIR__ . '/config.local.php';
if (file_exists($local_config)) {
    require_once $local_config;
}

defined('SMTP_HOST') || define('SMTP_HOST', getenv('FLEXIFEET_SMTP_HOST') ?: 'smtp.hostinger.com');
defined('SMTP_PORT') || define('SMTP_PORT', (int) (getenv('FLEXIFEET_SMTP_PORT') ?: 465));
defined('SMTP_ENCRYPTION') || define('SMTP_ENCRYPTION', getenv('FLEXIFEET_SMTP_ENCRYPTION') ?: 'ssl');
defined('SMTP_USERNAME') || define('SMTP_USERNAME', getenv('FLEXIFEET_SMTP_USERNAME') ?: 'support@flexifeet.net');
defined('SMTP_PASSWORD') || define('SMTP_PASSWORD', getenv('FLEXIFEET_SMTP_PASSWORD') ?: '');
defined('SMTP_FROM_EMAIL') || define('SMTP_FROM_EMAIL', getenv('FLEXIFEET_SMTP_FROM_EMAIL') ?: SMTP_USERNAME);
defined('SMTP_FROM_NAME') || define('SMTP_FROM_NAME', getenv('FLEXIFEET_SMTP_FROM_NAME') ?: BUSINESS_NAME);
defined('BOOKING_OWNER_EMAIL') || define('BOOKING_OWNER_EMAIL', getenv('FLEXIFEET_BOOKING_OWNER_EMAIL') ?: SMTP_USERNAME);
