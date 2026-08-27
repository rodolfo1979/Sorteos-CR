# Arquitectura Multitenant - Sorteos CR

## Objetivo

Convertir Sorteos CR en una plataforma donde varios organizadores puedan operar rifas independientes usando el mismo codigo Laravel, sin mezclar datos, pagos, correos, numeros, reportes ni configuracion administrativa.

El objetivo no es duplicar el sistema por cliente. El objetivo es tener una sola aplicacion, una sola base de datos y aislamiento por tenant desde el dominio, las consultas, el admin y los correos.

## Decision Arquitectonica

Modelo recomendado: **single database, tenant_id por tabla**.

Motivo:
- El sistema actual es compacto y ya usa relaciones simples: rifas, numeros, ordenes, eventos.
- Permite evolucionar sin mover cada cliente a una base separada.
- Es mas economico para Hostinger/VPS y mas facil de respaldar.
- Escala bien para decenas o cientos de organizadores si se indexa correctamente.

No se recomienda iniciar con una base de datos por tenant. Esa opcion aumenta complejidad operativa, migraciones, backups y soporte. Solo tendria sentido si en el futuro hay clientes grandes con requerimientos legales o de aislamiento fisico.

## Entidades Nuevas

### tenants

Representa cada organizador/cliente de la plataforma.

Campos sugeridos:
- id
- name
- slug
- status: active, suspended
- primary_domain
- admin_email
- notification_email
- timezone, por defecto America/Costa_Rica
- currency, por defecto CRC
- branding: logo_path, primary_color, accent_color
- created_at, updated_at

### tenant_domains

Permite dominios y subdominios por tenant.

Campos sugeridos:
- id
- tenant_id
- domain
- type: primary, alias, admin
- is_verified
- created_at, updated_at

Ejemplos:
- sorteoscr.morpho3d.com -> tenant principal
- cliente1.sorteoscr.com -> tenant cliente 1
- rifascliente.com -> dominio propio de cliente

### tenant_users

Usuarios administrativos por tenant.

Campos sugeridos:
- id
- tenant_id nullable para superadmin
- name
- email
- password
- role: owner, admin, viewer, superadmin
- status
- last_login_at
- created_at, updated_at

Esta tabla reemplazaria gradualmente el Basic Auth actual.

### tenant_settings

Configuracion operativa por tenant.

Campos sugeridos:
- tenant_id
- payment_instructions
- whatsapp_number
- mail_from_address
- mail_from_name
- brevo_sender_name
- notification_email
- reservation_minutes_default
- created_at, updated_at

## Tablas Actuales Que Deben Recibir tenant_id

Agregar `tenant_id` a:
- raffles
- raffle_numbers
- orders
- order_events

Regla: `raffles.tenant_id` es la fuente principal. `raffle_numbers`, `orders` y `order_events` tambien deben llevar `tenant_id` para filtros rapidos, auditoria y seguridad defensiva.

Indices sugeridos:
- raffles: unique(tenant_id, slug), index(tenant_id, sale_enabled, is_featured)
- raffle_numbers: unique(raffle_id, number), index(tenant_id, raffle_id, status), index(tenant_id, reserved_until)
- orders: index(tenant_id, raffle_id, status), index(tenant_id, created_at), unique(public_uuid)
- order_events: index(tenant_id, order_id, created_at), index(tenant_id, action)

Cambio importante: `raffles.slug` hoy es unico global. En multitenant debe ser unico por tenant: `tenant_id + slug`.

## Resolucion De Tenant

Crear un servicio `TenantResolver` y middleware `ResolveTenant`.

Orden recomendado:
1. Resolver por `Host` del request usando `tenant_domains.domain`.
2. Si el host es el dominio principal de plataforma, usar tenant principal o exigir slug publico.
3. Guardar tenant actual en un contexto de request: `CurrentTenant`.
4. Si no existe tenant activo, responder 404.

No usar tenant desde parametros manipulables como query string para rutas publicas. El dominio debe ser la fuente confiable.

## Aislamiento De Datos

Implementar un trait `BelongsToTenant` para modelos tenant-aware.

Funciones:
- Relacion `tenant()`.
- Scope `forTenant($tenant)`.
- Asignar `tenant_id` automaticamente al crear si hay tenant actual.

Decisiones de seguridad:
- En controladores publicos y admin, todas las consultas deben filtrar por tenant.
- En rutas con model binding, usar scoped bindings o resolver manualmente por `tenant_id`.
- No confiar solo en policies; el filtro debe estar en la consulta.
- Los jobs de cola deben guardar `tenant_id` y restaurar contexto antes de ejecutar.

## Rutas Publicas

Actual:
- `/`
- `/rifas/{slug}`
- `/rifas/{raffleId}/numeros-disponibles`
- `/rifas/{raffleId}/random`
- `/rifas/{raffleId}/comprar`
- `/confirmacion/{uuid}`

Arquitectura multitenant:
- Todas pasan por `ResolveTenant`.
- `/` muestra la rifa destacada del tenant actual.
- `/rifas/{slug}` busca por `tenant_id + slug`.
- Endpoints con `raffleId` validan que la rifa pertenezca al tenant actual.
- `/confirmacion/{uuid}` busca orden por `tenant_id + public_uuid` para evitar fugas entre tenants.

## Rutas Admin

