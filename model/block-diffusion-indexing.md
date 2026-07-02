---
type: Model Design
title: Block Diffusion Indexing Plan
description: Local block-based multimodal indexing design for FlexiFeetSupport, inspired by tiny DiffusionGemma metadata without pretending to be a neural checkpoint.
resource: https://huggingface.co/trl-internal-testing/tiny-DiffusionGemmaForBlockDiffusion/tree/main
tags: [block-diffusion, multimodal, indexing, okf, support-model]
timestamp: 2026-07-02T15:30:00Z
---

# Purpose

This repository uses files as readable model memory. The local system is block-first and byte-friendly: raw repository files are split into bounded byte ranges, hashed, described as blocks, and searched as semantic memory. No neural checkpoint is generated here.

# Block-First Rule

Do not treat this project as a wrapper around a Transformer model. Treat it as an omni-file model whose reasoning substrate is:

1. raw file bytes
2. OKF concept documents
3. JSON/CSV/JSONL rows
4. image/video metadata
5. query-time dataset snippets
6. bounded feedback and TTT memory

The model answer is produced by retrieving, denoising, and composing from blocks.

# Diffusion-Inspired Signals Used

The referenced tiny DiffusionGemma model is only a structural reference. Its metadata exposes useful mechanics that map cleanly onto files:

| Signal | Local interpretation |
|---|---|
| `model_type: diffusion_gemma` | Treat generation as iterative refinement over blocks, not one giant prompt. |
| `canvas_length: 32` | Keep answer-planning context bounded into small blocks. |
| text config with sliding/full attention | Search narrow byte blocks first, then expand to linked OKF concepts when needed. |
| vision config and image processor | Index images as modality blocks with captions, alt text, filename, and page context. |
| audio/video processor config | Reserve modality fields for future audio/video transcripts, not placeholders. |
| generation config with denoising/stability thresholds | Use confidence, source count, language match, and intent stability before answering. |

# Local Block Format

Each block is derivable from an OKF concept, source file, JSON row, or media asset:

```yaml
block_id: byte-block-includes-functions-0004
concept: /model/support-model.md
modality: bytes:text
source_file: includes/functions.php
byte_start: 8192
byte_end: 10240
byte_hash: 16_char_sha256_prefix
tokens: [greeting, support, diabetic, shoes, booking, ticket]
intent: greeting
language: en
confidence_inputs:
  source_count: 2
  language_match: true
  product_scope: true
```

# Unified Modality Plan

| Modality | Real current sources | Index text |
|---|---|---|
| Bytes/Text | PHP pages, blog JSON, README, `llms.txt`, OKF docs | byte ranges, hashes, headings, paragraphs, function names, route behavior |
| Structured data | JSON storage files, config constants, test assertions | fields, entities, statuses, allowed values |
| Images | product/condition/technology assets | filename, alt text, page section, blog featured image |
| Video/Reels | Instagram/YouTube URLs in reels storage/admin | canonical URL, platform, shortcode/video id, derived thumbnail |
| External dataset rows | HF Dataset Viewer rows/search | instruction, answer/context, dataset name, kind |

# Answer Generation Procedure

1. Detect intent and language.
2. Retrieve raw byte blocks, OKF concepts, and source-backed blocks.
3. Add query-specific HF rows only if the query is product/support relevant.
4. Penalize mismatched-language blocks unless the query asks for translation or multilingual context.
5. Compose an answer from stable facts first, then optional dataset behavior patterns.
6. Store bounded TTT memory only when the query is in scope and the snippet is useful.
7. Expose feedback controls and allow the visitor to provide no feedback.

# Current PHP Implementation

`flexifeet_support_byte_block_documents()` indexes curated raw files, including public pages, API endpoints, admin modules, tests, OKF model documents, `llms.txt`, `README.md`, and `storage/blog-posts.json`.

Each block stores:

* source file
* byte start/end
* byte length
* SHA-256 hash prefix
* block modality
* text extracted from the byte range

These blocks are added to `FlexiFeetSupport` training documents and appear as `Raw repository byte block` dataset documents.

# Citations

[1] [DiffusionGemma model file list](https://huggingface.co/trl-internal-testing/tiny-DiffusionGemmaForBlockDiffusion/tree/main)
[2] [DiffusionGemma config](https://huggingface.co/trl-internal-testing/tiny-DiffusionGemmaForBlockDiffusion/blob/main/config.json)
[3] [DiffusionGemma processor config](https://huggingface.co/trl-internal-testing/tiny-DiffusionGemmaForBlockDiffusion/blob/main/processor_config.json)
[4] [DiffusionGemma generation config](https://huggingface.co/trl-internal-testing/tiny-DiffusionGemmaForBlockDiffusion/blob/main/generation_config.json)
[5] [Local support model](../includes/functions.php)
