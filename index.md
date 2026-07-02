---
okf_version: "0.1"
---

# Flexi Feet Semantic Model Bundle

This root index follows Open Knowledge Format v0.1. Agents should read this file first, then traverse the linked concept documents before answering questions about this repository.

# Agent Entry Points

* [Agent Operating Instructions](AGENTS.md) - How agents should use this OKF bundle, the local semantic model, and the website tools.
* [LLMs Website Instructions](llms.txt) - Public website instructions for AI agents and crawlers.
* [MCP Booking Endpoint](model/booking-api.md) - JSON-RPC appointment tools exposed at `mcp.php`.

# Model Weight Concepts

* [Business Knowledge](model/business.md) - Canonical Flexi Feet service, contact, location, and safety facts.
* [Support Model Architecture](model/support-model.md) - How `FlexiFeetSupport` turns files, blogs, datasets, feedback, and TTT memory into semantic weights.
* [Block Diffusion Indexing Plan](model/block-diffusion-indexing.md) - Local block-based multimodal indexing design inspired by tiny DiffusionGemma metadata.
* [Data Stores](model/data-stores.md) - JSON/CSV/JSONL files used as local data and model memory.

# Product Surfaces

* [Booking and Support APIs](model/booking-api.md) - `api/support-bot.php`, `api/booking.php`, and `mcp.php` behavior.
* [Admin Console](model/admin-console.md) - Admin modules for appointments, posts, reels, media, tickets, SEO, SMTP, and automation.
* [Blog and SEO Corpus](model/blog-seo.md) - Published blog topics, Markdown support, SEO scoring, sitemap, robots, and analytics configuration.
* [Reels and Media](model/reels-media.md) - Instagram/YouTube reel canonicalization, automatic thumbnails, and media library behavior.
* [Frontend Support Bot](model/frontend-support-bot.md) - Floating support UI, booking/ticket forms, feedback controls, and browser-tested behavior.

# Update Log

* [Model Bundle Log](model/log.md) - Chronological history for this local knowledge bundle.
