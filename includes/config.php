<?php
declare(strict_types=1);

define('BUSINESS_NAME', 'Flexi Feet Sdn Bhd');
define('BUSINESS_PHONE', '+60 16-605 5477');
define('BUSINESS_EMAIL', 'flexifeetsdnbhd@gmail.com');
define('BUSINESS_ADDRESS', 'G17, Residency Awani 2, 1A, Jalan 2/12, Kampung Batu Muda, Sentul, 51100 Kuala Lumpur, Malaysia');
define('SITE_URL', 'https://flexifeet.net');

define('STORAGE_DIR', __DIR__ . '/../storage');
define('APPOINTMENTS_FILE', STORAGE_DIR . '/appointments.json');
define('BLOG_POSTS_FILE', STORAGE_DIR . '/blog-posts.json');
define('REELS_FILE', STORAGE_DIR . '/instagram-reels.json');
define('SUPPORT_TICKETS_FILE', STORAGE_DIR . '/support-tickets.json');
define('SUPPORT_FEEDBACK_FILE', STORAGE_DIR . '/support-feedback.json');
define('SUPPORT_TTT_MEMORY_FILE', STORAGE_DIR . '/support-ttt-memory.json');
define('SUPPORT_TRAINING_DATASET_FILE', STORAGE_DIR . '/support-training-dataset.csv');
define('SUPPORT_MULTILINGUAL_DATASET_FILE', STORAGE_DIR . '/support-multilingual-dataset.csv');
define('SUPPORT_WIKIDATA_DATASET_FILE', STORAGE_DIR . '/wikidata-flexifeet.jsonl');
defined('SUPPORT_HF_REMOTE_ENABLED') || define('SUPPORT_HF_REMOTE_ENABLED', getenv('FLEXIFEET_SUPPORT_HF_REMOTE_ENABLED') !== '0');
defined('SUPPORT_HF_REMOTE_LIMIT') || define('SUPPORT_HF_REMOTE_LIMIT', (int) (getenv('FLEXIFEET_SUPPORT_HF_REMOTE_LIMIT') ?: 16));
defined('SUPPORT_BLOCK_BYTES') || define('SUPPORT_BLOCK_BYTES', (int) (getenv('FLEXIFEET_SUPPORT_BLOCK_BYTES') ?: 2048));
defined('SUPPORT_BLOCK_LIMIT') || define('SUPPORT_BLOCK_LIMIT', (int) (getenv('FLEXIFEET_SUPPORT_BLOCK_LIMIT') ?: 96));
define('UPLOADS_DIR', __DIR__ . '/../assets/uploads');
define('UPLOADS_URL', 'assets/uploads');
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$12$Vrfd.NSjAkMh3uMjE76Cful4SkAmKVr9DOCIt1C0kObmPAErBfZTm'); // FlexiFeet@2026

define('CONFIG_LOCAL_FILE', __DIR__ . '/config.local.php');
if (file_exists(CONFIG_LOCAL_FILE)) {
    require_once CONFIG_LOCAL_FILE;
}

defined('SMTP_HOST') || define('SMTP_HOST', getenv('FLEXIFEET_SMTP_HOST') ?: 'smtp.hostinger.com');
defined('SMTP_PORT') || define('SMTP_PORT', (int) (getenv('FLEXIFEET_SMTP_PORT') ?: 465));
defined('SMTP_ENCRYPTION') || define('SMTP_ENCRYPTION', getenv('FLEXIFEET_SMTP_ENCRYPTION') ?: 'ssl');
defined('SMTP_USERNAME') || define('SMTP_USERNAME', getenv('FLEXIFEET_SMTP_USERNAME') ?: 'support@flexifeet.net');
defined('SMTP_PASSWORD') || define('SMTP_PASSWORD', getenv('FLEXIFEET_SMTP_PASSWORD') ?: '');
defined('SMTP_FROM_EMAIL') || define('SMTP_FROM_EMAIL', getenv('FLEXIFEET_SMTP_FROM_EMAIL') ?: SMTP_USERNAME);
defined('SMTP_FROM_NAME') || define('SMTP_FROM_NAME', getenv('FLEXIFEET_SMTP_FROM_NAME') ?: BUSINESS_NAME);
defined('BOOKING_OWNER_EMAIL') || define('BOOKING_OWNER_EMAIL', getenv('FLEXIFEET_BOOKING_OWNER_EMAIL') ?: SMTP_USERNAME);
defined('GA_MEASUREMENT_ID') || define('GA_MEASUREMENT_ID', getenv('FLEXIFEET_GA_MEASUREMENT_ID') ?: '');
defined('GOOGLE_SITE_VERIFICATION') || define('GOOGLE_SITE_VERIFICATION', getenv('FLEXIFEET_GOOGLE_SITE_VERIFICATION') ?: '');
defined('DEFAULT_SEO_TITLE') || define('DEFAULT_SEO_TITLE', getenv('FLEXIFEET_DEFAULT_SEO_TITLE') ?: 'Custom Diabetic Shoes & Orthopaedic Insoles Malaysia | Flexi Feet');
defined('DEFAULT_SEO_DESCRIPTION') || define('DEFAULT_SEO_DESCRIPTION', getenv('FLEXIFEET_DEFAULT_SEO_DESCRIPTION') ?: 'Flexi Feet Sdn Bhd provides custom diabetic shoes, orthopaedic footwear, offload insoles and 3D foot assessment in Sentul, Kuala Lumpur.');
defined('DEFAULT_SOCIAL_IMAGE') || define('DEFAULT_SOCIAL_IMAGE', getenv('FLEXIFEET_DEFAULT_SOCIAL_IMAGE') ?: 'assets/images/flexi-feet-logo.png');
defined('GOOGLE_SERVICE_ACCOUNT_EMAIL') || define('GOOGLE_SERVICE_ACCOUNT_EMAIL', getenv('FLEXIFEET_GOOGLE_SERVICE_ACCOUNT_EMAIL') ?: '');
defined('GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY') || define('GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY', getenv('FLEXIFEET_GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY') ?: '');
defined('GA4_PROPERTY_ID') || define('GA4_PROPERTY_ID', getenv('FLEXIFEET_GA4_PROPERTY_ID') ?: '');
defined('SEARCH_CONSOLE_SITE_URL') || define('SEARCH_CONSOLE_SITE_URL', getenv('FLEXIFEET_SEARCH_CONSOLE_SITE_URL') ?: SITE_URL);
defined('GOOGLE_ADSENSE_CLIENT_ID') || define('GOOGLE_ADSENSE_CLIENT_ID', getenv('FLEXIFEET_GOOGLE_ADSENSE_CLIENT_ID') ?: '');
defined('GOOGLE_PAGESPEED_API_KEY') || define('GOOGLE_PAGESPEED_API_KEY', getenv('FLEXIFEET_GOOGLE_PAGESPEED_API_KEY') ?: '');
defined('GOOGLE_AI_API_KEY') || define('GOOGLE_AI_API_KEY', getenv('FLEXIFEET_GOOGLE_AI_API_KEY') ?: '');
defined('GOOGLE_AI_MODEL') || define('GOOGLE_AI_MODEL', getenv('FLEXIFEET_GOOGLE_AI_MODEL') ?: 'gemma-4-31b-it');
defined('AUTOMATION_TOKEN') || define('AUTOMATION_TOKEN', getenv('FLEXIFEET_AUTOMATION_TOKEN') ?: '');
