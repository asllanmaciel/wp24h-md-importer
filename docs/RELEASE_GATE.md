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
- build with `bash scripts/build-zip.sh`;
- inspect the ZIP against `.distignore`;
- install the ZIP on a clean WordPress instance;
- confirm plugin activation and the main import flow from the ZIP.

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
