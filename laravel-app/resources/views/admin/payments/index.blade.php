<x-layouts.app title="Pagos - Sorteos CR" section="Verificacion">
    <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-rose-700">Revision</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight">Comprobantes pendientes</h2>
                </div>
                <span class="rounded-full bg-amber-50 px-3 py-2 text-sm font-black text-amber-700">{{ $pendingOrders->count() }} pendiente(s)</span>
            </div>

            <div class="mt-5 grid gap-4">
                @forelse ($pendingOrders as $order)
                    <article class="rounded-2xl border border-slate-200 p-4 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="font-black">{{ $order->buyer_name }} - {{ $order->raffle->name }}</h3>
                                <p class="text-sm text-slate-500">{{ $order->buyer_phone }} · {{ $order->buyer_email ?: 'Sin correo' }}</p>
                                <p class="mt-1 text-xs font-bold text-slate-400">Orden {{ strtoupper(substr($order->public_uuid, 0, 8)) }} · {{ $order->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <span class="rounded-full bg-amber-50 px-3 py-1 font-black text-amber-700">₡{{ number_format($order->amount_total, 0, ',', ' ') }}</span>
                        </div>
                        <p class="mt-3 text-sm text-slate-700">Numeros: <strong>{{ $order->numbers->pluck('number')->join(', ') }}</strong></p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if ($order->receipt_path)
                                <a class="inline-flex rounded-xl bg-slate-100 px-4 py-2 font-black text-slate-800 transition hover:bg-slate-200" href="{{ Storage::url($order->receipt_path) }}" target="_blank" rel="noopener">Ver comprobante</a>
                            @else
                                <span class="inline-flex rounded-xl bg-red-50 px-4 py-2 font-black text-red-700">Sin comprobante</span>
                            @endif
                            <form method="post" action="{{ route('admin.payments.approve', $order) }}">@csrf<button class="rounded-xl bg-rose-700 px-4 py-2 font-black text-white transition hover:bg-rose-800">Aprobar</button></form>
                            <form method="post" action="{{ route('admin.payments.reject', $order) }}">@csrf<button class="rounded-xl bg-red-50 px-4 py-2 font-black text-red-700 transition hover:bg-red-100">Rechazar</button></form>
                        </div>
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-slate-500">No hay comprobantes pendientes.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Historial</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight">Compras procesadas</h2>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-2 text-sm font-black text-slate-600">{{ $processedOrders->total() }} resultado(s)</span>
            </div>

            <form class="mt-5 grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-[1fr_auto_auto]" method="get" action="{{ route('admin.payments.index') }}">
                <label class="grid gap-1 text-sm font-black text-slate-600">
                    Buscar cliente, orden o numero
                    <input class="field bg-white" name="q" value="{{ $search }}" placeholder="Ej: Rodolfo, 13BA9F78, 00152">
                </label>
                <button class="self-end rounded-xl bg-slate-950 px-4 py-3 font-black text-white transition hover:bg-rose-700" type="submit">Buscar</button>
                @if ($search !== '')
                    <a class="self-end rounded-xl bg-white px-4 py-3 text-center font-black text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-100" href="{{ route('admin.payments.index') }}">Limpiar</a>
                @endif
            </form>

            <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Sorteo</th>
                            <th class="px-4 py-3">Numeros</th>
                            <th class="px-4 py-3">Monto</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Comprobante</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($processedOrders as $order)
                            <tr>
                                <td class="px-4 py-3 align-top"><strong>{{ $order->buyer_name }}</strong><br><span class="text-xs text-slate-500">{{ $order->buyer_phone }} · {{ $order->buyer_email ?: 'Sin correo' }}</span></td>
                                <td class="px-4 py-3 align-top font-bold">{{ $order->raffle->name }}</td>
                                <td class="max-w-sm px-4 py-3 align-top font-bold text-slate-700">{{ $order->numbers->pluck('number')->join(', ') }}</td>
                                <td class="px-4 py-3 align-top font-black">₡{{ number_format($order->amount_total, 0, ',', ' ') }}</td>
                                <td class="px-4 py-3 align-top">
                                    @if ($order->status === 'approved')
                                        <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-black text-rose-700">Aprobada</span>
                                    @else
                                        <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-black text-red-700">Rechazada</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top text-slate-500">{{ $order->updated_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 align-top">
                                    @if ($order->receipt_path)
                                        <a class="inline-flex rounded-lg bg-slate-100 px-3 py-2 font-black text-slate-800 transition hover:bg-slate-200" href="{{ Storage::url($order->receipt_path) }}" target="_blank" rel="noopener">Ver</a>
                                    @else
                                        <span class="text-xs font-bold text-slate-400">No disponible</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-8 text-center text-slate-500" colspan="7">Aun no hay compras aprobadas o rechazadas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $processedOrders->links() }}
            </div>
        </section>
    </div>
</x-layouts.app>

