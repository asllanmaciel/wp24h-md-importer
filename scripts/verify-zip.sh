#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SLUG="wp24h-md-importer"
ZIP_FILE="${1:-${ROOT}/build/${SLUG}.zip}"

if ! command -v unzip >/dev/null 2>&1; then
  printf 'Required command not found: unzip\n' >&2
  exit 1
fi

if [[ ! -f "${ZIP_FILE}" ]]; then
  printf 'Release ZIP not found: %s\n' "${ZIP_FILE}" >&2
  exit 1
fi

entries=()
while IFS= read -r entry; do
  entries[${#entries[@]}]="${entry}"
done < <(unzip -Z1 "${ZIP_FILE}")

if [[ ${#entries[@]} -eq 0 ]]; then
  printf 'Release ZIP is empty: %s\n' "${ZIP_FILE}" >&2
  exit 1
fi

prefix="${SLUG}/"
for entry in "${entries[@]}"; do
  if [[ "${entry}" != "${prefix}"* ]]; then
    printf 'Unexpected top-level entry: %s\n' "${entry}" >&2
    exit 1
  fi
done

required=(
  "${SLUG}/${SLUG}.php"
  "${SLUG}/readme.txt"
  "${SLUG}/LICENSE"
)

for path in "${required[@]}"; do
  if ! printf '%s\n' "${entries[@]}" | grep -Fxq "${path}"; then
    printf 'Required release file missing: %s\n' "${path}" >&2
    exit 1
  fi
done

forbidden_prefixes=(
  "${SLUG}/.git/"
  "${SLUG}/.github/"
  "${SLUG}/scripts/"
  "${SLUG}/build/"
)

for path in "${forbidden_prefixes[@]}"; do
  if printf '%s\n' "${entries[@]}" | grep -Fq "${path}"; then
    printf 'Forbidden release path found: %s\n' "${path}" >&2
    exit 1
  fi
done

forbidden_files=(
  "${SLUG}/.distignore"
  "${SLUG}/README.md"
  "${SLUG}/CHANGELOG.md"
  "${SLUG}/CONTRIBUTING.md"
  "${SLUG}/SECURITY.md"
  "${SLUG}/example-post.md"
)

for path in "${forbidden_files[@]}"; do
  if printf '%s\n' "${entries[@]}" | grep -Fxq "${path}"; then
    printf 'Forbidden release file found: %s\n' "${path}" >&2
    exit 1
  fi
done

for entry in "${entries[@]}"; do
  case "${entry}" in
    *.zip)
      printf 'Nested ZIP must not be included: %s\n' "${entry}" >&2
      exit 1
      ;;
  esac
done

printf 'Release ZIP verified: %s (%d entries)\n' "${ZIP_FILE}" "${#entries[@]}"
