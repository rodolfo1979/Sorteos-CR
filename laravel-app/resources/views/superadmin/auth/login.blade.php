<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-950 antialiased">
    <main class="grid min-h-screen place-items-center bg-[radial-gradient(circle_at_top_left,rgba(34,211,238,0.18),transparent_32%),linear-gradient(135deg,#020617,#111827_54%,#312e81)] p-4">
        <section class="grid w-full max-w-5xl overflow-hidden rounded-2xl border border-white/10 bg-white shadow-2xl shadow-slate-950/40 lg:grid-cols-[1fr_420px]">
            <div class="hidden bg-[linear-gradient(145deg,#020617,#0f172a_55%,#164e63)] p-8 text-white lg:block">
                <div class="flex items-center gap-3">
                    <div class="grid h-12 w-12 place-items-center rounded-xl bg-cyan-100 text-lg font-black text-slate-950">SA</div>
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-cyan-200">Sorteos CR</p>
                        <h1 class="text-3xl font-black tracking-tight">Super Admin</h1>
                    </div>
                </div>
                <div class="mt-24 max-w-md">
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-cyan-200">Plataforma</p>
                    <h2 class="mt-3 text-4xl font-black tracking-tight">Control de tenants separado del admin operativo.</h2>
                    <p class="mt-4 text-sm font-bold leading-6 text-cyan-100/80">Acceso reservado para administrar clientes, dominios y configuracion global.</p>
                </div>
            </div>

            <form class="grid gap-5 p-6 sm:p-8" method="post" action="{{ route('superadmin.login.store') }}">
                @csrf
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-indigo-700">Acceso seguro</p>
                    <h2 class="mt-2 text-3xl font-black tracking-tight">Entrar al super admin</h2>
                    <p class="mt-2 text-sm font-bold text-slate-500">Usa las credenciales configuradas en el servidor.</p>
                </div>

                @if (session('status'))
                    <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-4 text-sm font-bold text-cyan-900">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">{{ $errors->first() }}</div>
                @endif

                <label class="grid gap-1 text-sm font-black text-slate-600">Usuario
                    <input class="field" name="username" value="{{ old('username') }}" autocomplete="username" autofocus required>
                </label>

                <label class="grid gap-1 text-sm font-black text-slate-600">Clave
                    <input class="field" type="password" name="password" autocomplete="current-password" required>
                </label>

                <button class="primary-action w-full" type="submit">Entrar</button>
            </form>
        </section>
    </main>
</body>
</html>
