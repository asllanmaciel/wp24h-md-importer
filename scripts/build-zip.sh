#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SLUG="wp24h-md-importer"
BUILD_DIR="${ROOT}/build"
PACKAGE_DIR="${BUILD_DIR}/${SLUG}"
ZIP_FILE="${BUILD_DIR}/${SLUG}.zip"

rm -rf "${BUILD_DIR}"
mkdir -p "${PACKAGE_DIR}"

cd "${ROOT}"

# Copy the repository into a clean plugin directory while honoring .distignore.
rsync -a --delete --exclude-from=.distignore ./ "${PACKAGE_DIR}/"

# Basic release sanity checks.
test -f "${PACKAGE_DIR}/${SLUG}.php"
test -f "${PACKAGE_DIR}/readme.txt"
test -f "${PACKAGE_DIR}/LICENSE"
test -d "${PACKAGE_DIR}/includes"

cd "${BUILD_DIR}"
zip -qr "${ZIP_FILE}" "${SLUG}"

echo "Built ${ZIP_FILE}"
