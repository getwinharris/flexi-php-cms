---
type: Agent Instruction
title: Flexi Feet Repository Agent Instructions
description: Read-order, grounding, and editing rules for agents working in this Flexi Feet PHP CMS.
resource: AGENTS.md
tags: [agents, okf, support-model, flexifeet]
timestamp: 2026-07-02T15:30:00Z
---

# Read Order

1. Read [index.md](index.md) first. It is the OKF root index for this repository.
2. Read the relevant concept files under [model/](model/) before changing support, admin, blog, SEO, booking, reels, or MCP behavior.
3. Read source files cited by the concept document before editing code. The concept documents are model memory, not a replacement for code inspection.

# Grounding Rules

Use real repository facts only. Do not invent placeholders, fake neural weights, fake dataset rows, fake endpoints, or fake admin features.

The local model is `FlexiFeetSupport`. It is a PHP semantic/document model whose "weights" are raw file byte blocks, OKF documents, JSON content, dataset rows, feedback signals, and local TTT memory. It is not a trained neural checkpoint and should not be described as one.

When answering support-model questions:

* Prefer [model/support-model.md](model/support-model.md) and [model/block-diffusion-indexing.md](model/block-diffusion-indexing.md).
* Keep medical claims careful. Flexi Feet provides footwear, insoles, socks, assessment, fitting, and support services; do not claim cures or guaranteed medical outcomes.
* For urgent medical issues, advise contacting a qualified healthcare professional or emergency service.

# Editing Rules

* Keep the PHP CMS self-contained and Hostinger-friendly.
* Use `apply_patch` for manual edits.
* Run PHP lint and `php tests/run.php` after support, admin, API, model, or Markdown changes.
* Preserve user or generated work already present in the repository.
* Do not send owner notification email from the website for new appointments; mailbox forwarding handles owner copies. The website only sends customer confirmations.

# Runtime Pointers

* Public website: `index.php`
* Support API: `api/support-bot.php`
* MCP endpoint: `mcp.php`
* Core model/functions: `includes/functions.php`
* Config constants: `includes/config.php`
* Tests: `tests/run.php`

# Citations

[1] [Root OKF index](index.md)
[2] [Support model concept](model/support-model.md)
[3] [Block diffusion indexing concept](model/block-diffusion-indexing.md)
