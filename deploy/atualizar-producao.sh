#!/bin/bash
# Atualização da produção: backup do banco, código novo do GitHub, dependências, migrations e cache.
set -euo pipefail

APP_DIR="$HOME/agendamento.reserva-area.com.br"
BKP_DIR="$HOME/bkp/agendamento"
cd "$APP_DIR"
PHP="$(cat deploy/.php-bin 2>/dev/null || command -v php)"

env_val() { grep "^$1=" .env | head -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }

echo "==> Backup do banco"
mkdir -p "$BKP_DIR"
ARQ="$BKP_DIR/banco-$(date +%F-%H%M).sql"
MYSQL_PWD="$(env_val DB_PASSWORD)" mysqldump --no-tablespaces -u "$(env_val DB_USERNAME)" "$(env_val DB_DATABASE)" > "$ARQ"
[ -s "$ARQ" ] || { echo "ERRO: backup vazio, atualização cancelada."; exit 1; }
ls -lh "$ARQ"

echo "==> Código"
$PHP artisan down --retry=30 || true
trap '$PHP artisan up' EXIT
git pull --ff-only

echo "==> Dependências e banco"
$PHP deploy/composer.phar install --no-dev --optimize-autoloader --no-interaction --quiet
$PHP artisan migrate --force

echo "==> Cache"
$PHP artisan optimize:clear > /dev/null
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

echo
echo "Atualizado para: $(git log --oneline -1)"
