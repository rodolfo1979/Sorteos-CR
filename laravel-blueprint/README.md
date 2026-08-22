# Blueprint Laravel para Produccion

Esta carpeta contiene una guia tecnica para migrar el prototipo a Laravel en hosting compartido.

## Objetivo

Evitar fallos en produccion cuando muchas personas compran tiquetes al mismo tiempo.

La regla principal es que ningun numero puede venderse dos veces. Para eso, las reservas y ventas deben hacerse con transacciones de base de datos y bloqueo de filas.

## Modulos

- Rifa publica.
- Seleccion manual de numeros.
- Asignacion al azar.
- Compra de inversos.
- Ordenes pendientes de validacion.
- Aprobacion/rechazo de comprobantes.
- Liberacion automatica de reservas vencidas.
- Panel administrativo.
- Reportes.
- Auditoria.

## Archivos

- `database-schema.md`: tablas, estados e indices.
- `migration-examples.php`: migraciones base para Laravel.
- `RaffleReservationService.php`: servicio critico para reservar numeros sin doble venta.
- `AdminPaymentController.php`: ejemplo de aprobar/rechazar pagos.
- `Models.php`: modelos y relaciones base.
- `FormRequests.php`: validacion de formularios publicos/admin.
- `routes-example.php`: rutas publicas y administrativas.
- `ExpireReservationsCommand.php`: comando para liberar reservas vencidas.
- `scheduler-example.php`: programacion del comando cada minuto.
- `deployment-shared-hosting.md`: recomendaciones para servidor compartido.

## Principio de estabilidad

El frontend nunca debe decidir si un numero esta vendido. El frontend solo pide reservar. Laravel y MySQL deciden dentro de una transaccion.
