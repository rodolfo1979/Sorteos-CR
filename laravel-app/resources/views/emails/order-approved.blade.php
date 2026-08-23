<!doctype html>
<html lang="es">
<body style="margin:0;background:#fff7ed;font-family:Arial,sans-serif;color:#0f172a;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#fff7ed;padding:24px 12px;">
<tr><td align="center">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:640px;background:#ffffff;border:1px solid #fecdd3;border-radius:18px;overflow:hidden;">
<tr><td style="background:linear-gradient(135deg,#111827,#e11d48);padding:28px;color:#ffffff;">
<p style="margin:0;font-size:12px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#fde68a;">Orden {{ strtoupper(substr($order->public_uuid, 0, 8)) }}</p>
<h1 style="margin:8px 0 0;font-size:30px;line-height:1.1;">Pago validado</h1>
<p style="margin:12px 0 0;color:#fff1f2;line-height:1.6;">Tu recibo de pago fue validado y tu compra quedo correcta.</p>
</td></tr>
<tr><td style="padding:24px;">
<p style="margin:0 0 12px;font-weight:800;color:#111827;text-transform:uppercase;font-size:13px;">Tickets confirmados</p>
@foreach ($order->numbers as $number)
<span style="display:inline-block;margin:0 8px 10px 0;padding:12px 18px;border-radius:14px;background:#be123c;color:white;font-size:22px;font-weight:900;letter-spacing:1px;border:1px solid #fbbf24;">{{ $number->number }}</span>
@endforeach
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:18px;border-top:1px solid #e2e8f0;padding-top:18px;">
<tr><td style="color:#64748b;font-size:14px;">Sorteo</td><td align="right" style="font-size:16px;font-weight:900;">{{ $order->raffle->name }}</td></tr>
<tr><td style="padding-top:10px;color:#64748b;font-size:14px;">Monto pagado</td><td align="right" style="padding-top:10px;font-size:20px;font-weight:900;">₡{{ number_format($order->amount_total, 0, ',', ' ') }}</td></tr>
<tr><td style="padding-top:10px;color:#64748b;font-size:14px;">Estado</td><td align="right" style="padding-top:10px;font-size:18px;font-weight:900;color:#be123c;">Aprobado</td></tr>
</table>
<p style="margin:20px 0 0;line-height:1.6;color:#475569;">Conserva este correo como confirmacion de tus tickets para el sorteo {{ $order->raffle->name }}.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
