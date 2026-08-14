# Release gate — v1.2.0

The first GitHub release should be published only after the current plugin tree is validated on a disposable WordPress installation.

## Ownership decision

Before tagging:

- decide whether the repository will remain under `asllanmaciel` or be transferred to `WP24Horas`;
- if transferring, complete the transfer first;
- update canonical GitHub URLs after transfer;
- do not publish duplicate `v1.2.0` releases under both owners.

## Runtime validation

Verify at least:

- file upload import;
- update-by-slug behavior;
- publish/private permission downgrade to draft when required;
- category/tag capability behavior;
- REST import disabled by default;
- authenticated REST import when enabled;
- featured-image sideload;
- featured-image alt text;
- duplicate image reuse;
- rejection of invalid/unsafe featured-image URLs.

## Distribution validation

- run PHP syntax checks;
- choose one reproducible local packaging path:
  - Bash: `bash scripts/build-zip.sh` followed by `bash scripts/verify-zip.sh`;
  - PowerShell: `powershell -ExecutionPolicy Bypass -File scripts/build-zip.ps1` followed by `powershell -ExecutionPolicy Bypass -File scripts/verify-zip.ps1`;
- confirm the selected verifier accepts the canonical top-level directory and required plugin files;
- confirm repository-only/development files excluded by `.distignore` are absent;
- install `build/wp24h-md-importer.zip` on a clean WordPress instance;
- confirm plugin activation and the main import flow from that verified ZIP.

The release artifact must be the same ZIP that passed verification and clean-install testing. Both packaging paths implement the same artifact policy; a release does not need to be built twice on different operating systems.

## Version consistency

These values must agree before the tag:

```text
plugin header Version
WP24H_MD_IMPORTER_VERSION
readme.txt Stable tag
CHANGELOG release entry
Git tag
GitHub Release title
```

Target release: `v1.2.0`.
