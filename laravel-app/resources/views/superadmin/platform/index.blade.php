<x-layouts.superadmin :title="$title">
    <div class="space-y-6">
        <section class="overflow-hidden rounded-2xl bg-[linear-gradient(135deg,#0f172a,#1e1b4b_58%,#083344)] p-6 text-white shadow-2xl shadow-slate-950/20">
            <div class="grid gap-6 lg:grid-cols-[1fr_320px] lg:items-end">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-cyan-200">Super admin</p>
                    <h2 class="mt-2 text-4xl font-black tracking-tight">Plataforma</h2>
                    <p class="mt-3 max-w-2xl text-sm font-bold text-cyan-100/80">Control central de tenants, dominios y salud de datos para crecer sin mezclar informacion entre clientes.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                    <p class="text-xs font-black uppercase tracking-wide text-cyan-100">Integridad multitenant</p>
                    <strong class="mt-2 block text-3xl font-black">{{ $stats['unassigned_records'] }}</strong>
                    <p class="mt-1 text-sm font-bold text-cyan-100/80">Registros operativos sin tenant asignado.</p>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-4">
            <article class="metric-card"><span class="text-sm font-black text-slate-500">Tenants</span><strong class="mt-2 block text-3xl font-black text-indigo-700">{{ $stats['tenants'] }}</strong></article>
            <article class="metric-card"><span class="text-sm font-black text-slate-500">Activos</span><strong class="mt-2 block text-3xl font-black text-emerald-700">{{ $stats['active_tenants'] }}</strong></article>
            <article class="metric-card"><span class="text-sm font-black text-slate-500">Dominios</span><strong class="mt-2 block text-3xl font-black text-cyan-700">{{ $stats['domains'] }}</strong></article>
            <article class="metric-card"><span class="text-sm font-black text-slate-500">Sin tenant</span><strong class="mt-2 block text-3xl font-black text-amber-700">{{ $stats['unassigned_records'] }}</strong></article>
        </section>

        <section class="surface p-0">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-indigo-700">Operacion</p>
                    <h3 class="mt-1 text-2xl font-black tracking-tight">Tenants configurados</h3>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-2 text-sm font-black text-slate-600">Solo lectura</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Tenant</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3">Dominio principal</th>
                            <th class="px-5 py-3 text-right">Rifas</th>
                            <th class="px-5 py-3 text-right">Ordenes</th>
                            <th class="px-5 py-3 text-right">Pendientes</th>
                            <th class="px-5 py-3 text-right">Aprobado</th>
                            <th class="px-5 py-3">Ultima orden</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($tenants as $summary)
                            @php($tenant = $summary['tenant'])
                            <tr class="bg-white align-top">
                                <td class="px-5 py-4">
                                    <strong class="block font-black text-slate-950">{{ $tenant->name }}</strong>
                                    <span class="mt-1 block font-bold text-slate-500">{{ $tenant->slug }} · {{ $summary['domains_count'] }} dominio(s)</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span @class([
                                        'rounded-full px-3 py-1 text-xs font-black uppercase',
                                        'bg-emerald-50 text-emerald-700' => $tenant->status === 'active',
                                        'bg-amber-50 text-amber-700' => $tenant->status !== 'active',
                                    ])>{{ $tenant->status }}</span>
                                </td>
                                <td class="px-5 py-4 font-bold text-slate-700">{{ $tenant->primary_domain ?: 'Sin dominio' }}</td>
                                <td class="px-5 py-4 text-right font-black">{{ $summary['raffles_count'] }}</td>
                                <td class="px-5 py-4 text-right font-black">{{ $summary['orders_count'] }}</td>
                                <td class="px-5 py-4 text-right font-black text-amber-700">{{ $summary['pending_orders'] }}</td>
                                <td class="px-5 py-4 text-right font-black text-emerald-700">{{ number_format($summary['approved_revenue'], 0, ',', ' ') }}</td>
                                <td class="px-5 py-4 font-bold text-slate-700">{{ $summary['last_order_at'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-8 text-center font-bold text-slate-500">No hay tenants configurados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.superadmin>
