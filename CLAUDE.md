# Finanzas Familiares

## Descripcion
App web de finanzas familiares para gestionar gastos, ingresos, patrimonio y presupuestos entre miembros de una familia. Cada usuario se registra y se une a una "familia" para compartir la gestion financiera.

## Stack Tecnologico
- **Backend**: Laravel 12.x (PHP 8.2+)
- **Frontend**: Vue 3 + Inertia.js + Tailwind CSS 3
- **Charts**: Chart.js + vue-chartjs
- **Base de datos**: MySQL 8
- **Build**: Vite 5
- **Server local**: Apache 2 (finanzas.test)

## Estructura del Proyecto
```
app/
  Models/         - Eloquent models (User, Family, Account, CreditCard, etc.)
  Http/Controllers/ - Controllers para cada seccion
resources/
  js/
    Pages/        - Componentes Vue por seccion (Dashboard, Accounts, CreditCards, etc.)
    Layouts/      - AuthenticatedLayout (sidebar), GuestLayout (login/register)
    Components/UI/ - Componentes reutilizables (Modal, StatCard, MonthSelector)
    helpers.js    - Funciones de formato (formatMoney, formatDate)
database/
  migrations/     - Todas las migraciones de la DB
```

## Secciones
1. **Dashboard** - Analytics con charts, filtros por persona/mes, resumen financiero
2. **Cuentas** - Bancos, billeteras virtuales (Mercado Pago, Deel, etc.)
3. **Tarjetas de Credito** - Diseño visual de tarjetas, cuotas, limites
4. **Gastos Fijos** - Mensuales, con toggle de pago por mes
5. **Gastos Variables** - Con presupuestos, necesario/innecesario, por cuenta
6. **Ingresos** - Por trabajo/fuente, graficos de evolucion
7. **Patrimonio** - Activos con valor en ARS y USD con conversion automatica
8. **Supermercado** - Catalogo de productos argentinos, compras con items
9. **Configuracion** - Cotizacion USD, SMTP, backups de DB

## Base de datos
- DB: `finanzasfamiliares`
- User: `improntus`
- Password: `123456`
- No borrar otras bases de datos del servidor

## Comandos utiles
```bash
php artisan migrate          # Correr migraciones
npm run build               # Build de produccion
npm run dev                 # Dev server con HMR
php artisan route:list      # Ver rutas
php artisan config:clear    # Limpiar cache de config
```

## Dominio local
- `finanzas.test` via Apache VirtualHost
- Config en `/etc/apache2/sites-available/finanzas.test.conf`

## Conversion USD
- La cotizacion se configura en Settings > Cotizacion USD
- Se usa en toda la app para mostrar valores en ambas monedas
- Modelo Setting::getUsdRate() para obtener el valor actual

## Familias
- Al registrarse se crea una familia automaticamente
- Para que la pareja se una, usa el "codigo de familia" (ID de la familia) al registrarse
- Todos los datos se filtran por familia
