<!doctype html>
<html lang="es">
<body style="margin:0;background:#f3f8f4;font-family:Arial,sans-serif;color:#0f172a;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f3f8f4;padding:24px 12px;">
<tr><td align="center">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:640px;background:#ffffff;border:1px solid #d1fae5;border-radius:18px;overflow:hidden;">
<tr><td style="background:#047857;padding:28px;color:#ffffff;">
<p style="margin:0;font-size:12px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#bbf7d0;">Orden {{ strtoupper(substr($order->public_uuid, 0, 8)) }}</p>
<h1 style="margin:8px 0 0;font-size:30px;line-height:1.1;">Tickets reservados</h1>
<p style="margin:12px 0 0;color:#ecfdf5;line-height:1.6;">Recibimos tu comprobante. La compra queda pendiente de validacion por administracion.</p>
</td></tr>
<tr><td style="padding:24px;">
<p style="margin:0 0 12px;font-weight:800;color:#047857;text-transform:uppercase;font-size:13px;">Tus tickets digitales</p>
@foreach ($order->numbers as $number)
<span style="display:inline-block;margin:0 8px 10px 0;padding:12px 18px;border-radius:14px;background:#10b981;color:white;font-size:22px;font-weight:900;letter-spacing:1px;">{{ $number->number }}</span>
@endforeach
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:18px;border-top:1px solid #e2e8f0;padding-top:18px;">
<tr><td style="color:#64748b;font-size:14px;">Monto</td><td align="right" style="font-size:20px;font-weight:900;">₡{{ number_format($order->amount_total, 0, ',', ' ') }}</td></tr>
<tr><td style="padding-top:10px;color:#64748b;font-size:14px;">Estado</td><td align="right" style="padding-top:10px;font-size:18px;font-weight:900;color:#b45309;">Pendiente</td></tr>
</table>
<p style="margin:20px 0 0;line-height:1.6;color:#475569;">Cuando el comprobante sea validado, te enviaremos otro correo confirmando que el pago fue aprobado y tu compra quedo correcta.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
