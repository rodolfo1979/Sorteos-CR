<x-layouts.superadmin :title="$title">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.22em] text-indigo-700">Super admin</p>
            <h2 class="mt-2 text-4xl font-black tracking-tight">{{ $tenant->exists ? 'Editar tenant' : 'Crear tenant' }}</h2>
            <p class="mt-2 max-w-2xl text-slate-600">Define identidad, dominio principal y estado del tenant dentro de la plataforma.</p>
        </div>
        <a class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-black text-white transition hover:bg-slate-700" href="{{ route('superadmin.tenants.index') }}">Volver</a>
    </div>

    <form class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]" method="post" action="{{ $action }}">
        @csrf
        @if ($method !== 'post')
            @method($method)
        @endif

        <section class="space-y-6">
            <article class="surface p-5">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Identidad</p>
                <h3 class="mt-1 text-2xl font-black tracking-tight">Datos del tenant</h3>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="grid gap-1 text-sm font-black text-slate-600 md:col-span-2">Nombre
                        <input class="field" name="name" value="{{ old('name', $tenant->name) }}" placeholder="Sorteos CR" required>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Slug
                        <input class="field" name="slug" value="{{ old('slug', $tenant->slug) }}" placeholder="sorteos-cr">
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Estado
                        <select class="field" name="status" required>
                            <option value="active" @selected(old('status', $tenant->status) === 'active')>Activo</option>
                            <option value="suspended" @selected(old('status', $tenant->status) === 'suspended')>Suspendido</option>
                        </select>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600 md:col-span-2">Dominio principal
                        <input class="field" name="primary_domain" value="{{ old('primary_domain', $tenant->primary_domain) }}" placeholder="cliente.com">
                    </label>
                </div>
            </article>

            <article class="surface p-5">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Contacto</p>
                <h3 class="mt-1 text-2xl font-black tracking-tight">Correos del tenant</h3>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="grid gap-1 text-sm font-black text-slate-600">Correo admin
                        <input class="field" type="email" name="admin_email" value="{{ old('admin_email', $tenant->admin_email) }}" placeholder="admin@cliente.com">
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Correo notificaciones
                        <input class="field" type="email" name="notification_email" value="{{ old('notification_email', $tenant->notification_email) }}" placeholder="avisos@cliente.com">
                    </label>
                </div>
            </article>
        </section>

        <aside class="space-y-6">
            <article class="surface p-5">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Localizacion</p>
                <div class="mt-5 grid gap-4">
                    <label class="grid gap-1 text-sm font-black text-slate-600">Zona horaria
                        <input class="field" name="timezone" value="{{ old('timezone', $tenant->timezone ?: 'America/Costa_Rica') }}" required>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Moneda
                        <input class="field" name="currency" value="{{ old('currency', $tenant->currency ?: 'CRC') }}" maxlength="8" required>
                    </label>
                </div>
            </article>

            <article class="surface p-5">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Marca</p>
                <div class="mt-5 grid gap-4">
                    <label class="grid gap-1 text-sm font-black text-slate-600">Color primario
                        <input class="field" name="primary_color" value="{{ old('primary_color', $tenant->primary_color) }}" placeholder="#0f172a">
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Color acento
                        <input class="field" name="accent_color" value="{{ old('accent_color', $tenant->accent_color) }}" placeholder="#0891b2">
                    </label>
                </div>
            </article>

            <button class="primary-action w-full" type="submit">{{ $tenant->exists ? 'Guardar tenant' : 'Crear tenant' }}</button>
        </aside>
    </form>
</x-layouts.superadmin>
