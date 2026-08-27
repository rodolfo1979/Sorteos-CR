<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .login-page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                radial-gradient(circle at top left, rgba(34, 211, 238, 0.16), transparent 34%),
                linear-gradient(135deg, #06111f, #0f172a 54%, #172554);
        }

        .login-card {
            width: min(100%, 420px);
            border: 1px solid rgb(226 232 240);
            border-radius: 18px;
            background: rgb(255 255 255 / 0.98);
            box-shadow: 0 28px 70px rgb(0 0 0 / 0.38);
            padding: 28px;
        }

        .login-mark {
            display: grid;
            height: 44px;
            width: 44px;
            place-items: center;
            border-radius: 14px;
            background: #fff;
            color: #0f172a;
            font-weight: 950;
            box-shadow: 0 10px 24px rgb(15 23 42 / 0.12);
        }

        .login-field {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #fff;
            padding: 12px 14px;
            font-weight: 700;
            outline: none;
        }

        .login-field:focus {
            border-color: #0891b2;
            box-shadow: 0 0 0 4px rgb(34 211 238 / 0.16);
        }

        .login-button {
            width: 100%;
            border-radius: 12px;
            background: linear-gradient(135deg, #fbbf24, #22d3ee);
            color: #06111f;
            font-weight: 950;
            padding: 13px 16px;
            transition: 160ms ease;
        }

        .login-button:hover { transform: translateY(-1px); }
    </style>
</head>
<body class="font-sans antialiased">
    <main class="login-page">
        <form class="login-card" method="post" action="{{ route('admin.login.store') }}">
            @csrf

            <div class="flex items-center gap-3">
                <div class="login-mark">CR</div>
                <div>
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.18em] text-cyan-700">Administracion</p>
                    <h1 class="text-2xl font-black tracking-tight text-slate-950">{{ $tenant?->name ?: 'Sorteos CR' }}</h1>
                </div>
            </div>

            <div class="mt-8">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-700">Acceso seguro</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950">Iniciar sesion</h2>
                <p class="mt-2 text-sm font-bold leading-6 text-slate-500">Gestion de sorteos, pagos, reportes y numeros.</p>
            </div>

            @if (session('status'))
                <div class="mt-5 rounded-xl border border-cyan-200 bg-cyan-50 p-3 text-sm font-bold text-cyan-900">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>
            @endif

            <div class="mt-6 grid gap-4">
                <label class="grid gap-1 text-sm font-black text-slate-600">Usuario
                    <input class="login-field" name="username" value="{{ old('username') }}" autocomplete="username" autofocus required>
                </label>

                <label class="grid gap-1 text-sm font-black text-slate-600">Clave
                    <input class="login-field" type="password" name="password" autocomplete="current-password" required>
                </label>
            </div>

            <button class="login-button mt-6" type="submit">Entrar</button>
        </form>
    </main>
</body>
</html>
