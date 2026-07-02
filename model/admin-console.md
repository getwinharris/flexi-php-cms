---
type: Product Surface
title: Admin Console
description: Admin modules for appointments, blog posts, reels, media, support tickets, SMTP, SEO, analytics, PageSpeed, AI, and automation settings.
resource: admin/
tags: [admin, cms, seo, smtp, analytics]
timestamp: 2026-07-02T15:30:00Z
---

# Modules

| File | Purpose |
|---|---|
| `admin/index.php` | Dashboard with appointments and support/booking operational context. |
| `admin/posts.php` | Blog post listing and trash workflow. |
| `admin/post-edit.php` | Blog editor with Markdown support, SEO fields, and featured image upload. |
| `admin/reels.php` | URL-only reel manager with automatic metadata and drag ordering. |
| `admin/media.php` | Media library for uploads and asset browsing. |
| `admin/tickets.php` | Support ticket review and status updates. |
| `admin/settings.php` | SMTP, Google SEO, PageSpeed, AI, automation, and outbound mail test. |
| `admin/export.php` | Admin export support. |
| `admin/ai-writer.php` | Optional internal content drafting using Google AI config. |

# Settings Capabilities

Admin settings can save SMTP, Google Analytics measurement id, AdSense client id, Search Console verification, SEO defaults, service account credentials, PageSpeed API key, Google AI model/key, and automation token into `includes/config.local.php`.

The SEO dashboard shows published/indexable post counts, average blog SEO readiness, Google connection checks, GA4/Search Console fetch status, and PageSpeed scores when configured.

# Operational Rule

The website should not send a separate owner email for new appointments. The owner copy is expected through mailbox forwarding. The SMTP test remains available for verifying outbound mail.

# Citations

[1] [Admin settings](../admin/settings.php)
[2] [Admin sidebar](../admin/partials/sidebar.php)
[3] [Admin styles](../admin/flexi-admin.css)
[4] [Mail settings functions](../includes/functions.php)
