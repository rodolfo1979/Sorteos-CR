<x-layouts.superadmin :title="$title">
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-indigo-700">Super admin</p>
                <h2 class="mt-2 text-4xl font-black tracking-tight">Tenants</h2>
                <p class="mt-2 max-w-2xl text-slate-600">Administra clientes activos, dominios principales y estado de cada tenant de la plataforma.</p>
            </div>
            <a class="primary-action" href="{{ route('superadmin.tenants.create') }}">Crear tenant</a>
        </div>

        <section class="grid gap-4 md:grid-cols-4">
            <article class="metric-card"><span class="text-sm font-black text-slate-500">Total</span><strong class="mt-2 block text-3xl font-black text-indigo-700">{{ $stats['total'] }}</strong></article>
            <article class="metric-card"><span class="text-sm font-black text-slate-500">Activos</span><strong class="mt-2 block text-3xl font-black text-emerald-700">{{ $stats['active'] }}</strong></article>
            <article class="metric-card"><span class="text-sm font-black text-slate-500">Suspendidos</span><strong class="mt-2 block text-3xl font-black text-amber-700">{{ $stats['suspended'] }}</strong></article>
            <article class="metric-card"><span class="text-sm font-black text-slate-500">Dominios</span><strong class="mt-2 block text-3xl font-black text-cyan-700">{{ $stats['domains'] }}</strong></article>
        </section>

        <section class="surface p-0">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-indigo-700">Clientes</p>
                    <h3 class="mt-1 text-2xl font-black tracking-tight">Tenants registrados</h3>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-2 text-sm font-black text-slate-600">CRUD plataforma</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Tenant</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3">Dominio principal</th>
                            <th class="px-5 py-3">Usuario admin</th>
                            <th class="px-5 py-3">Correo admin</th>
                            <th class="px-5 py-3 text-right">Dominios</th>
                            <th class="px-5 py-3 text-right">Datos</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($tenants as $tenant)
                            <tr class="bg-white align-top">
                                <td class="px-5 py-4">
                                    <strong class="block font-black text-slate-950">{{ $tenant->name }}</strong>
                                    <span class="mt-1 block font-bold text-slate-500">{{ $tenant->slug }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span @class([
                                        'rounded-full px-3 py-1 text-xs font-black uppercase',
                                        'bg-emerald-50 text-emerald-700' => $tenant->status === 'active',
                                        'bg-amber-50 text-amber-700' => $tenant->status !== 'active',
                                    ])>{{ $tenant->status }}</span>
                                </td>
                                <td class="px-5 py-4 font-bold text-slate-700">{{ $tenant->primary_domain ?: 'Sin dominio' }}</td>
                                <td class="px-5 py-4 font-bold text-slate-700">{{ $tenant->admin_username ?: 'Sin usuario' }}</td>
                                <td class="px-5 py-4 font-bold text-slate-700">{{ $tenant->admin_email ?: 'Sin correo' }}</td>
                                <td class="px-5 py-4 text-right font-black">{{ $tenant->domains_count }}</td>
                                <td class="px-5 py-4 text-right font-bold text-slate-600">{{ $tenant->raffles_count }} rifa(s) · {{ $tenant->orders_count }} orden(es)</td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-200" href="{{ route('superadmin.tenants.edit', $tenant) }}">Editar</a>
                                        <form method="post" action="{{ route('superadmin.tenants.destroy', $tenant) }}" onsubmit="return confirm('Eliminar este tenant solo es seguro si no tiene datos operativos. ¿Continuar?')">
                                            @csrf
                                            @method('delete')
                                            <button class="rounded-xl bg-red-50 px-3 py-2 text-xs font-black text-red-700 transition hover:bg-red-100" type="submit">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
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
