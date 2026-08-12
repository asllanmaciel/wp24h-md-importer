# WP24H MD Importer

WordPress plugin for importing Markdown documents with YAML front matter into posts.

Designed to remain suitable for submission to the official WordPress.org Plugin Directory while keeping development on GitHub.

## Features

- Imports `.md` and `.markdown` files.
- Parses a deliberately restricted YAML front matter schema without external dependencies.
- Creates posts or updates an existing post with the same slug.
- Supports post title, slug, date, status and excerpt.
- Imports an optional remote featured image and stores accessible alt text.
- Reuses a previously sideloaded featured image when the same source URL is imported again.
- Creates categories and tags when the current user is allowed to manage them.
- Stores SEO title, meta description and source URLs.
- Integrates with Yoast SEO and Rank Math metadata when those plugins are active.
- Converts a practical Markdown subset to sanitized WordPress HTML.
- Provides an optional authenticated REST API for automation.
- Uses WordPress capabilities, nonces, sanitization and escaping.
- Performs no tracking and only makes an external request when a `featured_image` URL is explicitly supplied.

## Version and release status

Current plugin version: **1.2.0**.

No GitHub tag/release has been published yet. The first GitHub release can be `v1.2.0` after clean WordPress runtime validation and ZIP inspection. See [`docs/RELEASING.md`](docs/RELEASING.md).

## Front matter example

```yaml
---
title: "What changed in AI this week"
slug: "ai-weekly-update"
date: "2026-08-12"
status: "draft"
excerpt: "A practical summary of the most relevant AI developments."
categories:
  - Artificial Intelligence
tags:
  - AI
  - Business
seo_title: "AI weekly update: what matters now"
meta_description: "The most relevant AI developments and what they mean for developers and entrepreneurs."
featured_image: "https://example.com/images/ai-weekly-update.webp"
featured_image_alt: "Developer reviewing an AI systems dashboard"
sources:
  - "https://example.com/source"
---
```

Everything after the closing `---` is treated as Markdown post content.

### Featured images

`featured_image` is optional. When present, it must be an absolute public HTTP(S) URL pointing to a supported image type such as JPEG, PNG, GIF or WebP. The authenticated WordPress user must have the `upload_files` capability.

The plugin downloads the image with WordPress core media APIs, stores it in the Media Library, sets it as the post thumbnail and saves `featured_image_alt` as the attachment alt text. WordPress core's safe HTTP download path validates the URL and redirects before downloading arbitrary remote URLs.

The original source URL is stored on the attachment. Re-importing the same URL reuses the existing attachment instead of creating duplicate Media Library items, which makes repeated automation runs safer.

## Installation

1. Download or build the plugin ZIP so the top-level folder is `wp24h-md-importer`.
2. In WordPress, go to **Plugins > Add New > Upload Plugin**.
3. Upload the ZIP and activate the plugin.
4. Go to **Tools > Import Markdown**.

## REST API automation

The REST API is **disabled by default**. An administrator can enable it under **Tools > Import Markdown**.

Endpoint:

```text
POST /wp-json/wp24h-md-importer/v1/import
```

Request body:

```json
{
  "markdown": "---\ntitle: Example post\nstatus: draft\nfeatured_image: https://example.com/cover.webp\nfeatured_image_alt: Example cover\n---\n\n## Hello\n\nMarkdown content.",
  "update_existing": true
}
```

The endpoint uses standard WordPress REST authentication and requires the authenticated user to have `edit_posts`. Imports containing `featured_image` also require `upload_files`. For external automation, use HTTPS and a WordPress Application Password dedicated to the integration user.

Example with cURL:

```bash
curl --user "USERNAME:APPLICATION_PASSWORD" \
  --header "Content-Type: application/json" \
  --data @payload.json \
  https://example.com/wp-json/wp24h-md-importer/v1/import
```

The password must never be embedded in public source code or committed to Git.

## Supported Markdown

The dependency-free renderer intentionally covers a practical subset: headings, paragraphs, ordered and unordered lists, blockquotes, links, remote images, bold, italic, strikethrough, inline code, fenced code blocks and horizontal rules.

Remote images written inside the Markdown body remain remote URLs in the generated HTML. Only the explicit `featured_image` front matter field is downloaded into the WordPress Media Library.

## WordPress.org

The repository includes the official `readme.txt` format, GPL-compatible licensing metadata, translation-ready strings, WordPress capability checks, nonce validation and a stable version header.

WordPress.org's SVN repository is intended for official releases. GitHub remains the development repository; approved releases can later be mirrored to WordPress.org SVN.

## Development and release

- [`CONTRIBUTING.md`](CONTRIBUTING.md)
- [`SECURITY.md`](SECURITY.md)
- [`CHANGELOG.md`](CHANGELOG.md)
- [`docs/RELEASING.md`](docs/RELEASING.md)

## License

GPL-2.0-or-later.
