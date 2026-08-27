<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Super admin - Sorteos CR' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .superadmin-shell { min-height: 100vh; }
        .superadmin-sidebar { position: sticky; top: 0; z-index: 30; }
        .superadmin-main { min-width: 0; }

        @media (min-width: 1024px) {
            .superadmin-shell { display: flex; align-items: stretch; }
            .superadmin-sidebar { flex: 0 0 280px; width: 280px; min-height: 100vh; }
            .superadmin-main { flex: 1 1 auto; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-950 antialiased">
    <div class="superadmin-shell">
        <aside class="superadmin-sidebar h-auto border-b border-white/10 bg-[linear-gradient(180deg,#020617,#111827_52%,#312e81)] px-4 py-4 text-white shadow-2xl shadow-slate-950/30 lg:border-b-0 lg:px-5 lg:py-6">
            <div class="flex items-center gap-3">
                <div class="grid h-12 w-12 place-items-center rounded-xl bg-cyan-100 text-lg font-black text-slate-950 shadow-lg">SA</div>
                <div>
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.18em] text-cyan-200">Super admin</p>
                    <h1 class="text-2xl font-black tracking-tight">Super Admin</h1>
                </div>
            </div>

            <nav class="mt-5 grid grid-cols-2 gap-2 text-sm font-black lg:mt-8 lg:grid-cols-1">
                <a class="nav-link" href="{{ route('superadmin.tenants.index') }}">Tenants</a>
            </nav>

            <div class="mt-6 hidden rounded-xl border border-white/10 bg-white/10 p-4 text-sm text-cyan-50 lg:block">
                <p class="font-black">Capa plataforma</p>
                <p class="mt-1 text-cyan-100/80">Separada del admin operativo para controlar tenants, dominios e integridad global.</p>
            </div>
        </aside>

        <main class="superadmin-main bg-[radial-gradient(circle_at_top_right,rgba(34,211,238,0.12),transparent_28%),linear-gradient(180deg,#f8fafc,#eef3f8)] p-4 sm:p-6 xl:p-8">
            @if (session('status'))
                <div class="mb-4 rounded-xl border border-cyan-200 bg-cyan-50 p-4 font-bold text-cyan-900 shadow-sm">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900 shadow-sm">
                    <strong class="font-black">Revisa la solicitud:</strong>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>
