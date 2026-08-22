<x-layouts.app title="Admin - Sorteos CR" section="Administracion">
    <section class="overflow-hidden rounded-2xl bg-[#063d32] text-white shadow-2xl shadow-emerald-950/15">
        <div class="grid gap-6 p-6 lg:grid-cols-[1fr_320px] lg:p-8">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-200">Centro de control</p>
                <h2 class="mt-2 text-4xl font-black tracking-tight">Dashboard operativo</h2>
                <p class="mt-3 max-w-2xl text-emerald-50">Ventas, comprobantes, ocupacion de numeros y actividad reciente en una vista preparada para administracion diaria.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/10 p-5">
                <p class="text-sm font-black text-emerald-100">Estado del sistema</p>
                <strong class="mt-2 block text-3xl font-black">En pruebas</strong>
                <p class="mt-2 text-sm text-emerald-100/80">Subdominio activo antes del dominio oficial.</p>
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="metric-card"><span class="text-sm font-black text-slate-500">Ventas aprobadas</span><strong class="mt-2 block text-3xl font-black text-teal-700">₡{{ number_format($approvedRevenue, 0, ',', ' ') }}</strong></article>
        <article class="metric-card"><span class="text-sm font-black text-slate-500">Comprobantes pendientes</span><strong class="mt-2 block text-3xl font-black">{{ $pendingPayments }}</strong></article>
        <article class="metric-card"><span class="text-sm font-black text-slate-500">Rifas creadas</span><strong class="mt-2 block text-3xl font-black">{{ $raffles->count() }}</strong></article>
        <article class="metric-card"><span class="text-sm font-black text-slate-500">Numeros reservados</span><strong class="mt-2 block text-3xl font-black">{{ number_format($raffles->sum('reserved_numbers_count')) }}</strong></article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
        <article class="surface p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Inventario</p>
                    <h3 class="text-2xl font-black tracking-tight">Vendidos y reservados</h3>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-2 text-sm font-black text-slate-600">Tiempo real al recargar</span>
            </div>
            <div class="mt-5 h-[320px]"><canvas data-admin-sales-chart='@json($salesChart)'></canvas></div>
        </article>

        <article class="surface p-5">
            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Rifas</p>
            <h3 class="text-2xl font-black tracking-tight">Estado por sorteo</h3>
            <div class="mt-4 grid gap-3">
                @foreach ($raffles as $raffle)
                    @php $progress = $raffle->numbers_count ? round(($raffle->sold_numbers_count / $raffle->numbers_count) * 100) : 0; @endphp
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <strong>{{ $raffle->name }}</strong>
                            <span class="text-sm font-black text-teal-700">{{ $progress }}%</span>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200"><div class="h-full rounded-full bg-teal-600" style="width: {{ $progress }}%"></div></div>
                        <p class="mt-2 text-sm text-slate-500">{{ number_format($raffle->sold_numbers_count) }} vendidos · {{ number_format($raffle->reserved_numbers_count) }} reservados</p>
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="surface mt-6 p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Movimiento</p>
                <h3 class="text-2xl font-black tracking-tight">Actividad reciente</h3>
            </div>
            <a class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-black text-slate-700" href="{{ route('admin.payments.index') }}">Revisar pagos</a>
        </div>
        <div class="mt-4 grid gap-3">
            @forelse ($recentOrders as $order)
                <article class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4">
                    <div><strong>{{ $order->buyer_name }}</strong><p class="text-sm text-slate-500">{{ $order->raffle->name }} · {{ $order->numbers->pluck('number')->join(', ') }}</p></div>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-black text-amber-700">{{ $order->status }}</span>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-slate-500">Aun no hay compras.</p>
            @endforelse
        </div>
    </section>
</x-layouts.app>
