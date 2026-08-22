# Esquema de Base de Datos

## `raffles`

Guarda la configuracion de cada sorteo.

Campos principales:

- `id`
- `name`
- `slug`
- `total_numbers`
- `price`
- `numbers_per_order`
- `max_random_changes`
- `assignment_mode`: `manual` o `random`
- `sale_enabled`
- `draw_date`
- `prize`
- `image_path`
- `organizer_name`
- `organizer_whatsapp`
- `payment_info`
- `rules_text`
- `created_at`
- `updated_at`

## `raffle_numbers`

Guarda cada numero individual de una rifa.

Campos principales:

- `id`
- `raffle_id`
- `number`
- `status`: `available`, `reserved`, `sold`
- `order_id`
- `reserved_until`
- `created_at`
- `updated_at`

Indices:

- Unico: `raffle_id + number`
- Indice: `raffle_id + status`
- Indice: `reserved_until`

## `orders`

Guarda la compra.

Campos principales:

- `id`
- `raffle_id`
- `buyer_name`
- `buyer_phone`
- `buyer_email`
- `amount`
- `package_count`
- `assignment_mode`
- `status`: `pending`, `approved`, `rejected`, `expired`
- `receipt_path`
- `approved_at`
- `rejected_at`
- `created_at`
- `updated_at`

## `order_numbers`

Relaciona ordenes con numeros.

Campos principales:

- `id`
- `order_id`
- `raffle_number_id`
- `number`
- `created_at`
- `updated_at`

Indice unico:

- `order_id + raffle_number_id`

## `audit_logs`

Registra acciones administrativas.

Campos principales:

- `id`
- `user_id`
- `action`
- `entity_type`
- `entity_id`
- `metadata`
- `ip_address`
- `user_agent`
- `created_at`

## Estados criticos

`available`: disponible para compra.

`reserved`: apartado temporalmente mientras se valida comprobante.

`sold`: pago aprobado, numero vendido definitivamente.

Si el admin rechaza el pago, los numeros vuelven a `available`.

Si la reserva vence, los numeros vuelven a `available`.
