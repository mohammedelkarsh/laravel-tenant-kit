# Laravel Tenant Kit — Docker first-time setup (Windows PowerShell)
$ErrorActionPreference = "Stop"

Write-Host "Starting Docker stack..." -ForegroundColor Cyan
docker compose up -d --build

Write-Host "Preparing .env..." -ForegroundColor Cyan
docker compose exec app cp .env.docker .env
docker compose exec app php artisan key:generate --force

Write-Host "Running migrations & seed..." -ForegroundColor Cyan
docker compose exec app php artisan migrate --seed --force

Write-Host "Building frontend assets..." -ForegroundColor Cyan
docker compose --profile build run --rm node

Write-Host ""
Write-Host "Done! Open http://laravel-tenant-kit.test:8080" -ForegroundColor Green
Write-Host "Ensure hosts file contains:" -ForegroundColor Yellow
Write-Host "  127.0.0.1 laravel-tenant-kit.test"
Write-Host "  127.0.0.1 demo.laravel-tenant-kit.test"
