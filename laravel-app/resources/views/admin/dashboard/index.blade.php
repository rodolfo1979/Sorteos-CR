<x-layouts.app title="Admin - Sorteos CR" section="Administracion">
    <section class="rounded-lg bg-emerald-900 p-6 text-white shadow-sm">
        <p class="text-xs font-black uppercase text-emerald-200">Centro de control</p>
        <h2 class="mt-1 text-3xl font-black">Dashboard en tiempo real</h2>
        <p class="mt-2 text-emerald-50">Resumen de ventas, comprobantes y actividad.</p>
    </section>

    <section class="mt-5 grid gap-4 md:grid-cols-3">
        <article class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm"><span class="text-sm font-bold text-stone-500">Ventas aprobadas</span><strong class="mt-2 block text-2xl">₡{{ number_format($approvedRevenue, 0, ',', ' ') }}</strong></article>
        <article class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm"><span class="text-sm font-bold text-stone-500">Comprobantes pendientes</span><strong class="mt-2 block text-2xl">{{ $pendingPayments }}</strong></article>
        <article class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm"><span class="text-sm font-bold text-stone-500">Rifas creadas</span><strong class="mt-2 block text-2xl">{{ $raffles->count() }}</strong></article>
    </section>

    <section class="mt-5 rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
        <h3 class="text-xl font-black">Actividad reciente</h3>
        <div class="mt-4 grid gap-3">
            @forelse ($recentOrders as $order)
                <article class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-stone-50 p-4">
                    <div><strong>{{ $order->buyer_name }}</strong><p class="text-sm text-stone-500">{{ $order->raffle->name }} · {{ $order->numbers->pluck('number')->join(', ') }}</p></div>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-black text-amber-700">{{ $order->status }}</span>
                </article>
            @empty
                <p class="rounded-lg border border-dashed border-stone-300 p-6 text-center text-stone-500">Aun no hay compras.</p>
            @endforelse
        </div>
    </section>
</x-layouts.app>
