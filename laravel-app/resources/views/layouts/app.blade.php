<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Sorteos CR' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 text-slate-900 antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[250px_1fr]">
        <aside class="bg-emerald-950 p-6 text-white">
            <p class="text-xs font-black uppercase tracking-wide text-emerald-200">{{ $section ?? 'Sistema' }}</p>
            <h1 class="mt-1 text-3xl font-black">Sorteos CR</h1>
            <nav class="mt-8 grid gap-2 text-sm font-bold">
                <a class="rounded-lg border border-white/15 px-4 py-3 hover:bg-white/10" href="{{ route('raffles.show') }}">Venta</a>
                <a class="rounded-lg border border-white/15 px-4 py-3 hover:bg-white/10" href="{{ route('admin.dashboard') }}">Admin</a>
                <a class="rounded-lg border border-white/15 px-4 py-3 hover:bg-white/10" href="{{ route('admin.payments.index') }}">Pagos</a>
                <a class="rounded-lg border border-white/15 px-4 py-3 hover:bg-white/10" href="{{ route('admin.reports.index') }}">Reportes</a>
                <a class="rounded-lg border border-white/15 px-4 py-3 hover:bg-white/10" href="{{ route('admin.numbers.index') }}">Numeros</a>
            </nav>
        </aside>

        <main class="p-4 sm:p-6 lg:p-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-800">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                    <strong class="font-black">Revisa la compra:</strong>
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
