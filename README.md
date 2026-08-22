# Panel de Rifas Configurable

Prototipo web estático para administrar rifas digitales.

## Incluye

- Paginas separadas para venta publica, confirmacion, panel administrativo, validacion de pagos, reportes y control de numeros.
- Crear diferentes sorteos desde el panel.
- Pausar o reactivar la venta de cada sorteo sin eliminarlo.
- Configurar cantidad total de números: 5, 10,000, 100,000, etc.
- Configurar precio por compra, por ejemplo ₡4,000.
- Configurar cuántos números recibe el comprador por ese monto.
- Elegir si el comprador selecciona sus números o si el sistema los asigna al azar.
- Configurar cuantos cambios de numeros al azar puede hacer el comprador.
- Subir fotografia del premio desde el panel admin.
- Mostrar la fotografia del premio en la pagina publica de compra.
- Registrar compradores con comprobante de pago.
- Visualizar el comprobante en el panel de pagos antes de aprobar o rechazar.
- Mostrar confirmacion de compra con estado pendiente.
- Aprobar o rechazar compras desde el panel de pagos.
- Ver historial completo de ordenes por estado.
- Controlar numeros vendidos, reservados y disponibles con busqueda y filtros.
- Simular envio de correo al comprador cuando administracion aprueba el pago.
- Estados de números: disponible, reservado y vendido.
- Datos guardados en `localStorage` del navegador.
- Documento de seguridad en `SECURITY.md`.
- Blueprint de migracion a Laravel en `laravel-blueprint/`.

## Cómo abrirlo

Abre `index.html` en el navegador para ver la pagina publica.

- `index.html`: venta publica.
- `confirmacion.html`: confirmacion despues de enviar comprobante.
- `admin.html`: configuracion de rifas.
- `pagos.html`: validacion de comprobantes.
- `reportes.html`: historial y resumen de ventas.
- `numeros.html`: inventario administrativo de numeros.

Este prototipo no usa servidor ni base de datos todavia. Para produccion conviene migrar la logica a backend con base de datos indexada, autenticacion de administradores, almacenamiento real de comprobantes y envio real de correos.
