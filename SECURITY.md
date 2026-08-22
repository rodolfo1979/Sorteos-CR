# Seguridad del Sistema de Rifas

Este prototipo funciona en navegador y usa `localStorage`, por eso no ejecuta SQL ni tiene servidor real todavia. Para produccion, el sistema debe implementarse con backend, base de datos, autenticacion y almacenamiento seguro.

## Reglas obligatorias para produccion

- Usar consultas parametrizadas u ORM seguro para evitar SQL injection.
- Validar todos los datos en servidor, aunque tambien se validen en pantalla.
- Escapar cualquier dato mostrado en HTML para prevenir XSS.
- Autenticacion obligatoria para panel admin, pagos y reportes.
- Roles separados: administrador, validador de pagos y vendedor.
- Sesiones con cookies `HttpOnly`, `Secure` y `SameSite`.
- CSRF tokens en formularios administrativos.
- Rate limiting en login, checkout y busqueda de numeros.
- Bloqueo transaccional de numeros para evitar doble venta.
- Auditoria de cambios: quien aprobo, rechazo, edito rifas o libero numeros.
- Comando programado cada minuto para liberar reservas vencidas.
- Subida de comprobantes e imagenes con limite de tamano, tipo MIME permitido y renombrado del archivo.
- Guardar archivos fuera del directorio publico o en bucket privado con URLs firmadas.
- Escanear archivos subidos antes de servirlos al usuario.
- Backups automaticos de base de datos y comprobantes.
- HTTPS obligatorio.
- Variables secretas fuera del codigo fuente.
- Politica de contenido CSP para reducir riesgo de scripts inyectados.

## Base de datos recomendada

- Tabla `raffles`: configuracion de cada sorteo.
- Tabla `raffle_numbers`: numero, estado, rifa y orden asociada.
- Tabla `orders`: comprador, estado, monto, comprobante y timestamps.
- Tabla `audit_logs`: acciones administrativas.
- Tabla `users`: administradores y roles.

Los cambios de estado de numeros deben hacerse dentro de transacciones. Si un pago se rechaza, los numeros reservados vuelven a `available`. Si se aprueba, pasan a `sold`.

Ver `laravel-blueprint/` para el esquema, migraciones ejemplo y servicios transaccionales recomendados.

El comando `raffles:expire-reservations` debe correr con cron cada minuto para que los numeros apartados no queden bloqueados indefinidamente.

## Regla anti doble venta

Nunca marcar numeros desde JavaScript o desde una consulta sin bloqueo. En Laravel, la reserva debe usar:

- `DB::transaction()`
- `lockForUpdate()`
- condicion `status = available`
- indice unico `raffle_id + number`

Si dos compradores intentan tomar el mismo numero al mismo tiempo, solo una transaccion debe ganar. La otra debe recibir respuesta de numero no disponible y volver a escoger/asignar.

## Estado actual del prototipo

- La compra rechazada libera los numeros reservados.
- Los textos dinamicos se escapan antes de mostrarse.
- Las imagenes aceptadas en admin son JPG, PNG o WebP.
- El tamano de imagen esta limitado a 1 MB en esta version local.
- El envio de correo esta simulado; en produccion debe integrarse con un proveedor real.
