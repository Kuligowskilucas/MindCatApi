#!/usr/bin/env bash
set -euo pipefail

cd /var/www/MindCatApi

echo "==> Modo manutenção"
php artisan down --retry=15 || true
trap 'php artisan up || true' EXIT

echo "==> Limpando ruído do working tree (arquivos gerenciados pelo Laravel)"
git checkout -- bootstrap/cache 2>/dev/null || true

echo "==> Atualizando código"
git pull --ff-only

echo "==> Garantindo diretórios de runtime"
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/backups \
         bootstrap/cache

echo "==> Dependências de produção"
composer install --no-dev --optimize-autoloader --no-interaction --no-progress

echo "==> Backup do banco antes de migrar"
get_env() { grep -E "^$1=" .env | head -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }
DB_DATABASE="$(get_env DB_DATABASE)"
DB_USERNAME="$(get_env DB_USERNAME)"
DB_PASSWORD="$(get_env DB_PASSWORD)"
BACKUP="storage/backups/pre-deploy-$(date +%Y%m%d-%H%M%S).sql.gz"
CNF="$(mktemp)"
printf '[client]\nuser=%s\npassword=%s\n' "$DB_USERNAME" "$DB_PASSWORD" > "$CNF"
mysqldump --defaults-extra-file="$CNF" --single-transaction --no-tablespaces --quick "$DB_DATABASE" | gzip > "$BACKUP"
rm -f "$CNF"
echo "    salvo em $BACKUP"

echo "==> Migrations (aplica só as pendentes)"
php artisan migrate --force

echo "==> Recacheando config e rotas"
php artisan config:cache
php artisan route:cache

echo "==> Recarregando PHP-FPM"
sudo systemctl reload php8.3-fpm

echo "==> Deploy concluído com sucesso"