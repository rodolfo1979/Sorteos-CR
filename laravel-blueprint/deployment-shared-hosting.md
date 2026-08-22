# Despliegue en Servidor Compartido

## Requisitos recomendados

- PHP 8.2 o superior.
- MySQL 8 o MariaDB 10.6+.
- Composer disponible.
- Cron jobs habilitados.
- SSL activo.
- Acceso a `public_html`.

## Estructura recomendada

En hosting compartido, lo ideal es dejar el proyecto Laravel fuera de `public_html` y apuntar el dominio a la carpeta `public`.

Si el hosting no permite cambiar document root:

- Copiar contenido de `public/` a `public_html`.
- Ajustar `index.php` para apuntar a `../laravel-app/vendor/autoload.php` y `../laravel-app/bootstrap/app.php`.
- Mantener `.env`, `storage`, `app`, `database` y `vendor` fuera de acceso publico.

## Cron obligatorio

Agregar cron cada minuto:

```bash
* * * * * php /ruta/al/proyecto/artisan schedule:run >> /dev/null 2>&1
```

Tareas programadas:

- Liberar reservas vencidas.
- Enviar correos pendientes.
- Limpiar archivos temporales.
- Crear backups si el hosting lo permite.

En Laravel, registrar:

```php
Schedule::command('raffles:expire-reservations')->everyMinute()->withoutOverlapping();
```

## Seguridad minima

- `APP_DEBUG=false`.
- `APP_ENV=production`.
- `APP_KEY` generado.
- HTTPS forzado.
- Backups automaticos.
- Validacion de archivos subidos.
- Admin protegido con login y roles.

## Rendimiento

Comandos despues de cambios:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

Evitar cargar todos los numeros en una sola respuesta. Usar paginacion, rangos o asignacion aleatoria en backend.
