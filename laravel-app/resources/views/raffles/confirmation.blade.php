<x-layouts.app title="Confirmacion - Sorteos CR" section="Compra recibida">
    <section class="max-w-4xl rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-black uppercase text-stone-500">Orden {{ substr($order->public_uuid, 0, 8) }}</p>
        <h2 class="mt-1 text-3xl font-black">Numeros reservados</h2>
        <p class="mt-3 text-stone-600">Tu comprobante queda pendiente de validacion por administracion.</p>
        <div class="mt-5 rounded-lg bg-stone-50 p-4">
            <p class="text-sm font-bold text-stone-500">Numeros</p>
            <p class="mt-2 text-2xl font-black">{{ $order->numbers->pluck('number')->join(', ') }}</p>
        </div>
        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            <article class="rounded-lg border border-stone-200 p-4"><span class="text-sm text-stone-500">Monto</span><strong class="block text-xl">₡{{ number_format($order->amount_total, 0, ',', ' ') }}</strong></article>
            <article class="rounded-lg border border-stone-200 p-4"><span class="text-sm text-stone-500">Estado</span><strong class="block text-xl">{{ ucfirst($order->status) }}</strong></article>
        </div>
    </section>
</x-layouts.app>
