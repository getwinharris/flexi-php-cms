---
type: Content Corpus
title: Blog and SEO Corpus
description: Published blog topics, Markdown rendering, SEO scoring, sitemap, robots, analytics tags, and agent discovery.
resource: storage/blog-posts.json
tags: [blog, seo, markdown, analytics, corpus]
timestamp: 2026-07-02T15:30:00Z
---

# Published Topics

The current blog corpus includes published educational posts on:

* custom diabetic shoes in Malaysia
* custom offload insoles for pressure relief
* flat feet insoles in Kuala Lumpur
* diabetic socks
* 3D foot scanning
* diabetic foot ulcer footwear warning signs
* bunions, hammer toes, and wide feet
* Charcot foot and custom footwear
* partial foot amputation shoe fillers and insoles
* choosing a custom insole provider in Malaysia

# Rendering

`render_post_content()` supports Markdown-style headings, lists, blockquotes, inline links, inline code, emphasis, and images. This lets admin-authored blog content behave more like GitHub README formatting while using site CSS.

# SEO and Discovery

The site uses configurable default SEO title/description/social image, optional Google Analytics measurement id, optional Search Console verification, sitemap generation, robots rules, and agent discovery tags for `llms.txt` and `mcp.php`.

# Model Role

Published blog posts are included in `FlexiFeetSupport` training documents. They provide domain-specific explanations that support service answers and suggestions.

# Citations

[1] [Blog storage](../storage/blog-posts.json)
[2] [Markdown rendering functions](../includes/functions.php)
[3] [Public blog index](../blog.php)
[4] [Public blog post view](../blog-post.php)
[5] [Robots file](../robots.txt)
[6] [Sitemap generator](../sitemap.php)
