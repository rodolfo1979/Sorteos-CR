<x-layouts.app title="Detalle de compra - Sorteos CR" section="Verificacion">
    @php
        $localCreated = $order->created_at->timezone('America/Costa_Rica')->format('d/m/Y H:i');
        $localUpdated = $order->updated_at->timezone('America/Costa_Rica')->format('d/m/Y H:i');
        $statusClasses = [
            'approved' => 'bg-cyan-50 text-cyan-700',
            'rejected' => 'bg-red-50 text-red-700',
            'pending' => 'bg-amber-50 text-amber-700',
        ];
        $whatsappPhone = preg_replace('/\D+/', '', $order->buyer_phone ?? '');
    @endphp

    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a class="text-sm font-black text-indigo-700 transition hover:text-indigo-900" href="{{ route('admin.payments.index') }}">Volver a pagos</a>
                <h2 class="mt-1 text-3xl font-black tracking-tight">Orden {{ strtoupper(substr($order->public_uuid, 0, 8)) }}</h2>
                <p class="mt-1 text-sm font-bold text-slate-500">Creada {{ $localCreated }} · Actualizada {{ $localUpdated }}</p>
            </div>
            <span class="rounded-full px-4 py-2 text-sm font-black {{ $statusClasses[$order->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $statusLabel }}</span>
        </div>

        <section class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-700">Cliente</p>
                        <h3 class="mt-1 text-2xl font-black">{{ $order->buyer_name }}</h3>
                        <p class="mt-2 text-sm font-bold text-slate-500">{{ $order->buyer_phone }} · {{ $order->buyer_email ?: 'Sin correo' }}</p>
                    </div>
                    <strong class="rounded-2xl bg-slate-950 px-4 py-3 text-xl font-black text-white">₡{{ number_format($order->amount_total, 0, ',', ' ') }}</strong>
                </div>

                <dl class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-black uppercase tracking-wide text-slate-500">Sorteo</dt>
                        <dd class="mt-1 font-black">{{ $order->raffle?->name ?? 'Sorteo eliminado' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-black uppercase tracking-wide text-slate-500">Asignacion</dt>
                        <dd class="mt-1 font-black">{{ $order->assignment_mode === 'random' ? 'Al azar' : 'Manual' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-black uppercase tracking-wide text-slate-500">Paquetes</dt>
                        <dd class="mt-1 font-black">{{ $order->package_count }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-black uppercase tracking-wide text-slate-500">Comprobante</dt>
                        <dd class="mt-1 font-black">{{ $order->receipt_original_name ?: 'No disponible' }}</dd>
                    </div>
                </dl>

                <div class="mt-5">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Numeros</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($order->numbers as $number)
                            <span class="rounded-xl bg-cyan-700 px-4 py-2 text-lg font-black text-white">{{ $number->number }}</span>
                        @endforeach
                    </div>
                </div>
            </article>

            <aside class="space-y-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Acciones</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($order->receipt_path)
                            <a class="rounded-xl bg-slate-100 px-4 py-2 font-black text-slate-800 transition hover:bg-slate-200" href="{{ Storage::url($order->receipt_path) }}" target="_blank" rel="noopener">Ver comprobante</a>
                        @endif
                        @if ($order->status === 'pending')
                            <form method="post" action="{{ route('admin.payments.approve', $order) }}">@csrf<button class="rounded-xl bg-indigo-700 px-4 py-2 font-black text-white transition hover:bg-indigo-800">Aprobar</button></form>
                            <form method="post" action="{{ route('admin.payments.reject', $order) }}">@csrf<button class="rounded-xl bg-red-50 px-4 py-2 font-black text-red-700 transition hover:bg-red-100">Rechazar</button></form>
                        @endif
                    </div>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Correos</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <form method="post" action="{{ route('admin.payments.resend-email', $order) }}">@csrf<input type="hidden" name="type" value="reserved"><button class="rounded-xl bg-white px-4 py-2 font-black text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">Reenviar reserva</button></form>
                        @if ($order->status === 'approved')
                            <form method="post" action="{{ route('admin.payments.resend-email', $order) }}">@csrf<input type="hidden" name="type" value="approved"><button class="rounded-xl bg-cyan-700 px-4 py-2 font-black text-white transition hover:bg-cyan-800">Reenviar aprobacion</button></form>
                        @elseif ($order->status === 'rejected')
                            <form method="post" action="{{ route('admin.payments.resend-email', $order) }}">@csrf<input type="hidden" name="type" value="rejected"><button class="rounded-xl bg-red-50 px-4 py-2 font-black text-red-700 transition hover:bg-red-100">Reenviar rechazo</button></form>
                        @endif
                        <form method="post" action="{{ route('admin.payments.resend-email', $order) }}">@csrf<input type="hidden" name="type" value="admin"><button class="rounded-xl bg-amber-50 px-4 py-2 font-black text-amber-800 transition hover:bg-amber-100">Avisar admin</button></form>
                    </div>
                </article>
            </aside>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-700">Resumen WhatsApp</p>
                <div class="flex flex-wrap gap-2">
                    <button class="rounded-xl bg-slate-950 px-4 py-2 font-black text-white transition hover:bg-indigo-700" type="button" data-copy-summary>Copiar resumen</button>
                    @if ($whatsappPhone !== '')
                        <a class="rounded-xl bg-emerald-600 px-4 py-2 font-black text-white transition hover:bg-emerald-700" href="https://wa.me/{{ $whatsappPhone }}?text={{ urlencode($whatsappSummary) }}" target="_blank" rel="noopener">Abrir WhatsApp</a>
                    @endif
                </div>
            </div>
            <textarea class="mt-4 min-h-44 w-full rounded-2xl border border-slate-200 bg-slate-50 p-4 font-mono text-sm text-slate-700" data-summary readonly>{{ $whatsappSummary }}</textarea>
        </section>
    </div>

    <script>
        document.querySelector('[data-copy-summary]')?.addEventListener('click', async () => {
            const summary = document.querySelector('[data-summary]')?.value || '';
            await navigator.clipboard.writeText(summary);
        });
    </script>
</x-layouts.app>