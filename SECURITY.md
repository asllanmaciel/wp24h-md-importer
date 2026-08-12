# Security Policy

## Supported versions

Security fixes are applied to the latest published version of WP24H MD Importer.

## Reporting a vulnerability

Please do not open a public issue containing exploit details, credentials, private URLs, customer data, or reproduction data from a production WordPress site.

Report security concerns privately through GitHub's security reporting features when available, or contact the maintainer through the channels listed on the GitHub profile.

Please include:

- affected plugin version;
- WordPress and PHP versions;
- clear reproduction steps;
- expected and observed behavior;
- security impact;
- suggested mitigation, if known.

## Security model

WP24H MD Importer relies on WordPress capabilities and authentication for imports. The REST endpoint is disabled by default and, when enabled, requires an authenticated user with the appropriate WordPress capabilities. Imported HTML is filtered through WordPress sanitization APIs and remote featured images are handled through WordPress core media APIs.

Security reports concerning capability bypasses, unsafe remote requests, content sanitization, file handling, REST authentication, or unintended publication are in scope.