Actual:
- `/admin` protegido por Basic Auth global.

Arquitectura objetivo:
- `/admin/login`
- `/admin` para usuarios del tenant resuelto por dominio.
- Superadmin separado: `/platform` o `admin.sorteoscr.com`.

Fase transitoria:
- Mantener Basic Auth mientras se agrega tenant principal.
- Agregar `tenant_id` al admin por dominio actual.
- Luego migrar a sesiones Laravel con `tenant_users`.

## Correos

Cada correo debe usar tenant actual:
- remitente
- nombre de remitente
- email admin de notificacion
- branding del correo
- links publicos con dominio del tenant

Los jobs de correo deben incluir `tenant_id`. Al procesar cola, el job debe restaurar tenant antes de renderizar plantillas.

El historial `order_events` debe registrar `tenant_id` para auditoria por cliente.

## Archivos Y Comprobantes

Ruta recomendada por tenant:
- `receipts/{tenant_id}/{order_uuid}/archivo.jpg`
- `raffles/{tenant_id}/{raffle_id}/imagen.jpg`

Esto evita mezclar imagenes y simplifica limpieza/backups.

## Estado actual de implementacion

Implementado en Fase 1:
- Documento de arquitectura versionado.
- Tablas base `tenants`, `tenant_domains` y `tenant_settings`.
- `tenant_id` nullable en `raffles`, `raffle_numbers`, `orders` y `order_events`.
- Tenant principal `Sorteos CR` con dominio/configuracion inicial.
- Backfill de datos existentes al tenant principal.
- Relaciones Eloquent basicas para tenant.
- Nuevas rifas, numeros, ordenes y eventos quedan asociados al tenant principal.

Pendiente para Fase 2:
- Resolver tenant real por dominio.
- Filtrar todas las consultas por tenant actual.
- Separar caches/snapshots por tenant.
- Proteger model binding por tenant.
## Fases De Implementacion

### Fase 1 - Base multitenant sin cambiar UX

Objetivo: preparar datos sin afectar produccion.

Cambios:
- Crear `tenants`, `tenant_domains`, `tenant_settings`.
- Crear tenant principal para Sorteos CR.
- Agregar `tenant_id` nullable a tablas actuales.
- Backfill de tenant_id en rifas, numeros, ordenes y eventos existentes.
- Agregar indices.
- Agregar modelos `Tenant`, `TenantDomain`, `TenantSetting`.

Riesgo: bajo si se mantiene nullable durante migracion.

### Fase 2 - Resolucion por dominio y filtros defensivos

Objetivo: que todas las consultas operen contra tenant actual.

Cambios:
- Middleware `ResolveTenant`.
- Servicio `CurrentTenant`.
- Aplicar middleware a rutas publicas y admin.
- Filtrar consultas en controladores y servicios.
- Proteger model binding.
- Ajustar snapshots/cache para incluir tenant_id en claves.

Riesgo: medio. Requiere pruebas de compra, admin, numeros y confirmacion.

### Fase 3 - Admin por tenant

Objetivo: reemplazar Basic Auth global por usuarios reales.

Cambios:
- `tenant_users`.
- Login/logout Laravel.
- Roles: owner, admin, viewer, superadmin.
- Middleware de permisos.
- Panel superadmin para tenants.

Riesgo: medio-alto. Toca seguridad.

### Fase 4 - Branding, correos y dominios propios

Objetivo: cada tenant con su identidad.

Cambios:
- Logo/color/nombre por tenant.
- Configuracion de remitente y notificaciones por tenant.
- Links de correo con dominio correcto.
- Validacion de dominios.

Riesgo: medio. Toca correos y assets.

### Fase 5 - Operacion SaaS

Objetivo: administrar clientes como plataforma.

Cambios:
- Superadmin de tenants.
- Suspender/reactivar tenant.
- Metricas por tenant.
- Exportaciones por tenant.
- Backups y limpieza por tenant.

Riesgo: bajo-medio si fases anteriores estan solidas.

## Plan De Pruebas Obligatorio

Antes de activar multitenant:
- Tenant A no ve rifas de Tenant B.
- Tenant A no puede comprar rifa de Tenant B usando ID directo.
- Tenant A no puede abrir confirmacion de Tenant B por UUID.
- Admin Tenant A no ve pagos, numeros ni reportes de Tenant B.
- Correos de Tenant A usan dominio/remitente de Tenant A.
- Jobs de cola mantienen tenant correcto.
- Snapshot publico/cache separa tenant_id.
- Backfill no duplica ni rompe ordenes existentes.

## Riesgos Principales

1. Fuga de datos entre tenants por consultas sin `tenant_id`.
2. Model binding que resuelve por ID global.
3. Cache/snapshot compartido entre tenants.
4. Jobs de cola sin contexto tenant.
5. Correos con links o remitentes del tenant incorrecto.
6. Archivos subidos en rutas compartidas sin separacion.

## Recomendacion Ejecutiva

No implementar todo de una vez.

Primer cambio de codigo recomendado: **Fase 1 solamente**. Crear estructura tenant, asignar todo lo existente al tenant principal y no cambiar todavia el comportamiento publico. Despues se prueba y se despliega. Solo cuando eso este estable, se activa resolucion por dominio y filtros.

Esta ruta permite evolucionar hacia multitenant sin poner en riesgo las compras actuales.