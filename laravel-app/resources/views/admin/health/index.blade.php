<x-layouts.app title="Salud del sistema - Sorteos CR" section="Salud">
    @php
        $pendingJobs = is_numeric($queue['pending_jobs']) ? (int) $queue['pending_jobs'] : null;
        $failedJobs = is_numeric($queue['failed_jobs']) ? (int) $queue['failed_jobs'] : null;
        $queueState = ($failedJobs ?? 0) > 0 ? 'Atencion' : (($pendingJobs ?? 0) > 20 ? 'Cola alta' : 'Normal');
        $queueClass = $queueState === 'Normal' ? 'bg-emerald-50 text-emerald-700' : ($queueState === 'Cola alta' ? 'bg-amber-50 text-amber-800' : 'bg-red-50 text-red-700');
        $statusClasses = [
            'pending' => 'bg-amber-50 text-amber-800',
            'reserved' => 'bg-cyan-50 text-cyan-800',
            'approved' => 'bg-emerald-50 text-emerald-700',
            'rejected' => 'bg-red-50 text-red-700',
        ];
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

        <section class="grid gap-4 md:grid-cols-4">
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
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Multitenant</p>
                <strong class="mt-2 block text-2xl font-black {{ $tenancy['ok'] ? 'text-emerald-700' : 'text-red-700' }}">{{ $tenancy['ok'] ? 'Integro' : 'Revisar' }}</strong>
                <p class="mt-2 text-sm font-bold text-slate-500">{{ $tenancy['tenant_name'] }} · {{ $tenancy['tenant_domain'] }}</p>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-700">Integridad multitenant</p>
                    <h3 class="mt-1 text-xl font-black">Tenant principal y datos asignados</h3>
                </div>
                <span class="rounded-full {{ $tenancy['ok'] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }} px-3 py-2 text-sm font-black">{{ $tenancy['ok'] ? 'Sin pendientes' : 'Hay datos sin tenant' }}</span>
            </div>
            <dl class="mt-4 grid gap-3 text-sm md:grid-cols-3">
                <div class="rounded-xl bg-slate-50 p-3"><dt class="font-black text-slate-500">Tenants</dt><dd class="mt-1 text-xl font-black">{{ $tenancy['tenants_count'] }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-3"><dt class="font-black text-slate-500">Dominios</dt><dd class="mt-1 text-xl font-black">{{ $tenancy['domains_count'] }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-3"><dt class="font-black text-slate-500">Tenant actual</dt><dd class="mt-1 font-black">{{ $tenancy['tenant_name'] }}</dd></div>
            </dl>
            <div class="mt-4 grid gap-3 md:grid-cols-4">
                @forelse ($tenancy['unassigned'] as $table => $count)
                    <div class="rounded-xl {{ $count > 0 ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }} p-3">
                        <p class="text-xs font-black uppercase tracking-wide">{{ $table }}</p>
                        <strong class="mt-1 block text-2xl font-black">{{ $count }}</strong>
                    </div>
                @empty
                    <div class="rounded-xl bg-red-50 p-3 font-black text-red-700">No se pudo leer integridad multitenant.</div>
                @endforelse
            </div>
        </section>

        <section class="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-700">Compras</p>
                        <h3 class="mt-1 text-xl font-black">Ultimas compras</h3>
                    </div>
                    <a class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-black text-slate-700 transition hover:bg-slate-200" href="{{ route('admin.payments.index') }}">Ver pagos</a>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-xs font-black uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="py-2 pr-4">Hora CR</th>
                                <th class="py-2 pr-4">Comprador</th>
                                <th class="py-2 pr-4">Sorteo</th>
                                <th class="py-2 pr-4">Estado</th>
                                <th class="py-2 pr-4">Numeros</th>
                                <th class="py-2">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-bold">
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td class="py-3 pr-4 whitespace-nowrap text-slate-600">{{ $order['created_at'] }}</td>
                                    <td class="py-3 pr-4">
                                        <div class="font-black">{{ $order['buyer_name'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $order['buyer_email'] }}</div>
                                    </td>
                                    <td class="py-3 pr-4 text-slate-600">{{ $order['raffle'] }}</td>
                                    <td class="py-3 pr-4"><span class="rounded-full px-3 py-1 text-xs font-black {{ $statusClasses[$order['status']] ?? 'bg-slate-100 text-slate-700' }}">{{ $order['status_label'] }}</span></td>
                                    <td class="py-3 pr-4 text-slate-600">{{ $order['numbers'] === [] ? 'Sin numeros' : implode(', ', array_slice($order['numbers'], 0, 6)) }}{{ count($order['numbers']) > 6 ? ' +' . (count($order['numbers']) - 6) : '' }}</td>
                                    <td class="py-3">
                                        <div class="flex flex-wrap gap-2">
                                            <a class="rounded-lg bg-indigo-50 px-3 py-2 text-xs font-black text-indigo-700 transition hover:bg-indigo-100" href="{{ $order['detail_url'] }}">Detalle</a>
                                            @if ($order['whatsapp_url'])
                                                <a class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700 transition hover:bg-emerald-100" href="{{ $order['whatsapp_url'] }}" target="_blank" rel="noopener">WhatsApp</a>
                                            @endif
                                            <form method="post" action="{{ $order['resend_url'] }}">
                                                @csrf
                                                <input type="hidden" name="type" value="{{ $order['email_type'] }}">
                                                <button class="rounded-lg bg-amber-50 px-3 py-2 text-xs font-black text-amber-800 transition hover:bg-amber-100" type="submit">{{ $order['email_label'] }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="py-4 text-slate-500" colspan="6">Sin compras recientes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-700">Correo</p>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3"><dt class="font-black text-slate-500">Mailer</dt><dd class="font-black">{{ $mail['mailer'] }}</dd></div>
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3"><dt class="font-black text-slate-500">Host</dt><dd class="font-black">{{ $mail['host'] }}:{{ $mail['port'] }}</dd></div>
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3"><dt class="font-black text-slate-500">Remitente</dt><dd class="font-black">{{ $mail['from_address'] }}</dd></div>
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3"><dt class="font-black text-slate-500">Nombre</dt><dd class="font-black">{{ $mail['from_name'] }}</dd></div>
                </dl>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-700">Actividad administrativa</p>
                    <h3 class="mt-1 text-xl font-black">Eventos recientes de ordenes</h3>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-2 text-sm font-black text-slate-600">{{ count($recentActivity) }} evento(s)</span>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                @forelse ($recentActivity as $event)
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">{{ $event['action'] }}</span>
                            <span class="text-xs font-black text-slate-500">{{ $event['created_at'] }}</span>
                        </div>
                        <p class="mt-2 font-black text-slate-800">{{ $event['description'] }}</p>
                        <p class="mt-1 text-sm font-bold text-slate-500">{{ $event['buyer_name'] }} · {{ $event['raffle'] }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="text-xs font-black text-slate-500">{{ $event['actor'] }}</span>
                            @if ($event['detail_url'])
                                <a class="rounded-lg bg-white px-3 py-2 text-xs font-black text-indigo-700 ring-1 ring-slate-200 transition hover:bg-indigo-50" href="{{ $event['detail_url'] }}">Detalle</a>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl bg-slate-50 p-4 font-bold text-slate-500">Sin actividad administrativa registrada.</div>
                @endforelse
            </div>
        </section>
        <section class="grid gap-5 xl:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-700">Correos recientes</p>
                        <h3 class="mt-1 text-xl font-black">Eventos detectados</h3>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-2 text-sm font-black text-slate-600">{{ count($mailEvents) }} linea(s)</span>
                </div>
                <pre class="mt-4 max-h-96 overflow-auto rounded-2xl bg-slate-950 p-4 text-xs font-bold leading-6 text-slate-100">{{ $mailEvents === [] ? 'Sin eventos recientes de correo.' : implode("\n", $mailEvents) }}</pre>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-700">Errores recientes</p>
                        <h3 class="mt-1 text-xl font-black">production.ERROR</h3>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-2 text-sm font-black text-slate-600">{{ count($errors) }} linea(s)</span>
                </div>
                <pre class="mt-4 max-h-96 overflow-auto rounded-2xl bg-slate-950 p-4 text-xs font-bold leading-6 text-slate-100">{{ $errors === [] ? 'Sin errores recientes.' : implode("\n", $errors) }}</pre>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-indigo-700">Scheduler</p>
            <pre class="mt-4 max-h-80 overflow-auto rounded-2xl bg-slate-950 p-4 text-xs font-bold leading-6 text-slate-100">{{ $schedule['output'] ?: 'Sin salida disponible.' }}</pre>
        </section>
    </div>
</x-layouts.app>