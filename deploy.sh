#!/usr/bin/env bash
set -euo pipefail

cd /var/www/MindCatApi

echo "==> Modo manutenção"
php artisan down --retry=15 || true
trap 'php artisan up || true' EXIT

echo "==> Limpando ruído do working tree (arquivos gerenciados pelo Laravel)"
git checkout -- storage bootstrap/cache 2>/dev/null || true

echo "==> Atualizando código"
git pull --ff-only

echo "==> Dependências de produção (só reinstala se o composer.lock mudou)"
composer install --no-dev --optimize-autoloader --no-interaction --no-progress

echo "==> Backup do banco antes de migrar"
get_env() { grep -E "^$1=" .env | head -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }
DB_DATABASE="$(get_env DB_DATABASE)"
DB_USERNAME="$(get_env DB_USERNAME)"
DB_PASSWORD="$(get_env DB_PASSWORD)"
mkdir -p storage/backups
BACKUP="storage/backups/pre-deploy-$(date +%Y%m%d-%H%M%S).sql.gz"
CNF="$(mktemp)"
printf '[client]\nuser=%s\npassword=%s\n' "$DB_USERNAME" "$DB_PASSWORD" > "$CNF"
mysqldump --defaults-extra-file="$CNF" "$DB_DATABASE" | gzip > "$BACKUP"
rm -f "$CNF"
echo "    salvo em $BACKUP"

echo "==> Migrations (aplica só as pendentes)"
php artisan migrate --force

echo "==> Recacheando config e rotas"
php artisan config:cache
php artisan route:cache

echo "==> Reiniciando workers de fila (relevante quando o e-mail entrar)"
php artisan queue:restart || true

echo "==> Recarregando PHP-FPM"
sudo systemctl reload php8.3-fpm

echo "==> Deploy concluído com sucesso"