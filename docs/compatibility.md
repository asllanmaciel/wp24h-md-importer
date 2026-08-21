# WordPress compatibility harness

The compatibility harness runs the plugin in a disposable Docker Compose project with MariaDB, WordPress, WP-CLI and an HTTP server for local Markdown and image fixtures. It supports only the current verification matrix:

- WordPress 7.1
- WordPress 7.0.4

Run it from the repository root:

```powershell
./scripts/compatibility-check.ps1 -WordPressVersion 7.1
./scripts/compatibility-check.ps1 -WordPressVersion 7.0.4
```

```sh
./scripts/compatibility-check.sh 7.1
./scripts/compatibility-check.sh 7.0.4
```

Each command creates `reports/compatibility/<version>.json`, then removes only its own Docker Compose project, volumes and containers (`wp24h-compat-71` or `wp24h-compat-704`). The reports are intentionally ignored by Git.

## What is verified

The runner activates the installed plugin and checks Markdown import, slug-preserving reimport, categories, tags, SEO metadata, REST opt-in behavior, capability enforcement, PNG/JPEG/WebP featured images, attachment reuse, rejected invalid media and PHP warnings/notices.

Fixtures are served from the internal Docker hostname `fixtures`; the runner permits that hostname only inside the test process so WordPress can exercise its real media download path without downloading a public asset. The plugin continues to require validated public HTTP(S) URLs in normal use.

## Results and troubleshooting

`PASS` means all named checks succeeded with no PHP warnings, notices, deprecations or fatals. `FAIL` means a compatibility check failed. `BLOCKED` means Docker could not obtain a required image or service and is not evidence of compatibility.

The harness publishes no host port, so it does not conflict with local WordPress projects. Make sure the Docker daemon is running before execution. A `BLOCKED` or `FAIL` result never authorizes an update to the plugin's `Tested up to` metadata.
