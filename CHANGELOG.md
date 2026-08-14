# Changelog

All notable changes to WP24H MD Importer are documented here.

The project follows Semantic Versioning for plugin versions. Versioned sections below describe the plugin's source/version history and do not by themselves imply that a matching Git tag or GitHub Release exists. GitHub distribution releases are published only after the documented release gate passes.

## [Unreleased]

### Added

- Security policy for private vulnerability reporting.
- Contribution guidelines for compatibility, security and review expectations.
- Manual distribution build script for producing a clean WordPress plugin ZIP without GitHub Actions.
- Distribution exclusion rules through `.distignore`.
- Release gate documentation covering owner/transfer decision, runtime validation, ZIP verification and tag consistency.
- Structural release ZIP verifier checking canonical top-level layout, required files and forbidden development/repository-only paths.

### Changed

- GitHub Actions PHP lint is manual-only while CI usage is being optimized, avoiding automatic Actions consumption on every push and pull request.
- Release builds use an isolated temporary directory with cleanup and explicit `rsync`/`zip` dependency checks instead of deleting a shared build directory.

## [1.2.0] - 2026-08-12

### Added

- `featured_image` front matter field for remote featured image imports.
- `featured_image_alt` support for accessible attachment alt text.
- Featured image attachment ID in importer responses.

### Changed

- Repeated imports reuse an attachment previously sideloaded from the same source URL instead of creating duplicate Media Library entries.

### Security

- Featured image imports require the `upload_files` capability.
- Remote image URLs are restricted to validated HTTP(S) URLs and downloaded through WordPress core safe HTTP/media APIs.

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
