<x-layouts.app title="Salud del sistema - Sorteos CR" section="Salud">
    @php
        $pendingJobs = is_numeric($queue['pending_jobs']) ? (int) $queue['pending_jobs'] : null;
        $failedJobs = is_numeric($queue['failed_jobs']) ? (int) $queue['failed_jobs'] : null;
        $queueState = ($failedJobs ?? 0) > 0 ? 'Atencion' : (($pendingJobs ?? 0) > 20 ? 'Cola alta' : 'Normal');
        $queueClass = $queueState === 'Normal' ? 'bg-emerald-50 text-emerald-700' : ($queueState === 'Cola alta' ? 'bg-amber-50 text-amber-800' : 'bg-red-50 text-red-700');
    @endphp

    <div class="space-y-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-700">Monitor</p>
                <h2 class="mt-1 text-3xl font-black tracking-tight">Salud del sistema</h2>
                <p class="mt-1 text-sm font-bold text-slate-500">Hora Costa Rica: {{ now('America/Costa_Rica')->format('d/m/Y H:i:s') }}</p>
            </div>
            <span class="rounded-full px-4 py-2 text-sm font-black {{ $queueClass }}">{{ $queueState }}</span>
        </div>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Cola pendiente</p>
                <strong class="mt-2 block text-4xl font-black">{{ $queue['pending_jobs'] }}</strong>
                <p class="mt-2 text-sm font-bold text-slate-500">Conexion: {{ $queue['connection'] }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Jobs fallidos</p>
                <strong class="mt-2 block text-4xl font-black {{ ($failedJobs ?? 0) > 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $queue['failed_jobs'] }}</strong>
                <p class="mt-2 text-sm font-bold text-slate-500">Revisar si sube de cero.</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">SMTP</p>
                <strong class="mt-2 block text-2xl font-black">{{ $mail['mailer'] }} · {{ $mail['host'] }}</strong>
                <p class="mt-2 text-sm font-bold text-slate-500">Usuario {{ $mail['username'] }} · clave {{ $mail['password'] }}</p>
            </article>
        </section>

        <section class="grid gap-5 lg:grid-cols-[0.9fr_1.1fr]">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-700">Correo</p>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3"><dt class="font-black text-slate-500">Mailer</dt><dd class="font-black">{{ $mail['mailer'] }}</dd></div>
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3"><dt class="font-black text-slate-500">Host</dt><dd class="font-black">{{ $mail['host'] }}:{{ $mail['port'] }}</dd></div>
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3"><dt class="font-black text-slate-500">Remitente</dt><dd class="font-black">{{ $mail['from_address'] }}</dd></div>
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3"><dt class="font-black text-slate-500">Nombre</dt><dd class="font-black">{{ $mail['from_name'] }}</dd></div>
                </dl>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-700">Scheduler</p>
                <pre class="mt-4 max-h-80 overflow-auto rounded-2xl bg-slate-950 p-4 text-xs font-bold leading-6 text-slate-100">{{ $schedule['output'] ?: 'Sin salida disponible.' }}</pre>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-700">Logs recientes</p>
                    <h3 class="mt-1 text-xl font-black">Errores y correos</h3>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-2 text-sm font-black text-slate-600">{{ count($errors) }} linea(s)</span>
            </div>
            <pre class="mt-4 max-h-96 overflow-auto rounded-2xl bg-slate-950 p-4 text-xs font-bold leading-6 text-slate-100">{{ $errors === [] ? 'Sin eventos recientes relevantes.' : implode("\n", $errors) }}</pre>
        </section>
    </div>
</x-layouts.app>