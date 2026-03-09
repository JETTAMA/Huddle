#!/usr/bin/env bash
set -euo pipefail

CONTAINER_NAME="${CONTAINER_NAME:-e-huddle-php-live}"
PORT="${PORT:-18080}"
IMAGE="${IMAGE:-php:8.3-cli}"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if docker ps -a --format '{{.Names}}' | grep -Fxq "$CONTAINER_NAME"; then
  docker rm -f "$CONTAINER_NAME" >/dev/null
fi

docker run -d --rm \
  --name "$CONTAINER_NAME" \
  -p "$PORT:8000" \
  -v "$APP_DIR:/app" \
  -w /app \
  "$IMAGE" \
  php -S 0.0.0.0:8000 router.php >/dev/null

echo "Container: $CONTAINER_NAME"
echo "URL: http://localhost:$PORT/"
echo "Live reload behavior: refresh browser after editing PHP/CSS/JS files."
