<x-layouts.app title="Reportes - Sorteos CR" section="Reportes">
    <section class="grid gap-4 md:grid-cols-3">
        <article class="rounded-lg border border-stone-200 bg-white p-5"><span class="text-sm text-stone-500">Ventas aprobadas</span><strong class="block text-2xl">₡{{ number_format($approvedRevenue, 0, ',', ' ') }}</strong></article>
        <article class="rounded-lg border border-stone-200 bg-white p-5"><span class="text-sm text-stone-500">Pendientes</span><strong class="block text-2xl">{{ $pendingCount }}</strong></article>
        <article class="rounded-lg border border-stone-200 bg-white p-5"><span class="text-sm text-stone-500">Rechazadas</span><strong class="block text-2xl">{{ $rejectedCount }}</strong></article>
    </section>
    <section class="mt-5 rounded-lg border border-stone-200 bg-white p-5">
        <h2 class="text-2xl font-black">Historial</h2>
        <div class="mt-4 grid gap-3">
            @foreach ($orders as $order)
                <article class="rounded-lg bg-stone-50 p-4"><strong>{{ $order->buyer_name }}</strong><p class="text-sm text-stone-500">{{ $order->raffle->name }} · {{ $order->numbers->pluck('number')->join(', ') }} · ₡{{ number_format($order->amount_total, 0, ',', ' ') }} · {{ $order->status }}</p></article>
            @endforeach
        </div>
        <div class="mt-4">{{ $orders->links() }}</div>
    </section>
</x-layouts.app>
