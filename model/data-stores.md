---
type: Data Catalog
title: Local Data Stores
description: JSON, CSV, and JSONL files that power the CMS, support model, appointments, blog, reels, feedback, and TTT memory.
resource: storage/
tags: [storage, json, support-model, cms]
timestamp: 2026-07-02T15:30:00Z
---

# JSON Stores

| File | Key | Purpose |
|---|---|---|
| `storage/appointments.json` | `appointments` | Appointment requests and status records. |
| `storage/blog-posts.json` | `posts` | Blog CMS corpus used by public pages and support model training. |
| `storage/instagram-reels.json` | `reels` | Admin-managed social video URLs and generated metadata. |
| `storage/support-tickets.json` | `tickets` | Website/support issues created by the support bot or admin flow. |
| `storage/support-feedback.json` | `feedback` | Like/dislike signals for support responses. |
| `storage/support-ttt-memory.json` | `documents` | Bounded local TTT learning notes used as model documents. |

# Optional Dataset Files

| File | Format | Purpose |
|---|---|---|
| `storage/support-training-dataset.csv` | CSV | Local Bitext-style support intent rows. |
| `storage/support-multilingual-dataset.csv` | CSV | Local multilingual support examples. |
| `storage/wikidata-flexifeet.jsonl` | JSONL | Local Wikidata-style entity grounding rows. |

# Bootstrap Behavior

`storage_bootstrap()` creates missing core JSON files with empty arrays. `save_json_file()` writes arrays with an `updated_at` timestamp and `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES`.

# Git Behavior

Large or private storage files are ignored by `.gitignore` using `storage/*.csv`, `storage/*.json`, and `storage/*.jsonl`. Some JSON storage files may already be tracked because they existed before ignore rules; treat them carefully.

# Citations

[1] [Config storage constants](../includes/config.php)
[2] [Storage bootstrap and JSON helpers](../includes/functions.php)
[3] [Git ignore rules](../.gitignore)
