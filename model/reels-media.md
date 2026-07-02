---
type: Product Surface
title: Reels and Media
description: Instagram/YouTube reel management, URL canonicalization, automatic thumbnails, and media library behavior.
resource: admin/reels.php
tags: [reels, instagram, youtube, media]
timestamp: 2026-07-02T15:30:00Z
---

# Reel Workflow

Admin reel entry is URL-only. When a reel is saved, the app derives:

* clean canonical URL
* title from Instagram shortcode or YouTube video id
* thumbnail from Instagram `/media/?size=l` or YouTube `hqdefault.jpg`
* sort order
* active/inactive status

# Instagram URL Handling

The Instagram host check accepts `instagram.com` and any `*.instagram.com` subdomain, including mobile and redirector hosts. Tracking URLs such as `https://www.instagram.com/reel/DFzlzIOqvKv/?utm_source=...` are canonicalized to `https://www.instagram.com/reel/DFzlzIOqvKv/`.

# Media Library

The media library handles image uploads and previews. Admin preview helpers avoid prefixing absolute URLs with `../`, which keeps externally hosted images valid.

# Model Role

Reel URLs and thumbnails are currently presentation data, not core support answers. For future multimodal indexing, reels should be indexed as video blocks using platform, shortcode/video id, canonical URL, thumbnail URL, and any manually reviewed transcript or caption.

# Citations

[1] [Reels admin](../admin/reels.php)
[2] [Reel functions](../includes/functions.php)
[3] [Media admin](../admin/media.php)
[4] [Frontend reels section](../index.php)
