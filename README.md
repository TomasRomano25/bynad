# Bynad Finance

App web de finanzas familiares para gestionar gastos, ingresos, patrimonio y presupuestos compartidos entre miembros de una familia.

## Stack

- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Vue 3 + Inertia.js + Tailwind CSS 3
- **Charts:** Chart.js + vue-chartjs
- **Base de datos:** MySQL 8
- **Build:** Vite 5
- **Web server:** Nginx

## Funcionalidades

- Dashboard con analytics y filtros por miembro/mes
- Cuentas bancarias y billeteras virtuales
- Tarjetas de crédito con diseño visual y cuotas
- Gastos fijos mensuales con toggle de pago
- Gastos variables con presupuestos por categoría
- Ingresos por fuente con gráficos de evolución
- Patrimonio (activos en ARS y USD con conversión automática)
- Lista del supermercado con catálogo de productos
- Landing page pública + blog
- Panel de configuración (solo admin)

---

## Instalación local

### Requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8
- Apache o Nginx

### Pasos

```bash
# 1. Clonar el repo
git clone git@github.com:TomasRomano25/bynad.git
cd bynad

# 2. Instalar dependencias PHP
composer install

# 3. Instalar dependencias JS
npm install

# 4. Configurar entorno
cp .env.example .env
php artisan key:generate
```

Editar `.env` con los datos de la base de datos:

```env
DB_DATABASE=finanzasfamiliares
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

```bash
# 5. Crear base de datos y migrar
php artisan migrate

# 6. Build del frontend
npm run build

# 7. Link de storage
php artisan storage:link
```

### Servidor local (Apache)

El proyecto corre en `finanzas.test` con un VirtualHost de Apache. La config está en `finanzas.test.conf`.

```bash
# Activar el sitio
sudo cp finanzas.test.conf /etc/apache2/sites-available/
sudo a2ensite finanzas.test.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

Agregar al `/etc/hosts`:
```
127.0.0.1 finanzas.test
```

---

## Producción

### Servidor

- **Dominio:** https://bynad.com
- **IP:** 72.60.155.87
- **Directorio:** `/var/www/bynad`
- **Web server:** Nginx
- **PHP:** 8.3 (FPM)
- **DB:** MySQL 8 — base `finanzasfamiliares`, usuario `improntus`

### Deploy inicial (primera vez)

En el servidor:

```bash
# Clonar repo
git clone git@github.com:TomasRomano25/bynad.git /var/www/bynad
cd /var/www/bynad

# Dependencias
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Configurar entorno
cp .env.example .env
# Editar .env con los valores de producción
php artisan key:generate
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan storage:link

# Permisos
chown -R www-data:www-data /var/www/bynad
chmod -R 775 /var/www/bynad/storage /var/www/bynad/bootstrap/cache
```

Config de Nginx en `/etc/nginx/sites-available/bynad`:

```nginx
server {
    listen 80;
    server_name bynad.com www.bynad.com;

    root /var/www/bynad/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 20M;
}
```

```bash
ln -s /etc/nginx/sites-available/bynad /etc/nginx/sites-enabled/bynad
nginx -t && systemctl reload nginx
```

### SSL con Certbot

```bash
apt install certbot python3-certbot-nginx -y
certbot --nginx -d bynad.com -d www.bynad.com
```

Certbot renueva el certificado automáticamente.

### Deploy de actualizaciones

Cada vez que hagas cambios:

```bash
# Local — pushear cambios
git add .
git commit -m "descripción del cambio"
git push
```

En el servidor:

```bash
cd /var/www/bynad
git pull
composer install --no-dev --optimize-autoloader --no-interaction
npm install && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## Usuario admin

El admin de la plataforma es un usuario con `is_admin = true`. No es un rol familiar — es el dueño del sistema y el único que puede acceder a Configuración.

```bash
# Registrarte primero en /register, luego:
php artisan user:make-admin tu@email.com
```

Desde Configuración el admin gestiona:
- Cotización USD (usada para conversión de activos)
- Configuración SMTP para emails
- Backups de la base de datos

---

## Familias

- Al registrarse se crea una familia automáticamente
- Para que otro miembro se una, usa el **código de familia** (ID numérico) durante el registro
- Todos los datos se filtran por familia

---

## Variables de entorno relevantes

| Variable | Descripción |
|---|---|
| `APP_URL` | URL pública de la app |
| `APP_DEBUG` | `false` en producción |
| `DB_DATABASE` | Nombre de la base de datos |
| `DB_USERNAME` / `DB_PASSWORD` | Credenciales MySQL |
| `MAIL_*` | Config SMTP (configurable desde el panel) |

---

## Comandos útiles

```bash
php artisan migrate                            # Correr migraciones
php artisan migrate:rollback                   # Deshacer última migración
php artisan route:list                         # Ver todas las rutas
php artisan config:clear                       # Limpiar cache de config
php artisan user:make-admin email@ejemplo.com  # Hacer admin a un usuario
npm run dev                                    # Dev server con HMR
npm run build                                  # Build de producción
```
