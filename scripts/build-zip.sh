#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SLUG="wp24h-md-importer"
OUTPUT_DIR="${ROOT}/build"
TEMP_BASE="${RUNNER_TEMP:-${TMPDIR:-/tmp}}"
TEMP_ROOT="$(mktemp -d "${TEMP_BASE}/${SLUG}.XXXXXX")"
PACKAGE_DIR="${TEMP_ROOT}/${SLUG}"
ZIP_FILE="${OUTPUT_DIR}/${SLUG}.zip"

cleanup() {
  rm -rf "${TEMP_ROOT}"
}
trap cleanup EXIT

for command_name in rsync zip; do
  if ! command -v "${command_name}" >/dev/null 2>&1; then
    printf 'Required command not found: %s\n' "${command_name}" >&2
    exit 1
  fi
done

mkdir -p "${PACKAGE_DIR}" "${OUTPUT_DIR}"

rsync -a --delete --exclude-from="${ROOT}/.distignore" "${ROOT}/" "${PACKAGE_DIR}/"

for required_path in \
  "${PACKAGE_DIR}/${SLUG}.php" \
  "${PACKAGE_DIR}/readme.txt" \
  "${PACKAGE_DIR}/LICENSE" \
  "${PACKAGE_DIR}/includes"
do
  if [[ ! -e "${required_path}" ]]; then
    printf 'Required release path missing: %s\n' "${required_path}" >&2
    exit 1
  fi
done

rm -f "${ZIP_FILE}"
(
  cd "${TEMP_ROOT}"
  zip -qr "${ZIP_FILE}" "${SLUG}"
)

printf 'Built %s\n' "${ZIP_FILE}"
