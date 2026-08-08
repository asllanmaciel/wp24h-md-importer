=== WP24H MD Importer ===
Contributors: asllanmaciel
Tags: markdown, importer, content, yaml, front matter
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Import Markdown files with YAML front matter into WordPress posts, including categories, tags, SEO metadata, and source URLs.

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

= SEO metadata =

The plugin always stores SEO title and meta description in its own post metadata. If Yoast SEO or Rank Math is active, it also writes to their commonly used post metadata fields.

= Security =

The importer uses WordPress capabilities and nonces, validates the uploaded file extension and size, sanitizes front matter fields, restricts generated HTML with `wp_kses_post()`, and does not allow users without publishing capability to force imported posts directly to published/private status.

= Development =

Development takes place at:
https://github.com/asllanmaciel/wp24h-md-importer

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wp24h-md-importer`, or install the ZIP from **Plugins > Add New > Upload Plugin**.
2. Activate **WP24H MD Importer**.
3. Go to **Tools > Import Markdown**.
4. Select a Markdown file and click **Import post**.

== Frequently Asked Questions ==

= Does it require a YAML PHP extension or Composer dependency? =

No. The plugin includes a deliberately restricted parser for the front matter schema documented above.

= What happens if a post with the same slug already exists? =

By default it is updated. You can disable that behavior on the import screen to create a new post instead.

= Can imported files publish posts automatically? =

Yes, when `status: publish` is present and the current WordPress user has permission to publish posts. Otherwise the importer falls back to draft status.

= Does the plugin contact external servers? =

No. Remote image URLs contained in Markdown are stored in post content as URLs, but the plugin itself does not fetch them during import.

== Changelog ==

= 1.0.0 =
* Initial release.
* Markdown import with restricted YAML front matter parsing.
* Create or update posts by slug.
* Categories, tags, SEO metadata, and source URL support.
* Optional Yoast SEO and Rank Math metadata integration when those plugins are active.

== Upgrade Notice ==

= 1.0.0 =
Initial public release.
