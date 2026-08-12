# Releasing WP24H MD Importer

GitHub Actions are not required to publish a release. The default release path is local validation + local ZIP build + explicit tag/release creation.

## Version consistency

Before tagging, these values must agree:

```text
plugin header Version
WP24H_MD_IMPORTER_VERSION
readme.txt Stable tag
CHANGELOG release entry
Git tag
GitHub Release title
```

## Local validation

At minimum:

```bash
find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

On PowerShell:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

Then verify the plugin on a disposable WordPress installation, including:

- file upload import;
- update-by-slug behavior;
- permission downgrade from publish/private to draft when appropriate;
- categories/tags capability behavior;
- REST API disabled by default;
- authenticated REST import when enabled;
- featured-image sideload, alt text and duplicate reuse;
- invalid/unsafe featured-image URLs rejected.

## Build

Use the repository build script to create a distribution ZIP after validation:

```bash
bash scripts/build-zip.sh
```

Inspect the ZIP before release. Development-only files excluded by `.distignore` must not appear in the plugin package.

## First GitHub release

The current plugin version is `1.2.0`, but no GitHub tag/release has been published yet.

A first GitHub release may therefore be `v1.2.0` after the current tree is validated against a clean WordPress installation.

Recommended release order:

1. finish local/runtime validation;
2. confirm version fields are consistent;
3. update `CHANGELOG.md` if release contents changed;
4. build and inspect the ZIP;
5. create immutable tag `v1.2.0` on the validated commit;
6. create the GitHub Release from that tag;
7. attach the validated ZIP if desired;
8. verify the released ZIP installs cleanly.

## Repository transfer note

If the repository is transferred to `WP24Horas` before the release, update canonical GitHub URLs (Plugin URI, README and governance links) first, then tag under the organization repository. GitHub redirects are useful for compatibility but should not remain the preferred canonical documentation URL.

Do not create duplicate releases under both owners for the same version during the transfer window.
