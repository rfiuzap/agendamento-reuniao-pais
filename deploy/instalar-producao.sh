#!/bin/bash
# Primeira instalação na HostGator (rodar no Terminal do cPanel, dentro da pasta do projeto).
# Cria banco e usuário MySQL, gera o .env, instala dependências, cria as tabelas e o administrador.
set -euo pipefail

DOMINIO="agendamento.reserva-area.com.br"
APP_DIR="$HOME/$DOMINIO"
CPANEL_USER="$(whoami)"
DB_NAME="${CPANEL_USER}_agendamento"
DB_USER="${CPANEL_USER}_agend"

cd "$APP_DIR"

echo "==> PHP"
PHP=""
for v in 84 83 82; do
    [ -x "/opt/cpanel/ea-php$v/root/usr/bin/php" ] && { PHP="/opt/cpanel/ea-php$v/root/usr/bin/php"; break; }
done
if [ -z "$PHP" ] && php -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);'; then PHP="$(command -v php)"; fi
[ -z "$PHP" ] && { echo "ERRO: PHP 8.2+ não encontrado. No cPanel, abra 'Select PHP Version' e ative o PHP 8.2."; exit 1; }
$PHP -v | head -1
echo "$PHP" > deploy/.php-bin

echo "==> Composer"
if [ ! -f deploy/composer.phar ]; then
    curl -sS https://getcomposer.org/installer | $PHP -- --install-dir=deploy --quiet
fi
$PHP deploy/composer.phar install --no-dev --optimize-autoloader --no-interaction --quiet

if [ ! -f .env ]; then
    echo "==> Banco de dados $DB_NAME"
    DB_PASS="$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 24)"
    cpanel() {
        local out; out="$(uapi "$@")"
        grep -q 'status: 1' <<< "$out" || { echo "ERRO em 'uapi $1 $2':"; grep -A3 'errors:' <<< "$out"; exit 1; }
    }
    cpanel Mysql create_database name="$DB_NAME"
    cpanel Mysql create_user name="$DB_USER" password="$DB_PASS"
    cpanel Mysql set_privileges_on_database user="$DB_USER" database="$DB_NAME" privileges=ALL

    echo "==> Arquivo .env"
    cp .env.example .env
    sed -i \
        -e "s#^APP_ENV=.*#APP_ENV=production#" \
        -e "s#^APP_DEBUG=.*#APP_DEBUG=false#" \
        -e "s#^APP_URL=.*#APP_URL=https://$DOMINIO#" \
        -e "s#^LOG_LEVEL=.*#LOG_LEVEL=error#" \
        -e "s#^DB_HOST=.*#DB_HOST=localhost#" \
        -e "s#^DB_DATABASE=.*#DB_DATABASE=$DB_NAME#" \
        -e "s#^DB_USERNAME=.*#DB_USERNAME=$DB_USER#" \
        -e "s#^DB_PASSWORD=.*#DB_PASSWORD=$DB_PASS#" \
        .env
    grep -q '^SESSION_SECURE_COOKIE=' .env || echo 'SESSION_SECURE_COOKIE=true' >> .env
    $PHP artisan key:generate --force
else
    echo "==> .env já existe: mantido"
fi

echo "==> Tabelas"
$PHP artisan migrate --force

echo "==> Permissões e cache"
chmod -R 775 storage bootstrap/cache
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

echo "==> Administrador"
$PHP artisan app:criar-admin

echo
echo "Instalação concluída: https://$DOMINIO"
