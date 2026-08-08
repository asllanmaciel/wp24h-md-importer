# WP24H MD Importer

WordPress plugin for importing Markdown documents with YAML front matter into posts.

Designed to be suitable for submission to the official WordPress.org Plugin Directory while keeping development on GitHub.

## Features

- Imports `.md` and `.markdown` files.
- Parses a deliberately restricted YAML front matter schema without external dependencies.
- Creates posts or updates an existing post with the same slug.
- Supports post title, slug, date, status and excerpt.
- Creates categories and tags when the current user is allowed to manage them.
- Stores SEO title, meta description and source URLs.
- Integrates with Yoast SEO and Rank Math metadata when those plugins are active.
- Converts a practical Markdown subset to sanitized WordPress HTML.
- Uses WordPress capabilities, nonces, sanitization and escaping.
- Makes no external requests and performs no tracking.

## Front matter example

```yaml
---
title: "What changed in AI this week"
slug: "ai-weekly-update"
date: "2026-08-09"
status: "draft"
excerpt: "A practical summary of the most relevant AI developments."
categories:
  - Artificial Intelligence
tags:
  - AI
  - Business
seo_title: "AI weekly update: what matters now"
meta_description: "The most relevant AI developments and what they mean for developers and entrepreneurs."
sources:
  - "https://example.com/source"
---
```

Everything after the closing `---` is treated as Markdown post content.

## Installation

1. Download or build the plugin ZIP so the top-level folder is `wp24h-md-importer`.
2. In WordPress, go to **Plugins > Add New > Upload Plugin**.
3. Upload the ZIP and activate the plugin.
4. Go to **Tools > Import Markdown**.

## Supported Markdown

The dependency-free renderer intentionally covers a practical subset: headings, paragraphs, ordered and unordered lists, blockquotes, links, remote images, bold, italic, strikethrough, inline code, fenced code blocks and horizontal rules.

## WordPress.org

The repository includes the official `readme.txt` format, GPL-compatible licensing metadata, translation-ready strings, WordPress capability checks, nonce validation and a stable version header.

WordPress.org's SVN repository is intended for releases. GitHub remains the development repository; approved releases can later be mirrored to WordPress.org SVN.

## License

GPL-2.0-or-later.
