=== WP24H MD Importer ===
Contributors: asllanmaciel
Tags: markdown, importer, content, yaml, front matter
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Import Markdown files with YAML front matter into WordPress posts, including categories, tags, SEO metadata, sources, and optional authenticated REST automation.

== Description ==

WP24H MD Importer adds a simple importer under **Tools > Import Markdown**.

Upload a `.md` or `.markdown` file containing YAML front matter and Markdown content. The plugin creates a WordPress post or, optionally, updates an existing post with the same slug.

Supported front matter fields:

* `title` (required)
* `slug`
* `date`
* `status` (`draft`, `pending`, `publish`, or `private`)
* `excerpt`
* `categories`
* `tags`
* `seo_title`
* `meta_description`
* `sources`

The built-in Markdown parser supports headings, paragraphs, unordered and ordered lists, blockquotes, links, remote images, bold, italic, inline code, fenced code blocks, strikethrough, and horizontal rules.

No external service is required and the plugin does not send site data to third parties.

= REST API =

Version 1.1.0 adds an optional authenticated endpoint for automated imports. It is disabled by default and can only be enabled by an administrator under **Tools > Import Markdown**.

Endpoint:

`POST /wp-json/wp24h-md-importer/v1/import`

JSON body:

`{"markdown":"---\ntitle: Example\n---\n\nPost content","update_existing":true}`

The endpoint uses normal WordPress REST authentication and requires the authenticated user to have the `edit_posts` capability. WordPress Application Passwords over HTTPS are recommended for external automation.

= SEO metadata =

The plugin always stores SEO title and meta description in its own post metadata. If Yoast SEO or Rank Math is active, it also writes to their commonly used post metadata fields.

= Security =

The importer uses WordPress capabilities and nonces, validates uploaded file extension and size, sanitizes front matter fields, restricts generated HTML with `wp_kses_post()`, limits REST payloads to 2 MB, and does not allow users without publishing capability to force imported posts directly to published/private status.

= Development =

Development takes place at:
https://github.com/asllanmaciel/wp24h-md-importer

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wp24h-md-importer`, or install the ZIP from **Plugins > Add New > Upload Plugin**.
2. Activate **WP24H MD Importer**.
3. Go to **Tools > Import Markdown**.
4. Select a Markdown file and click **Import post**.
5. Optional: administrators can enable REST API imports on the same screen.

== Frequently Asked Questions ==

= Does it require a YAML PHP extension or Composer dependency? =

No. The plugin includes a deliberately restricted parser for the front matter schema documented above.

= What happens if a post with the same slug already exists? =

By default it is updated. You can disable that behavior on the import screen or set `update_existing` to false in REST requests.

= Can imported files publish posts automatically? =

Yes, when `status: publish` is present and the current WordPress user has permission to publish posts. Otherwise the importer falls back to draft status.

= Does the plugin contact external servers? =

No. Remote image URLs contained in Markdown are stored in post content as URLs, but the plugin itself does not fetch them during import.

= Is the REST API public? =

No. It is disabled by default. When enabled, requests must authenticate through WordPress and the authenticated user must have permission to edit posts.

== Changelog ==

= 1.1.0 =
* Added optional REST API imports.
* REST API is disabled by default and can only be enabled by administrators.
* Added WordPress capability checks for API requests.
* Added 2 MB REST payload limit.
* Added guidance for WordPress Application Password authentication.

= 1.0.0 =
* Initial release.
* Markdown import with restricted YAML front matter parsing.
* Create or update posts by slug.
* Categories, tags, SEO metadata, and source URL support.
* Optional Yoast SEO and Rank Math metadata integration when those plugins are active.

== Upgrade Notice ==

= 1.1.0 =
Adds an optional authenticated REST API for automated Markdown imports. The API remains disabled until an administrator explicitly enables it.
