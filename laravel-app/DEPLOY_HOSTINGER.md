# Despliegue Laravel en subdominio de pruebas

Objetivo: usar `https://sorteoscr.morpho3d.com` como ambiente de pruebas de Laravel y dejar el dominio oficial para el lanzamiento final.

## Configuracion actual del repositorio

El repositorio ya esta preparado para que Hostinger pueda desplegarlo en:

```text
public_html/sorteoscr
```

La raiz del repositorio contiene:

- `index.php`: carga Laravel desde `laravel-app`.
- `.htaccess`: envia las rutas a Laravel, sirve assets de `laravel-app/public/build`, sirve `storage`, y bloquea carpetas internas.
- `laravel-app/`: aplicacion Laravel real.
- `legacy-static/`: respaldo del prototipo estatico, bloqueado por `.htaccess` para no confundirlo con el sistema nuevo.

## Pasos en Hostinger

1. Confirmar que el Git deployment del subdominio siga apuntando a:

```text
public_html/sorteoscr
```

2. Crear base MySQL para pruebas.
3. Copiar `laravel-app/.env.production.example` como `laravel-app/.env` en el servidor.
4. Completar credenciales MySQL, SMTP y admin.
5. Desde terminal/SSH, dentro de `public_html/sorteoscr/laravel-app`, ejecutar:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

6. Verificar:

```bash
php artisan route:list --except-vendor
```

## Acceso admin temporal

Los paneles `/admin`, `/admin/pagos`, `/admin/reportes` y `/admin/numeros` estan protegidos por autenticacion basica temporal:

```text
ADMIN_USERNAME=admin
ADMIN_PASSWORD=CAMBIAR_POR_UN_PASSWORD_LARGO_Y_SEGURO
```

Antes de produccion final se debe reemplazar por login real con usuarios, roles, recuperacion de clave, rate limit y 2FA opcional.

## Importante

No subir `.env`, `vendor/`, `node_modules/` ni archivos de comprobantes al repositorio. El servidor debe generar esos archivos localmente.

## Assets frontend

Los archivos compilados de Vite se suben en laravel-app/public/build para evitar depender de Node en Hostinger. No ejecutar 
pm run build en el servidor si la version de Node es 18.x; solo usar los archivos desplegados por Git.

