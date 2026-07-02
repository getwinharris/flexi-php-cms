# Flexi Feet Sdn Bhd - Premium Website & CRM

A self-contained, high-performance website and CRM built for Flexi Feet Sdn Bhd. Designed with Apple-standard aesthetics and optimized for Hostinger shared hosting.

## Features

- **Apple Standard UI**: High-end minimalist design, glassmorphism, and smooth scroll animations.
- **Dynamic Content**: Admin-managed Instagram Reels ("Flexi Stories") scrolling section.
- **Self-Contained CRM**: A built-in admin dashboard to manage appointments stored in a local JSON database.
- **Blog CMS**: Admin-managed posts with draft/published states and public blog pages.
- **Media Library**: WordPress-style image uploads for blog featured images, inline blog media, and website content.
- **Instagram Reels Manager**: Admin-managed Reel URLs with automatic Instagram/YouTube thumbnails and drag-and-drop ordering.
- **SMTP Notifications**: Booking requests can email the site owner and send the customer a confirmation via Hostinger SMTP.
- **Local Support Model**: `FlexiFeetSupport` trains PHP semantic/n-gram weights from project files, blog JSON, built-in multilingual/Wikidata patterns, and small remote Hugging Face Dataset Viewer API samples.
- **3D Scanning Section**: Highlights the advanced Italian foot scanning technology.
- **Comprehensive Foot Care**: Detailed sections for 10 common diabetic and orthopaedic foot conditions.
- **Hostinger Ready**: Runs on PHP/JS/CSS with zero external dependencies or build steps.

## Deployment

1. Upload all files in this directory to your Hostinger `public_html` folder.
2. Ensure the `storage/` and `assets/uploads/` folders are writable by the server (usually `755` permission).
3. Copy `includes/config.local.example.php` to `includes/config.local.php` on the server and add the real SMTP password.
4. Access your site at `yourdomain.com`.

## Admin Access

- **Login URL**: `yourdomain.com/admin/login.php`
- **Default Username**: `admin`
- **Default Password**: `FlexiFeet@2026`

*Note: For security, it is highly recommended to update the admin credentials in `includes/config.php` before going live.*

## Local Testing

If you have PHP installed locally, you can run:
```bash
php -S localhost:8000
```
Then visit `http://localhost:8000` in your browser.

## Local Support Model Dataset

`FlexiFeetSupport` works as a local semantic model, not a neural model with binary weights. The website files, blog content, support intent rows, multilingual rows, and entity rows become the model's searchable document weights.

By default, the model can read small remote slices from Hugging Face over HTTP using the public Dataset Viewer API. It does not download whole datasets. Disable remote dataset reads with:

```text
FLEXIFEET_SUPPORT_HF_REMOTE_ENABLED=0
```

Current remote dataset adapters:

- `bitext/Bitext-customer-support-llm-chatbot-training-dataset` for customer-support intent patterns.
- `AmazonScience/mintaka` for multilingual QA with Wikidata entity grounding.
- `facebook/mlqa`, `google/xquad`, and `SEACrowd/tydiqa` for multilingual/cross-lingual QA patterns.
- `unicamp-dl/mmarco` for multilingual retrieval patterns.
- `philippesaade/wikidata` for multilingual Wikidata entity text.

At reply time, the support bot also performs a small query-specific Hugging Face `/search` request where supported, then stores only relevant Flexi Feet learning notes in:

```text
storage/support-ttt-memory.json
storage/support-feedback.json
```

This is a lightweight test-time-training style loop: visitor prompts, helpful/not-helpful feedback, and relevant dataset snippets influence future semantic ranking, but the app does not train or ship neural weights. Generic greetings such as "hi" or "hello" are handled as a support entry point and explain the actions the agent can take.

For private/offline enrichment, place local exports in:

```text
storage/support-training-dataset.csv
storage/support-multilingual-dataset.csv
storage/wikidata-flexifeet.jsonl
```

The Bitext-style CSV columns are `instruction`, `category`, `intent`, and `response`. The multilingual CSV can use `language`, `instruction`, `response`, and `intent`. The Wikidata JSONL can use `id`, `label`, `description`, and `aliases`, or multilingual `labels`/`descriptions` objects. All imports are capped for Hostinger-friendly performance.

## Technical Details

- **Frontend**: Vanilla JavaScript & Modern CSS (Grid, Flexbox, Backdrop-filter).
- **Backend**: PHP 7.4+.
- **Database**: `storage/appointments.json`, `storage/blog-posts.json`, and `storage/instagram-reels.json` (Atomic file-locked JSON storage).
- **Icons**: Custom embedded SVG icons for zero external requests.
- **Fonts**: 'Inter' imported via Google Fonts for premium typography.
