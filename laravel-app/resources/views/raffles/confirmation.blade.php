<x-layouts.app title="Confirmacion - Sorteos CR" section="Compra recibida">
    <section class="mx-auto max-w-4xl space-y-5 pb-20">
        <article class="overflow-hidden rounded-[1.5rem] border border-emerald-200 bg-white shadow-2xl shadow-emerald-950/10">
            <div class="bg-gradient-to-br from-[#063d32] to-[#0f766e] p-6 text-white sm:p-8">
                <p class="text-xs font-black uppercase tracking-[0.22em] text-amber-200">Orden {{ strtoupper(substr($order->public_uuid, 0, 8)) }}</p>
                <h1 class="mt-2 text-4xl font-black tracking-tight">Numeros reservados</h1>
                <p class="mt-3 max-w-2xl text-lg font-semibold leading-7 text-emerald-50">Tu comprobante queda pendiente de validacion por administracion.</p>
            </div>

            <div class="grid gap-5 p-5 sm:p-6">
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                    <p class="text-sm font-black uppercase tracking-wide text-[#063d32]">Tus tickets digitales</p>
                    <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($order->numbers as $number)
                            <span class="relative flex min-h-14 items-center justify-center overflow-hidden rounded-xl bg-teal-700 px-3 py-2 text-xl font-black tracking-wide text-white shadow-lg shadow-emerald-900/10 ring-1 ring-amber-400/45">
                                <span class="absolute -left-2 top-1/2 h-4 w-4 -translate-y-1/2 rounded-full bg-emerald-50"></span>
                                <span class="absolute -right-2 top-1/2 h-4 w-4 -translate-y-1/2 rounded-full bg-emerald-50"></span>
                                <svg class="mr-2 h-5 w-5 text-amber-200/80" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h11A2.5 2.5 0 0 1 20 8.5v2a2 2 0 0 0 0 3v2A2.5 2.5 0 0 1 17.5 18h-11A2.5 2.5 0 0 1 4 15.5v-2a2 2 0 0 0 0-3v-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                    <path d="M9 8v8M15 8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="1 3"/>
                                </svg>
                                {{ $number->number }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <article class="rounded-2xl border border-slate-200 bg-white p-4"><span class="text-sm font-bold text-slate-500">Monto</span><strong class="mt-1 block text-2xl font-black">₡{{ number_format($order->amount_total, 0, ',', ' ') }}</strong></article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-4"><span class="text-sm font-bold text-slate-500">Estado</span><strong class="mt-1 block text-2xl font-black text-amber-700">{{ $order->status === 'pending' ? 'Pendiente' : ucfirst($order->status) }}</strong></article>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <h2 class="text-xl font-black tracking-tight">Que pasa ahora</h2>
                    <p class="mt-3 leading-7 text-slate-700">Si ingresaste correo electronico, te enviamos un correo con tus tickets digitales separados. Cuando administracion valide el comprobante, recibiras otro correo confirmando que el pago fue aprobado y tu compra quedo correcta.</p>
                    <p class="mt-3 text-sm font-bold text-slate-500">Conserva esta pantalla o el correo hasta que el pago sea validado.</p>
                </div>
            </div>
        </article>
    </section>
</x-layouts.app>

