# WordPress Compatibility Harness Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (- [ ]) syntax for tracking.

**Goal:** Add a permanent Docker-based compatibility gate for WP24H MD Importer, initially exercising WordPress 7.1 and 7.0.4 without changing Tested up to.

**Architecture:** Docker Compose runs MariaDB, WordPress, WP-CLI and a local fixture HTTP server. A PHP runner executes in the active WordPress container and calls the real WP24H_MD_Importer APIs. PowerShell and shell wrappers select the Core version, capture a JSON report, and remove only prefixed Docker resources.

**Tech Stack:** Docker Compose, WordPress image, MariaDB, WP-CLI, PHP, PowerShell, POSIX shell.

**Spec:** docs/superpowers/specs/2026-08-21-wp-compatibility-harness-design.md

## Global Constraints

- Do not modify readme.txt, plugin version, release tags, CI, staging, or production.
- The only initial Core targets are 7.1 and 7.0.4.
- Fixtures are local; no test downloads a public asset.
- A failed check, unavailable image, unhealthy service, PHP warning, notice, deprecation, or fatal returns non-zero.
- Cleanup removes only Docker resources named with the wp24h-compat- prefix.
- All changes remain on codex/wp-compatibility-harness.

---

## File Structure

- Create: docker/compatibility.compose.yml — MariaDB, WordPress, WP-CLI and fixture service.
- Create: tests/compatibility/run.php — real WordPress/plugin checks plus JSON report.
- Create: tests/compatibility/fixtures/basic.md and complete.md — deterministic Markdown.
- Create: tests/compatibility/fixtures/featured.png, featured.jpg and featured.webp — local media.
- Create: scripts/compatibility-check.ps1 and scripts/compatibility-check.sh — versioned execution wrappers.
- Create: docs/compatibility.md — commands, matrix, semantics and troubleshooting.
- Create: reports/compatibility/.gitkeep; modify .gitignore for generated JSON.

### Task 1: Add isolated Docker topology and fixtures

**Files:**
- Create: docker/compatibility.compose.yml
- Create: tests/compatibility/fixtures/basic.md
- Create: tests/compatibility/fixtures/complete.md
- Create: tests/compatibility/fixtures/featured.png
- Create: tests/compatibility/fixtures/featured.jpg
- Create: tests/compatibility/fixtures/featured.webp

**Interfaces:**
- Consumes: environment variable WP_COMPAT_VERSION.
- Produces: project-scoped services db, wordpress, cli and fixtures.

- [x] **Step 1: Write Compose services**

Implement db, wordpress, cli and fixtures. Required boundaries:

~~~yaml
services:
  db:
    image: mariadb:11.4
    environment:
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress
      MYSQL_ROOT_PASSWORD: wordpress
    healthcheck:
      test: ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
  wordpress:
    image: wordpress:${WP_COMPAT_VERSION}-php8.3-apache
    depends_on:
      db:
        condition: service_healthy
    volumes:
      - ..:/var/www/html/wp-content/plugins/wp24h-md-importer:ro
  fixtures:
    image: nginx:1.27-alpine
    volumes:
      - ../tests/compatibility/fixtures:/usr/share/nginx/html:ro
~~~

Configure cli with the same WordPress volume and db network. Wrapper scripts set Compose project names wp24h-compat-71 and wp24h-compat-704.

- [x] **Step 2: Write Markdown fixtures**

basic.md must contain:

~~~markdown
---
title: Compatibilidade básica
slug: compatibilidade-basica
status: draft
---

# Conteúdo básico

Este post confirma a importação mínima.
~~~

complete.md must include title, slug, draft status, excerpt, categories, tags, seo_title, meta_description, sources, featured_image http://fixtures/featured.png, featured_image_alt, and a body with heading, list, link and bold text.

- [x] **Step 3: Add valid local images**

Create small valid PNG/JPEG/WebP fixtures with no user metadata. Verify signatures:

~~~powershell
Format-Hex tests/compatibility/fixtures/featured.png -Count 8
Format-Hex tests/compatibility/fixtures/featured.jpg -Count 3
Format-Hex tests/compatibility/fixtures/featured.webp -Count 12
~~~

