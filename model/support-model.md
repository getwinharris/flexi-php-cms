---
type: Model Architecture
title: FlexiFeetSupport Semantic Model
description: How the local PHP support model builds semantic weights from repository files, JSON content, datasets, feedback, and TTT memory.
resource: includes/functions.php
tags: [support-model, semantic-search, ttt, hf-datasets, multilingual]
timestamp: 2026-07-02T15:30:00Z
---

# Model Identity

`FlexiFeetSupport` is a local PHP semantic model. It does not load a remote LLM for support replies and does not train neural tensors. Its "weights" are generated from readable files, raw byte blocks, and structured data:

* bounded byte blocks from curated repository files
* project/business documents
* public blog posts
* built-in customer-support seed rows
* optional local CSV/JSONL datasets
* remote Hugging Face Dataset Viewer rows
* local feedback and TTT memory

# Training Documents

`flexifeet_support_training_documents()` builds document blocks from:

| Source | Role |
|---|---|
| Business constants | canonical contact, location, and services |
| Booking policy text | appointment hours and booking types |
| Payment policy text | card, QR, account transfer, deposit/balance terms |
| `llms.txt` | public agent instructions |
| `index.php` | homepage service content |
| published blog JSON | educational service corpus |
| raw repository byte blocks | byte-ranged file memory with source path, byte range, hash, and extracted text |
| Bitext-style seed rows | support intent behavior |
| multilingual seed rows | Malay, Tamil, Chinese, Hindi, Arabic, Spanish, French query patterns |
| Wikidata-style seed rows | entity grounding for Flexi Feet, Sentul, diabetic shoes, insoles, offloading, plantar pressure, diabetes, foot ulcers |
| support TTT memory | bounded local learning notes |
| HF remote rows | small remote HTTP slices, not full downloads |

# Weighting

The model tokenizes text with Unicode-aware token extraction, computes document frequencies, builds semantic TF-IDF-like vectors, and learns bigram/trigram phrase transitions. Raw file bytes are split into bounded blocks first; those blocks become ordinary semantic documents while retaining byte range and hash metadata.

At search time, `flexifeet_support_search()` scores local documents, applies a language penalty to mismatched-language documents, then augments search with query-specific Hugging Face `/search` rows when available.

# Reply Intents

| Intent | Trigger |
|---|---|
| `greeting` | generic prompts such as `hi`, `hello`, `hey`, `salam`, `vanakkam`, `你好`, `नमस्ते`, `مرحبا`, `hola`, `bonjour` |
| `ticket` | issue, bug, complaint, broken, not working, problem words |
| `booking` | book, appointment, fitting, schedule, slot, and multilingual equivalents |
| `out_of_scope` | no Flexi Feet service tokens |
| `service` | in-scope service/product/condition questions |

# Feedback and TTT

Every support reply includes a `response_id`. The frontend can send `like` or `dislike` to `api/support-bot.php?action=feedback`.

Stored files:

* `storage/support-feedback.json` keeps bounded response feedback.
* `storage/support-ttt-memory.json` keeps bounded local learning notes from liked responses and query-relevant dataset snippets.

This is a lightweight test-time-training loop: later searches read those notes as additional concept documents. It is intentionally bounded and text-based.

# External Dataset Adapters

Configured Hugging Face datasets:

* `bitext/Bitext-customer-support-llm-chatbot-training-dataset`
* `AmazonScience/mintaka`
* `facebook/mlqa`
* `google/xquad`
* `SEACrowd/tydiqa`
* `unicamp-dl/mmarco`
* `philippesaade/wikidata`

# Citations

[1] [Core support functions](../includes/functions.php)
[2] [Support API](../api/support-bot.php)
[3] [Config dataset constants](../includes/config.php)
[4] [Tests](../tests/run.php)
