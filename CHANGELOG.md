# Changelog

All notable changes to WP24H MD Importer are documented here.

The project follows Semantic Versioning.

## [1.1.0] - 2026-08-08

### Added

- Optional authenticated REST API for automated Markdown imports.
- Administrator setting to enable or disable REST imports.
- Support for WordPress Application Password authentication through the standard REST API.
- REST payload limit of 2 MB.
- API documentation and automation examples.

### Security

- REST API is disabled by default.
- API access requires an authenticated WordPress user with the `edit_posts` capability.
- Existing publish capability rules continue to apply to API imports.

## [1.0.0] - 2026-08-08

### Added

- Initial Markdown importer.
- Restricted YAML front matter parser.
- Markdown-to-HTML conversion without external dependencies.
- Create or update posts by slug.
- Categories and tags support.
- SEO title and meta description support.
- Source URL metadata.
- Optional Yoast SEO and Rank Math metadata integration.
