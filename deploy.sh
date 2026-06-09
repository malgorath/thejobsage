#!/bin/bash
set -e
 
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_DIR"
 
echo "==> Running tests..."
docker exec jobsage-app php artisan test --stop-on-failure
 
echo "==> Staging all changes..."
git add -A
 
if git diff --cached --quiet; then
  echo "==> Nothing to commit, skipping."
else
  echo "==> Enter commit message:"
  read -r MSG
  git commit -m "$MSG"
fi
 
echo "==> Restarting containers..."
docker compose down && docker compose up -d
 
echo "==> Done. JobSage is up."
 
