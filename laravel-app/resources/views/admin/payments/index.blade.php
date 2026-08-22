<x-layouts.app title="Pagos - Sorteos CR" section="Verificacion">
    <section class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
        <h2 class="text-2xl font-black">Comprobantes pendientes</h2>
        <div class="mt-5 grid gap-4">
            @forelse ($orders as $order)
                <article class="rounded-lg border border-stone-200 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><h3 class="font-black">{{ $order->buyer_name }} - {{ $order->raffle->name }}</h3><p class="text-sm text-stone-500">{{ $order->buyer_phone }} · {{ $order->buyer_email }}</p></div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 font-black text-emerald-700">₡{{ number_format($order->amount_total, 0, ',', ' ') }}</span>
                    </div>
                    <p class="mt-3">Numeros: <strong>{{ $order->numbers->pluck('number')->join(', ') }}</strong></p>
                    @if ($order->receipt_path)
                        <a class="mt-3 inline-flex rounded-lg bg-stone-100 px-3 py-2 font-black" href="{{ Storage::url($order->receipt_path) }}" target="_blank">Ver comprobante</a>
                    @endif
                    <div class="mt-4 flex flex-wrap gap-2">
                        <form method="post" action="{{ route('admin.payments.approve', $order) }}">@csrf<button class="rounded-lg bg-emerald-700 px-4 py-2 font-black text-white">Aprobar</button></form>
                        <form method="post" action="{{ route('admin.payments.reject', $order) }}">@csrf<button class="rounded-lg bg-red-100 px-4 py-2 font-black text-red-700">Rechazar</button></form>
                    </div>
                </article>
            @empty
                <p class="rounded-lg border border-dashed border-stone-300 p-6 text-center text-stone-500">No hay comprobantes pendientes.</p>
            @endforelse
        </div>
    </section>
</x-layouts.app>
