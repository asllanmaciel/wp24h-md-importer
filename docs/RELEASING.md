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

At minimum on Bash-compatible systems:

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

### Bash / Unix-like environments

Build the distribution ZIP:

```bash
bash scripts/build-zip.sh
```

Verify the exact ZIP that would be released:

```bash
bash scripts/verify-zip.sh
```

The build uses an isolated temporary directory and validates that `rsync` and `zip` are available before packaging. The verifier is compatible with Bash 3.2-style environments.

### PowerShell / Windows

Build the same distribution shape without requiring WSL, `rsync`, or `zip` binaries:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-zip.ps1
```

Verify it with the PowerShell-native ZIP reader:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/verify-zip.ps1
```

The PowerShell scripts are written to remain compatible with Windows PowerShell 5.1 as well as newer PowerShell versions.

### Verification contract

Both verification paths check the same release policy:

- one canonical top-level directory (`wp24h-md-importer/`);
- required plugin files are present;
- the `includes/` runtime directory is present;
- `.github`, build scripts and repository-only tooling are absent;
- repository-only documentation/governance files excluded by `.distignore` are absent;
- no nested ZIP is included.

A release only needs one fully validated build path on the machine producing the artifact. Cross-platform parity exists so maintainers are not forced into one operating system or shell.

## First GitHub release

The current plugin version is `1.2.0`, but no GitHub tag/release has been published yet.

A first GitHub release may therefore be `v1.2.0` after the current tree is validated against a clean WordPress installation.

Recommended release order:

1. finish local/runtime validation;
2. confirm version fields are consistent;
3. update `CHANGELOG.md` if release contents changed;
4. build the ZIP using Bash or PowerShell;
5. run the matching verifier against that exact ZIP;
6. install the verified ZIP in a clean WordPress instance;
7. create immutable tag `v1.2.0` on the validated commit;
8. create the GitHub Release from that tag;
9. attach the same verified ZIP if desired.

## Repository transfer note

If the repository is transferred to `WP24Horas` before the release, update canonical GitHub URLs (Plugin URI, README and governance links) first, then tag under the organization repository. GitHub redirects are useful for compatibility but should not remain the preferred canonical documentation URL.

Do not create duplicate releases under both owners for the same version during the transfer window.
