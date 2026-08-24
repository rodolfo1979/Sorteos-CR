# Prueba de carga de compras

Este script simula compradores reales contra la pagina publica:

1. Abre la pagina de venta.
2. Lee el token CSRF y las rutas del formulario.
3. Pide numeros al azar.
4. Envia datos del comprador y un comprobante PDF falso.
5. Reporta compras creadas, conflictos controlados, validaciones y fallos tecnicos.

Usar siempre una rifa de prueba, no una rifa real.

## Ejecucion recomendada

Desde `laravel-app`, en Hostinger o Linux:

```bash
BASE_URL=https://sorteoscr.morpho3d.com USERS=10 CONCURRENCY=2 npm run load:purchase
BASE_URL=https://sorteoscr.morpho3d.com USERS=25 CONCURRENCY=5 npm run load:purchase
BASE_URL=https://sorteoscr.morpho3d.com USERS=100 CONCURRENCY=10 npm run load:purchase
```

En Windows PowerShell local:

```powershell
$env:BASE_URL="https://sorteoscr.morpho3d.com"
$env:USERS="10"
$env:CONCURRENCY="2"
npm run load:purchase
```
Subir a 300 o 500 solo cuando las fases pequenas no muestren errores 500 ni lentitud fuerte.

## Variables

- `BASE_URL`: dominio o subdominio de prueba.
- `USERS`: cantidad total de compras simuladas.
- `CONCURRENCY`: compras simultaneas.
- `PACKAGE_COUNT`: paquetes por compra, por defecto 1.
- `TIMEOUT_MS`: tiempo maximo por request, por defecto 20000.
- `PAUSE_MS`: pausa entre pedir numeros y comprar, por defecto 250.
