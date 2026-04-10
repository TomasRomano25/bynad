#!/bin/bash

# ─── CONFIGURACION ────────────────────────────────────────────────────────────
APP_DIR="/var/www/bynad"
REPO="git@github.com:TomasRomano25/bynad.git"
DOMAIN="bynad.finance"        # o tu IP si no tenes dominio todavia
DB_NAME="finanzasfamiliares"
DB_USER="improntus"
DB_PASS="123456"              # cambia en produccion
# ──────────────────────────────────────────────────────────────────────────────

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log()    { echo -e "${BLUE}[DEPLOY]${NC} $1"; }
success(){ echo -e "${GREEN}[OK]${NC} $1"; }
warn()   { echo -e "${YELLOW}[WARN]${NC} $1"; }
error()  { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

echo ""
echo -e "${BLUE}╔══════════════════════════════════════╗${NC}"
echo -e "${BLUE}║        Bynad Finance - Deploy        ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════╝${NC}"
echo ""

# ─── 1. CODIGO ───────────────────────────────────────────────────────────────
if [ ! -d "$APP_DIR/.git" ]; then
    log "Clonando repositorio..."
    git clone "$REPO" "$APP_DIR"
    success "Repositorio clonado"
else
    log "Actualizando codigo..."
    cd "$APP_DIR"
    git pull origin main
    success "Codigo actualizado"
fi

cd "$APP_DIR"

# ─── 2. PERMISOS ─────────────────────────────────────────────────────────────
log "Configurando permisos..."
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
success "Permisos OK"

# ─── 3. COMPOSER ─────────────────────────────────────────────────────────────
log "Instalando dependencias PHP..."
composer install --no-dev --optimize-autoloader --no-interaction
success "Composer OK"

# ─── 4. ARCHIVO .ENV ─────────────────────────────────────────────────────────
if [ ! -f "$APP_DIR/.env" ]; then
    log "Creando .env..."
    cat > "$APP_DIR/.env" << ENVEOF
APP_NAME="Bynad Finance"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://${DOMAIN}

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

VITE_APP_NAME="Bynad Finance"
ENVEOF
    success ".env creado"
    log "Generando APP_KEY..."
    php artisan key:generate --force
    success "APP_KEY generada"
else
    warn ".env ya existe, no se sobreescribe"
fi

# ─── 5. BASE DE DATOS ────────────────────────────────────────────────────────
log "Verificando base de datos..."
if ! mysql -u"$DB_USER" -p"$DB_PASS" -e "USE \`$DB_NAME\`;" 2>/dev/null; then
    log "Creando base de datos $DB_NAME..."
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL ON \`$DB_NAME\`.* TO '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}'; FLUSH PRIVILEGES;" 2>/dev/null || \
    mysql -u"$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || \
    error "No se pudo crear la DB. Crea el usuario MySQL manualmente primero."
    success "Base de datos creada"
fi

log "Ejecutando migraciones..."
php artisan migrate --force
success "Migraciones OK"

# ─── 6. FRONTEND ─────────────────────────────────────────────────────────────
log "Instalando dependencias JS..."
npm install --silent
log "Compilando assets..."
npm run build
success "Build OK"

# ─── 7. CACHE LARAVEL ────────────────────────────────────────────────────────
log "Optimizando Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link 2>/dev/null || true
success "Cache OK"

# ─── 8. APACHE ───────────────────────────────────────────────────────────────
VHOST_FILE="/etc/apache2/sites-available/bynad.conf"
if [ ! -f "$VHOST_FILE" ]; then
    log "Configurando VirtualHost Apache..."
    cat > "$VHOST_FILE" << APACHEEOF
<VirtualHost *:80>
    ServerName ${DOMAIN}
    DocumentRoot ${APP_DIR}/public

    <Directory ${APP_DIR}/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/bynad-error.log
    CustomLog \${APACHE_LOG_DIR}/bynad-access.log combined
</VirtualHost>
APACHEEOF
    a2enmod rewrite 2>/dev/null
    a2ensite bynad.conf
    systemctl reload apache2
    success "Apache configurado para $DOMAIN"
else
    warn "VirtualHost ya existe ($VHOST_FILE)"
    systemctl reload apache2
fi

# ─── 9. ADMIN ────────────────────────────────────────────────────────────────
echo ""
read -p "¿Asignar usuario admin? (s/n): " MAKE_ADMIN
if [[ "$MAKE_ADMIN" == "s" || "$MAKE_ADMIN" == "S" ]]; then
    read -p "Email: " ADMIN_EMAIL
    php artisan user:make-admin "$ADMIN_EMAIL" || \
        warn "No encontrado. Registrate primero y corré: php artisan user:make-admin tu@email.com"
fi

echo ""
echo -e "${GREEN}╔══════════════════════════════════════╗${NC}"
echo -e "${GREEN}║     Deploy completado con exito!     ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════╝${NC}"
echo ""
echo -e "  URL: ${BLUE}http://${DOMAIN}${NC}"
echo -e "  Dir: ${BLUE}${APP_DIR}${NC}"
echo ""
echo -e "${YELLOW}Proximos pasos:${NC}"
echo "  1. Registrate en http://${DOMAIN}/register"
echo "  2. php artisan user:make-admin tu@email.com"
echo ""
