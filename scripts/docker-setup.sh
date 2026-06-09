#!/usr/bin/env bash
set -euo pipefail

echo "Starting Docker stack..."
docker compose up -d --build

echo "Preparing .env..."
docker compose exec app cp .env.docker .env
docker compose exec app php artisan key:generate --force

echo "Running migrations & seed..."
docker compose exec app php artisan migrate --seed --force

echo "Building frontend assets..."
docker compose --profile build run --rm node

echo ""
echo "Done! Open http://laravel-tenant-kit.test:8080"
echo "Ensure hosts file contains:"
echo "  127.0.0.1 laravel-tenant-kit.test"
echo "  127.0.0.1 demo.laravel-tenant-kit.test"