Expected: PNG begins 89 50 4E 47; JPEG begins FF D8 FF; WebP contains WEBP at offset 8.

- [x] **Step 4: Verify isolated service startup**

~~~powershell
$env:WP_COMPAT_VERSION = "7.1"
docker compose -p wp24h-compat-71 -f docker/compatibility.compose.yml up -d db fixtures
docker compose -p wp24h-compat-71 -f docker/compatibility.compose.yml ps
~~~

Expected: db is healthy and fixtures is running.

- [x] **Step 5: Clean up and commit**

~~~powershell
docker compose -p wp24h-compat-71 -f docker/compatibility.compose.yml down --volumes --remove-orphans
git add docker/compatibility.compose.yml tests/compatibility/fixtures
git commit -m "test: add isolated WordPress compatibility fixtures"
~~~

### Task 2: Build test-first compatibility runner

**Files:**
- Create: tests/compatibility/run.php
- Reference: includes/class-wp24h-md-importer.php
- Reference: includes/class-wp24h-md-rest-api.php

**Interfaces:**
- Consumes: active WordPress plugin, local fixture host http://fixtures, and WP_COMPAT_REPORT.
- Produces: JSON with WordPress/PHP/plugin versions, named checks and PASS or FAIL.

- [x] **Step 1: Write the first failing basic-import check**

Start run.php with a named assertion helper and add:

~~~php
$result = WP24H_MD_Importer::import( file_get_contents( $fixture_dir . '/basic.md' ) );
$assert( is_int( $result['post_id'] ) && $result['post_id'] > 0, 'basic import creates a post' );
$assert( 'compatibilidade-basica' === get_post_field( 'post_name', $result['post_id'] ), 'basic import keeps slug' );
~~~

- [x] **Step 2: Verify RED before WordPress bootstrap**

~~~powershell
php tests/compatibility/run.php
~~~

Expected: failure because WordPress bootstrap is unavailable. This proves the runner cannot pass outside the target environment.

- [x] **Step 3: Implement bootstrap and activation guard**

Use WP_COMPAT_WP_ROOT to load wp-load.php. Abort with a clear error if wp24h-md-importer/wp24h-md-importer.php is inactive. Do not load plugin classes manually.

- [x] **Step 4: Verify basic-import GREEN inside WP-CLI**

~~~powershell
docker compose -p wp24h-compat-71 -f docker/compatibility.compose.yml run --rm cli php /var/www/html/wp-content/plugins/wp24h-md-importer/tests/compatibility/run.php
~~~

Expected: basic import passes and every failure reports its check name.

- [x] **Step 5: Add one check at a time, red then green**

Add checks in this order, running the command above after each:

1. complete fixture creates taxonomy and importer SEO metadata;
2. reimport returns updated=true and keeps post ID;
3. REST route is absent with wp24h_md_api_enabled=0;
4. REST route registers with wp24h_md_api_enabled=1;
5. user without upload_files receives RuntimeException for complete fixture;
6. administrator import creates PNG thumbnail and reimport reuses attachment ID;
7. invalid URL throws and creates no attachment;
8. error handler reports no warning, notice, deprecation or fatal.

Each added check must first fail for its intended missing condition, then pass after the minimal harness/environment correction.

- [x] **Step 6: Emit report and fail correctly**

On success write reports/compatibility/<version>.json:

~~~json
{
  "wordpress_version": "7.1",
  "php_version": "8.x",
  "plugin_version": "1.2.0",
  "status": "PASS",
  "checks": []
}
~~~

On exception write status FAIL, failed check and message, then exit 1.

- [x] **Step 7: Run full runner and commit**

~~~powershell
docker compose -p wp24h-compat-71 -f docker/compatibility.compose.yml run --rm cli php /var/www/html/wp-content/plugins/wp24h-md-importer/tests/compatibility/run.php
git add tests/compatibility/run.php
git commit -m "test: add WordPress compatibility runner"
~~~

Expected: all named checks pass and report is produced.

