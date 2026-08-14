# Releasing WP24H MD Importer

GitHub Actions are not required to publish a release. The default release path is local validation + local ZIP build/verification + explicit tag/release creation.

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

## Build and verify

Use the repository build script to create the distribution ZIP:

```bash
bash scripts/build-zip.sh
```

The build uses an isolated temporary directory and validates that `rsync` and `zip` are available before packaging.

Then verify the exact ZIP that would be released:

```bash
bash scripts/verify-zip.sh
```

The verifier checks:

- one canonical top-level directory (`wp24h-md-importer/`);
- required plugin files are present;
- `.github`, build scripts and build artifacts are absent;
- repository-only documentation/governance files excluded by `.distignore` are absent;
- no nested ZIP is included.

Both scripts intentionally use shell constructs compatible with older Bash environments, including Bash 3.2-style array population in the verifier.

## First GitHub release

The current plugin version is `1.2.0`, but no GitHub tag/release has been published yet.

A first GitHub release may therefore be `v1.2.0` after the current tree is validated against a clean WordPress installation.

Recommended release order:

1. finish local/runtime validation;
2. confirm version fields are consistent;
3. update `CHANGELOG.md` if release contents changed;
4. build the ZIP;
5. run `scripts/verify-zip.sh` against that ZIP;
6. install the verified ZIP in a clean WordPress instance;
7. create immutable tag `v1.2.0` on the validated commit;
8. create the GitHub Release from that tag;
9. attach the same verified ZIP if desired.

## Repository transfer note

If the repository is transferred to `WP24Horas` before the release, update canonical GitHub URLs (Plugin URI, README and governance links) first, then tag under the organization repository. GitHub redirects are useful for compatibility but should not remain the preferred canonical documentation URL.

Do not create duplicate releases under both owners for the same version during the transfer window.
