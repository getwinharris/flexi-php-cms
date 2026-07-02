---
type: Frontend Surface
title: Frontend Support Bot
description: Floating support panel behavior, chat form, booking/ticket detail forms, suggestions, and feedback controls.
resource: assets/app.js
tags: [frontend, support-bot, feedback, booking]
timestamp: 2026-07-02T15:30:00Z
---

# UI Components

The public homepage includes a fixed support bot panel with:

* support toggle button
* initial bot instructions
* message form
* `Book Fitting` and `Create Ticket` quick actions
* booking/ticket detail form
* availability status display
* bot suggestions
* Like/Dislike feedback controls per bot response

# Behavior

Submitting a message posts `action=message` to `api/support-bot.php`. Bot replies can trigger booking or ticket mode. Booking mode loads available slots for the selected date. Feedback buttons post `action=feedback` with response id, rating, intent, language, and original message terms.

# Validation

The rendered bot has been browser-tested on desktop and mobile. A `hello` prompt returns a greeting entry response, shows suggestions, exposes feedback buttons, and clicking Like shows `Saved` without console errors.

# Citations

[1] [Frontend JavaScript](../assets/app.js)
[2] [Frontend styles](../assets/styles.css)
[3] [Homepage support markup](../index.php)
[4] [Support API](../api/support-bot.php)
