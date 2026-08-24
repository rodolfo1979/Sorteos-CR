<x-layouts.app title="Admin - Sorteos CR" section="Administracion">
    <div data-admin-realtime-url="{{ route('admin.realtime') }}">
    <section class="overflow-hidden rounded-2xl bg-[radial-gradient(circle_at_top_right,rgba(34,211,238,0.28),transparent_34%),linear-gradient(135deg,#0f172a,#1e1b4b_58%,#083344)] text-white shadow-2xl shadow-slate-950/20">
        <div class="grid gap-6 p-6 lg:grid-cols-[1fr_320px] lg:p-8">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-cyan-200">Centro de control</p>
                <h2 class="mt-2 text-4xl font-black tracking-tight">Dashboard operativo</h2>
                <p class="mt-3 max-w-2xl text-cyan-50">Ventas, comprobantes, ocupacion de numeros y actividad reciente en una vista preparada para administracion diaria.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/10 p-5">
                <p class="text-sm font-black text-cyan-100">Estado del sistema</p>
                <strong class="mt-2 block text-3xl font-black">En pruebas</strong>
                <p class="mt-2 text-sm text-cyan-100/80">Subdominio activo antes del dominio oficial.</p>
                <a class="mt-4 inline-flex rounded-xl bg-white px-4 py-3 text-sm font-black text-[#0f172a] transition hover:bg-cyan-50" href="{{ route('admin.raffles.create') }}">Crear sorteo</a>
            </div>
        </div>
    </section>


    @if (session('status'))
        <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 font-black text-cyan-900">{{ session('status') }}</div>
    @endif
    <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="metric-card"><span class="text-sm font-black text-slate-500">Ventas aprobadas</span><strong class="mt-2 block text-3xl font-black text-indigo-700" data-admin-stat="approved_revenue">₡{{ number_format($approvedRevenue, 0, ',', ' ') }}</strong></article>
        <article class="metric-card"><span class="text-sm font-black text-slate-500">Comprobantes pendientes</span><strong class="mt-2 block text-3xl font-black" data-admin-stat="pending_payments">{{ $pendingPayments }}</strong></article>
        <article class="metric-card"><span class="text-sm font-black text-slate-500">Rifas creadas</span><strong class="mt-2 block text-3xl font-black" data-admin-stat="raffles_count">{{ $raffles->count() }}</strong></article>
        <article class="metric-card"><span class="text-sm font-black text-slate-500">Numeros reservados</span><strong class="mt-2 block text-3xl font-black" data-admin-stat="reserved_numbers">{{ number_format($raffles->sum('reserved_numbers_count')) }}</strong></article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
        <article class="surface p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Inventario</p>
                    <h3 class="text-2xl font-black tracking-tight">Vendidos y reservados</h3>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-2 text-sm font-black text-slate-600">Actualiza solo cada 5s</span>
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
                            <div class="flex items-center gap-2"><span class="rounded-full px-2.5 py-1 text-xs font-black {{ $raffle->sale_enabled ? 'bg-cyan-100 text-cyan-700' : 'bg-amber-100 text-amber-800' }}">{{ $raffle->sale_enabled ? 'Activa' : 'Pausada' }}</span><span class="text-sm font-black text-indigo-700">{{ $progress }}%</span></div>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200"><div class="h-full rounded-full bg-cyan-600" style="width: {{ $progress }}%"></div></div>
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm text-slate-500">{{ number_format($raffle->sold_numbers_count) }} vendidos · {{ number_format($raffle->reserved_numbers_count) }} reservados</p>
                            <div class="flex flex-wrap gap-2">
                                <a class="rounded-lg bg-white px-3 py-2 text-xs font-black text-indigo-700 shadow-sm transition hover:bg-cyan-50" href="{{ route('admin.raffles.edit', $raffle) }}">Editar venta</a>                                <form method="post" action="{{ route('admin.raffles.toggle-sale', $raffle) }}">
                                    @csrf
                                    @method('patch')
                                    <button class="rounded-lg px-3 py-2 text-xs font-black shadow-sm transition {{ $raffle->sale_enabled ? 'bg-amber-50 text-amber-800 hover:bg-amber-100' : 'bg-cyan-50 text-cyan-700 hover:bg-cyan-100' }}" type="submit">
                                        {{ $raffle->sale_enabled ? 'Pausar venta' : 'Reactivar venta' }}
                                    </button>
                                </form>
                                <form method="post" action="{{ route('admin.raffles.destroy', $raffle) }}" onsubmit="return confirm('Eliminar este sorteo borrara sus numeros, ordenes y comprobantes asociados. ¿Deseas continuar?')">
                                    @csrf
                                    @method('delete')
                                    <button class="rounded-lg bg-red-50 px-3 py-2 text-xs font-black text-red-700 shadow-sm transition hover:bg-red-100" type="submit">Eliminar</button>
                                </form>
                            </div>
                        </div>
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
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-slate-100 px-3 py-2 text-sm font-black text-slate-600" data-admin-recent-count>{{ $recentOrders->total() }} movimiento(s)</span>
                <span class="rounded-full bg-cyan-50 px-3 py-2 text-sm font-black text-cyan-700" data-admin-updated-at>En vivo</span>
                <a class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-black text-slate-700 transition hover:bg-slate-200" href="{{ route('admin.payments.index') }}">Revisar pagos</a>
            </div>
        </div>
        <div class="mt-4 grid gap-3" data-admin-recent-list>
            @forelse ($recentOrders as $order)
                <article class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4">
                    <div><strong>{{ $order->buyer_name }}</strong><p class="text-sm text-slate-500">{{ $order->raffle->name }} · {{ $order->numbers->pluck('number')->join(', ') }}</p></div>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-black text-amber-700">{{ $order->status }}</span>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-slate-500">Aun no hay compras.</p>
            @endforelse
        </div>

        @if ($recentOrders->hasPages())
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                <p class="text-sm font-bold text-slate-500">
                    Mostrando {{ $recentOrders->firstItem() ?? 0 }}-{{ $recentOrders->lastItem() ?? 0 }} de {{ $recentOrders->total() }} movimiento(s)
                </p>
                <div class="flex items-center gap-2">
                    @if ($recentOrders->onFirstPage())
                        <span class="rounded-xl bg-white px-4 py-2 font-black text-slate-300 ring-1 ring-slate-200">Anterior</span>
                    @else
                        <a class="rounded-xl bg-white px-4 py-2 font-black text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-100" href="{{ $recentOrders->previousPageUrl() }}">Anterior</a>
                    @endif

                    <span class="rounded-xl bg-slate-950 px-4 py-2 font-black text-white">Pagina {{ $recentOrders->currentPage() }} de {{ $recentOrders->lastPage() }}</span>

                    @if ($recentOrders->hasMorePages())
                        <a class="rounded-xl bg-white px-4 py-2 font-black text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-100" href="{{ $recentOrders->nextPageUrl() }}">Siguiente</a>
                    @else
                        <span class="rounded-xl bg-white px-4 py-2 font-black text-slate-300 ring-1 ring-slate-200">Siguiente</span>
                    @endif
                </div>
            </div>
        @endif
    </section>
    </div>
</x-layouts.app>


