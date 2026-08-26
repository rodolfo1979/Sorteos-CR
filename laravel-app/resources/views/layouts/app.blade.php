<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Sorteos CR' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f6f7f4] font-sans text-slate-950 antialiased">
    @php $isAdmin = request()->is('admin*'); @endphp

    @if ($isAdmin)
        <div class="min-h-screen lg:grid lg:grid-cols-[270px_1fr]">
            <aside class="sticky top-0 z-30 h-auto border-b border-white/10 bg-[#063d32] px-4 py-4 text-white shadow-2xl shadow-emerald-950/10 lg:h-screen lg:border-b-0 lg:px-5 lg:py-6">
                <div class="flex items-center gap-3">
                    <div class="grid h-12 w-12 place-items-center rounded-xl bg-white text-lg font-black text-[#063d32] shadow-lg">CR</div>
                    <div>
                        <p class="text-[0.68rem] font-black uppercase tracking-[0.18em] text-emerald-200">Administracion</p>
                        <h1 class="text-2xl font-black tracking-tight">Sorteos CR</h1>
                    </div>
                </div>

                <nav class="mt-5 grid grid-cols-2 gap-2 text-sm font-black lg:mt-8 lg:grid-cols-1">
                    <a class="nav-link" href="{{ route('admin.dashboard') }}">Panel admin</a>
                    <a class="nav-link" href="{{ route('admin.raffles.create') }}">Crear sorteo</a>
                    <a class="nav-link" href="{{ route('admin.payments.index') }}">Pagos</a>
                    <a class="nav-link" href="{{ route('admin.reports.index') }}">Reportes</a>
                    <a class="nav-link" href="{{ route('admin.numbers.index') }}">Numeros</a>
                    <a class="nav-link" href="{{ route('admin.health.index') }}">Salud</a>
                    <a class="nav-link" href="{{ route('raffles.show') }}">Ver venta</a>
                </nav>

                <div class="mt-6 hidden rounded-xl border border-white/10 bg-white/10 p-4 text-sm text-emerald-50 lg:block">
                    <p class="font-black">Panel privado</p>
                    <p class="mt-1 text-emerald-100/80">Gestion de sorteos, pagos, reportes y numeros. El dominio principal queda limpio para los clientes.</p>
                </div>
            </aside>

            <main class="min-w-0 p-4 sm:p-6 xl:p-8">
                @if (session('status'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-800 shadow-sm">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 shadow-sm">
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
    @else
        <main class="min-h-screen min-w-0">
            @if (session('status'))
                <div class="mx-auto mb-4 max-w-7xl px-4 pt-4 sm:px-6 xl:px-8">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-800 shadow-sm">{{ session('status') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="mx-auto mb-4 max-w-7xl px-4 pt-4 sm:px-6 xl:px-8">
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 shadow-sm">
                        <strong class="font-black">Revisa la solicitud:</strong>
                        <ul class="mt-2 list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="mx-auto max-w-7xl p-4 sm:p-6 xl:p-8">
                {{ $slot }}
            </div>
        </main>
    @endif
</body>
</html>