### Task 3: Add portable wrappers, hygiene and documentation

**Files:**
- Create: scripts/compatibility-check.ps1
- Create: scripts/compatibility-check.sh
- Create: docs/compatibility.md
- Create: reports/compatibility/.gitkeep
- Modify: .gitignore

**Interfaces:**
- Consumes: version argument exactly equal to 7.1 or 7.0.4.
- Produces: exit 0 only on PASS; cleanup after success or failure.

- [x] **Step 1: Write failing invalid-version behavior**

~~~powershell
./scripts/compatibility-check.ps1 -WordPressVersion 6.5
~~~

Expected: non-zero and “supported versions: 7.1, 7.0.4”.

~~~sh
./scripts/compatibility-check.sh 6.5
~~~

Expected: the equivalent non-zero result.

- [x] **Step 2: Implement wrapper lifecycle**

~~~text
validate Docker daemon
→ validate version allowlist
→ docker compose pull
→ docker compose up -d
→ wp core install and wp plugin activate wp24h-md-importer
→ run compatibility runner
→ copy report to reports/compatibility
→ docker compose down --volumes --remove-orphans
→ return runner exit code
~~~

Use try/finally in PowerShell and trap in shell. The wrappers must not remove Docker resources outside their Compose project.

- [x] **Step 3: Add report hygiene**

Add to .gitignore:

~~~text
/reports/compatibility/*.json
!/reports/compatibility/.gitkeep
~~~

Create the .gitkeep file.

- [x] **Step 4: Document commands and BLOCKED semantics**

docs/compatibility.md must contain:

~~~powershell
./scripts/compatibility-check.ps1 -WordPressVersion 7.1
./scripts/compatibility-check.ps1 -WordPressVersion 7.0.4
~~~

~~~sh
./scripts/compatibility-check.sh 7.1
./scripts/compatibility-check.sh 7.0.4
~~~

Explain that unavailable images yield BLOCKED and never authorize Tested up to changes.

- [x] **Step 5: Verify invalid versions and commit**

Run both invalid-version commands; each must exit non-zero. Then:

~~~powershell
git add scripts/compatibility-check.ps1 scripts/compatibility-check.sh docs/compatibility.md .gitignore reports/compatibility/.gitkeep
git commit -m "test: add compatibility harness commands"
~~~

### Task 4: Execute matrix and protect metadata follow-up

**Files:**
- Modify: m3-portfolio-os triage record only after final evidence exists
- Do not modify: readme.txt

**Interfaces:**
- Consumes: complete wrappers and runner.
- Produces: reports for 7.1 and 7.0.4.

- [x] **Step 1: Run 7.1**

~~~powershell
./scripts/compatibility-check.ps1 -WordPressVersion 7.1
~~~

Expected: exit 0 and reports/compatibility/7.1.json status PASS. If unavailable, report BLOCKED and stop.

- [x] **Step 2: Run 7.0.4**

~~~powershell
./scripts/compatibility-check.ps1 -WordPressVersion 7.0.4
~~~

Expected: exit 0 and reports/compatibility/7.0.4.json status PASS.

- [x] **Step 3: Verify cleanup and scope**

~~~powershell
docker ps -a --filter "name=wp24h-compat-"
docker volume ls --filter "name=wp24h-compat-"
git diff --check
git status --short
~~~

Expected: no harness resources remain, no generated JSON is tracked and readme.txt is unchanged.

- [ ] **Step 4: Create separate metadata PR only on two PASS reports**

Only after both reports are PASS, update the hub triage evidence and create a new PR containing only:

~~~text
Tested up to: 7.0
→
Tested up to: 7.1
~~~

On FAIL or BLOCKED, preserve metadata and record evidence.

## Plan Self-Review

- Spec coverage: Tasks 1–3 build isolated Docker services, local fixtures, test-first runner, reports, portable commands, cleanup and documentation. Task 4 executes the matrix while protecting the compatibility declaration.
- Placeholder scan: no unresolved markers are present.
- Safety check: no production, CI, staging, release, tag or Tested up to change is included before two PASS reports.
