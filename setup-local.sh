#!/bin/bash
# Script para configurar el sitio en Apache local
# Ejecutar con: sudo bash setup-local.sh

set -e

APP_DIR="/home/karen/projects/untitled2"
DOMAIN="finanzas.test"

echo "Configurando $DOMAIN en Apache..."

# Crear VirtualHost
cat > /etc/apache2/sites-available/${DOMAIN}.conf <<APACHE
<VirtualHost *:80>
    ServerName $DOMAIN
    DocumentRoot $APP_DIR/public

    <Directory $APP_DIR/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN}-error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN}-access.log combined
</VirtualHost>
APACHE

# Habilitar sitio y modulos
a2ensite ${DOMAIN}.conf 2>/dev/null || true
a2enmod rewrite 2>/dev/null || true

# Agregar al hosts
grep -q "$DOMAIN" /etc/hosts || echo "127.0.0.1 $DOMAIN" >> /etc/hosts

# Reiniciar Apache
systemctl restart apache2

echo "Listo! Accede a http://$DOMAIN"
