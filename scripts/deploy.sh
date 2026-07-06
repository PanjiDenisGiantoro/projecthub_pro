#!/bin/bash
# ============================================================
# Flovig — Deploy Script
# Dijalankan via HTTP webhook, bukan SSH.
# Aman untuk dev & production (baca APP_ENV dari .env).
# ============================================================
set -e

APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"
LOG_FILE="$APP_DIR/storage/logs/deploy.log"
BRANCH=$(cd "$APP_DIR" && git rev-parse --abbrev-ref HEAD)
ENV_FILE="$APP_DIR/.env"

# ── Helper ────────────────────────────────────────────────────
log() {
    local msg="[$(date '+%Y-%m-%d %H:%M:%S')] $1"
    echo "$msg" | tee -a "$LOG_FILE"
}

fail() {
    log "❌ ERROR: $1"
    echo "DEPLOY_FAILED" >> "$LOG_FILE"
    exit 1
}

# ── Header ────────────────────────────────────────────────────
echo "" >> "$LOG_FILE"
log "════════════════════════════════════"
log ">>> DEPLOY DIMULAI"
log ">>> Branch : $BRANCH"
log ">>> Dir    : $APP_DIR"
log "════════════════════════════════════"

cd "$APP_DIR" || fail "Gagal masuk ke $APP_DIR"

# ── 1. Pull kode terbaru ──────────────────────────────────────
log ">>> [1/8] Git pull origin $BRANCH..."
git fetch origin                          >> "$LOG_FILE" 2>&1
git reset --hard "origin/$BRANCH"         >> "$LOG_FILE" 2>&1
log "    Commit: $(git log -1 --pretty='%h %s')"

# ── 2. Composer ───────────────────────────────────────────────
log ">>> [2/8] Composer install..."
COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --optimize-autoloader \
    --no-interaction \
    --ignore-platform-req=php \
    >> "$LOG_FILE" 2>&1

# ── 3. NPM & build ───────────────────────────────────────────
log ">>> [3/8] npm ci..."
npm ci --no-audit --no-fund              >> "$LOG_FILE" 2>&1

log ">>> [4/8] npm run build..."
npm run build                            >> "$LOG_FILE" 2>&1

# ── 4. Migrasi ───────────────────────────────────────────────
log ">>> [5/9] php artisan migrate --force..."
php artisan migrate --force              >> "$LOG_FILE" 2>&1

# ── 5. Seeder ────────────────────────────────────────────────
log ">>> [6/9] db:seed PackageSeeder..."
php artisan db:seed --class=PackageSeeder --force >> "$LOG_FILE" 2>&1

# ── 6. Cache ─────────────────────────────────────────────────
log ">>> [7/9] Rebuild artisan cache..."
php artisan config:cache                 >> "$LOG_FILE" 2>&1
php artisan route:cache                  >> "$LOG_FILE" 2>&1
php artisan view:cache                   >> "$LOG_FILE" 2>&1
php artisan event:cache                  >> "$LOG_FILE" 2>&1

# ── 6. Queue ─────────────────────────────────────────────────
log ">>> [8/9] Restart queue worker..."
php artisan queue:restart                >> "$LOG_FILE" 2>&1

# ── 8. PHP-FPM reload ────────────────────────────────────────
log ">>> [9/9] Reload PHP-FPM..."
(systemctl reload php8.4-fpm  >> "$LOG_FILE" 2>&1) || \
(systemctl reload php8.3-fpm  >> "$LOG_FILE" 2>&1) || \
(service php8.4-fpm reload    >> "$LOG_FILE" 2>&1) || \
(service php8.3-fpm reload    >> "$LOG_FILE" 2>&1) || \
log "    PHP-FPM reload skipped (tidak ditemukan service yang sesuai)"

# ── Selesai ──────────────────────────────────────────────────
log "════════════════════════════════════"
log ">>> DEPLOY SELESAI ✓"
log ">>> Durasi selesai: $(date '+%Y-%m-%d %H:%M:%S')"
log "════════════════════════════════════"
echo "DEPLOY_SUCCESS" >> "$LOG_FILE"
