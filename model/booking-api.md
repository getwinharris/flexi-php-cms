---
type: API Endpoint
title: Booking and Support APIs
description: Public support bot API, appointment booking behavior, and remote MCP booking tools.
resource: api/support-bot.php
tags: [api, booking, support, mcp]
timestamp: 2026-07-02T15:30:00Z
---

# Support Bot API

`api/support-bot.php` accepts POST form requests.

| Action | Behavior |
|---|---|
| `message` | Returns a `FlexiFeetSupport` reply with intent, language, response id, suggestions, learned terms, and sources when relevant. |
| `feedback` | Stores `like` or `dislike` for a response id and may create local TTT memory for liked responses. |
| `availability` | Returns appointment availability for a preferred date. |
| `booking` | Validates fields and creates an appointment if the requested slot is open. |
| `ticket` | Creates a support ticket. |

# MCP Endpoint

`mcp.php` exposes JSON-RPC tools:

| Tool | Description |
|---|---|
| `get_available_slots` | Return available appointment slots for a date using current appointment records. |
| `book_appointment` | Create a Flexi Feet appointment request after a slot has been confirmed. |

Required MCP booking fields are `name`, `phone`, `email`, `preferred_date`, `preferred_time`, and `visit_type`.

# Mail Behavior

Appointment creation calls `notify_booking_emails()`. The current rule is deliberate: owner notification is skipped by the website because mailbox forwarding handles owner copies; the website sends only a customer confirmation when a valid customer email exists.

# Citations

[1] [Support bot API](../api/support-bot.php)
[2] [MCP endpoint](../mcp.php)
[3] [Appointment and mail functions](../includes/functions.php)
[4] [Public agent instructions](../llms.txt)
