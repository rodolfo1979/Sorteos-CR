<!doctype html>
<html lang="es">
<body style="margin:0;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f8fafc;padding:24px 12px;">
<tr><td align="center">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:680px;background:#ffffff;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden;">
<tr><td style="background:#111827;padding:28px;color:#ffffff;">
<p style="margin:0;font-size:12px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#fde68a;">Administracion Sorteos CR</p>
<h1 style="margin:8px 0 0;font-size:28px;line-height:1.1;">Nuevo comprobante pendiente</h1>
<p style="margin:12px 0 0;color:#e5e7eb;line-height:1.6;">Hay una compra esperando revision en el panel de pagos.</p>
</td></tr>
<tr><td style="padding:24px;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr><td style="padding:8px 0;color:#64748b;">Orden</td><td align="right" style="font-weight:900;">{{ strtoupper(substr($order->public_uuid, 0, 8)) }}</td></tr>
<tr><td style="padding:8px 0;color:#64748b;">Sorteo</td><td align="right" style="font-weight:900;">{{ $order->raffle->name }}</td></tr>
<tr><td style="padding:8px 0;color:#64748b;">Cliente</td><td align="right" style="font-weight:900;">{{ $order->buyer_name }}</td></tr>
<tr><td style="padding:8px 0;color:#64748b;">Telefono</td><td align="right" style="font-weight:900;">{{ $order->buyer_phone }}</td></tr>
<tr><td style="padding:8px 0;color:#64748b;">Correo</td><td align="right" style="font-weight:900;">{{ $order->buyer_email ?: 'No indicado' }}</td></tr>
<tr><td style="padding:8px 0;color:#64748b;">Monto</td><td align="right" style="font-size:20px;font-weight:900;">₡{{ number_format($order->amount_total, 0, ',', ' ') }}</td></tr>
</table>
<p style="margin:18px 0 10px;font-weight:800;color:#111827;text-transform:uppercase;font-size:13px;">Numeros reservados</p>
@foreach ($order->numbers as $number)
<span style="display:inline-block;margin:0 8px 10px 0;padding:10px 14px;border-radius:12px;background:#be123c;color:#fff;font-weight:900;">{{ $number->number }}</span>
@endforeach
<p style="margin:18px 0 0;line-height:1.6;color:#475569;">Ingresa al panel de pagos para revisar el comprobante, aprobarlo o rechazarlo.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
