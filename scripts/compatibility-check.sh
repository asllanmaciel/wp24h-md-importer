#!/usr/bin/env sh
set -eu

version=${1:-}
case "$version" in
  7.1|7.0.4) ;;
  *)
    echo "Unsupported WordPress version '$version'. Supported versions: 7.1, 7.0.4." >&2
    exit 2
    ;;
esac

if ! docker info --format '{{.ServerVersion}}' >/dev/null 2>&1; then
  echo "Docker daemon is unavailable. Start Docker and retry." >&2
  exit 1
fi

project="wp24h-compat-$(printf '%s' "$version" | tr -d '.')"
report_path="reports/compatibility/$version.json"
export WP_COMPAT_VERSION="$version"
runner_status=1

compose() {
  docker compose -p "$project" -f docker/compatibility.compose.yml "$@"
}

blocked() {
  printf '{\n  "wordpress_version": "%s",\n  "status": "BLOCKED",\n  "reason": "%s"\n}\n' "$version" "$1" > "$report_path"
}

cleanup() {
  compose down --volumes --remove-orphans >/dev/null 2>&1 || true
}
trap cleanup EXIT

rm -f "$report_path"
if ! compose pull; then
  blocked "Docker image unavailable or could not be pulled."
  exit 1
fi

compose up -d db fixtures wordpress

deadline=$(( $(date +%s) + 180 ))
ready=false
while [ "$(date +%s)" -lt "$deadline" ]; do
  db_health=$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{end}}' "$project-db-1" 2>/dev/null || true)
  if compose exec -T fixtures wget -q -O /dev/null http://127.0.0.1/featured.png >/dev/null 2>&1 \
    && compose exec -T wordpress test -f /var/www/html/wp-includes/version.php >/dev/null 2>&1 \
    && [ "$db_health" = "healthy" ]; then
    ready=true
    break
  fi
  sleep 1
done

if [ "$ready" != true ]; then
  echo "Timed out waiting for the database and fixture server." >&2
  exit 1
fi

compose run --rm cli core install --url=http://wordpress --title='WP24H Compatibility' --admin_user=admin --admin_password=admin --admin_email=admin@example.test --skip-email
compose run --rm cli plugin activate wp24h-md-importer
if compose run --rm cli eval 'wp_set_current_user(1); require "/var/www/html/wp-content/plugins/wp24h-md-importer/tests/compatibility/run.php";'; then
  runner_status=0
fi

exit "$runner_status"
