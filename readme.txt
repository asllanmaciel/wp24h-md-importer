=== WP24H MD Importer ===
Contributors: asllanmaciel
Tags: markdown, importer, content, yaml, front matter
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Import Markdown files with YAML front matter into WordPress posts, including categories, tags, SEO metadata, featured images, sources, and optional authenticated REST automation.

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
* `featured_image` (absolute public HTTP(S) image URL)
* `featured_image_alt`
* `sources`

When `featured_image` is supplied, the current user must have the `upload_files` capability. The image is downloaded using WordPress core media APIs, stored in the Media Library and set as the post thumbnail. The original source URL is stored on the attachment so repeated imports can reuse the same image instead of creating duplicates.

The built-in Markdown parser supports headings, paragraphs, unordered and ordered lists, blockquotes, links, remote images, bold, italic, inline code, fenced code blocks, strikethrough, and horizontal rules.

No external service is required. The plugin does not perform tracking and only makes an external request when a `featured_image` URL is explicitly supplied by an authorized importer.

= REST API =

The optional authenticated endpoint for automated imports is disabled by default and can only be enabled by an administrator under **Tools > Import Markdown**.

Endpoint:

`POST /wp-json/wp24h-md-importer/v1/import`

JSON body:

`{"markdown":"---\ntitle: Example\nfeatured_image: https://example.com/cover.webp\nfeatured_image_alt: Example cover\n---\n\nPost content","update_existing":true}`

The endpoint uses normal WordPress REST authentication and requires the authenticated user to have the `edit_posts` capability. Imports that include `featured_image` additionally require `upload_files`. WordPress Application Passwords over HTTPS are recommended for external automation.

= SEO metadata =

The plugin always stores SEO title and meta description in its own post metadata. If Yoast SEO or Rank Math is active, it also writes to their commonly used post metadata fields.

= Security =

The importer uses WordPress capabilities and nonces, validates uploaded file extension and size, sanitizes front matter fields, restricts generated HTML with `wp_kses_post()`, limits REST payloads to 2 MB, and does not allow users without publishing capability to force imported posts directly to published/private status.

Remote featured images are only accepted from validated HTTP(S) URLs and are downloaded through WordPress core's safe HTTP/media APIs. WordPress validates the URL and redirects to reduce SSRF risk before the image is saved to the Media Library.

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

= Can it import a featured image? =

Yes. Add an absolute public HTTP(S) URL in `featured_image` and optional accessible text in `featured_image_alt`. The importing user must be allowed to upload files. Re-importing the same image URL reuses the existing sideloaded attachment.

= Does the plugin contact external servers? =

Only when `featured_image` is explicitly present. Images embedded in the Markdown body remain remote URLs in post content and are not downloaded by the importer.

= Is the REST API public? =

No. It is disabled by default. When enabled, requests must authenticate through WordPress and the authenticated user must have permission to edit posts.

== Changelog ==

= 1.2.0 =
* Added `featured_image` and `featured_image_alt` front matter fields.
* Remote featured images are sideloaded into the WordPress Media Library and assigned as the post thumbnail.
* Repeated imports reuse an attachment previously imported from the same source URL.
* Featured image imports require the `upload_files` capability and validated HTTP(S) URLs.

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

= 1.2.0 =
Adds featured image imports from front matter with safe WordPress media sideloading, alt text support and source-URL reuse for repeated automation runs.
