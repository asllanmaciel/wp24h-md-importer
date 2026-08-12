# Contributing to WP24H MD Importer

Thanks for helping improve the plugin.

## Before opening a pull request

1. Keep changes focused and backward-compatible whenever possible.
2. Preserve the `wp24h_` prefix and existing public behavior unless a change is intentionally documented.
3. Follow WordPress coding and security practices.
4. Test imports through both the admin interface and REST API when the change affects shared importer logic.
5. Verify capability checks, sanitization, escaping and nonce handling for admin-facing changes.
6. Do not commit credentials, production URLs, customer content or private Markdown files.

## Compatibility

The plugin currently targets WordPress 6.5+ and PHP 7.4+.

Changes should avoid introducing a higher PHP requirement without discussion because compatibility is part of the plugin's public contract.

## Security

Do not open public issues for vulnerabilities with exploitable details. Follow [SECURITY.md](SECURITY.md).

## Pull requests

Describe:

- the problem being solved;
- the approach taken;
- relevant WordPress/PHP versions tested;
- security or compatibility implications;
- manual validation performed.

Small, reviewable pull requests are preferred over broad unrelated refactors.
