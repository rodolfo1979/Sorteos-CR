# Despliegue Laravel en subdominio de pruebas

Objetivo: usar `https://sorteoscr.morpho3d.com` como ambiente de pruebas de Laravel y dejar el dominio oficial para el lanzamiento final.

## Ruta recomendada

La carpeta publica del subdominio debe apuntar a:

```text
laravel-app/public
```

En hosting compartido esto se logra de dos formas:

1. Configurar el Document Root del subdominio hacia `laravel-app/public` si el panel lo permite.
2. Si el panel no permite apuntar fuera de `public_html`, subir el proyecto Laravel fuera de `public_html` y copiar el contenido de `laravel-app/public` en el folder publico, ajustando `index.php` para que cargue `../laravel-app/vendor/autoload.php` y `../laravel-app/bootstrap/app.php`.

La opcion 1 es la mas limpia.

## Pasos en el servidor

1. Crear base MySQL para pruebas.
2. Copiar `.env.production.example` como `.env`.
3. Completar credenciales MySQL, SMTP y admin.
4. Ejecutar:

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

5. Verificar:

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
