<x-layouts.app title="Comprar numeros - Sorteos CR" section="Sitio publico">
    @php
        $soldPercent = $raffle->total_numbers > 0 ? min(100, round(($raffle->sold_count / $raffle->total_numbers) * 100)) : 0;
    @endphp

    <section class="grid gap-6 2xl:grid-cols-[minmax(0,1fr)_460px]">
        <div class="min-w-0 space-y-6">
            <header class="overflow-hidden rounded-2xl bg-[#f72f3f] text-white shadow-2xl shadow-red-500/15">
                <div class="grid gap-6 p-6 lg:grid-cols-[1fr_280px] lg:p-8">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-red-100">Rifa activa</p>
                        <h2 class="mt-2 text-4xl font-black tracking-tight sm:text-5xl">{{ $raffle->name }}</h2>
                        <p class="mt-3 text-lg font-bold text-red-50">{{ $raffle->draw_date ? 'Sorteo: '.$raffle->draw_date->format('d/m/Y') : 'Fecha del sorteo por definir' }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/20 bg-white/12 p-5 backdrop-blur">
                        <div class="flex items-center justify-between text-sm font-black">
                            <span>Vendidos</span>
                            <span>{{ $soldPercent }}%</span>
                        </div>
                        <div class="mt-3 h-3 overflow-hidden rounded-full bg-white/25">
                            <div class="h-full rounded-full bg-white" style="width: {{ $soldPercent }}%"></div>
                        </div>
                        <p class="mt-3 text-sm text-red-50">{{ number_format($raffle->available_count) }} numeros disponibles</p>
                    </div>
                </div>
            </header>

            <article class="surface overflow-hidden">
                <div class="grid min-h-[360px] place-items-center bg-gradient-to-br from-slate-100 to-stone-200">
                    @if ($raffle->image_path)
                        <img class="h-full w-full object-cover" src="{{ Storage::url($raffle->image_path) }}" alt="Premio {{ $raffle->name }}">
                    @else
                        <div class="text-center">
                            <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-white shadow-sm">
                                <span class="text-2xl font-black text-slate-400">+</span>
                            </div>
                            <p class="mt-4 font-black text-slate-500">Fotografia profesional del premio</p>
                            <p class="mt-1 text-sm text-slate-400">Se cargara desde el panel administrativo</p>
                        </div>
                    @endif
                </div>
                <div class="grid gap-5 p-6 lg:grid-cols-[1fr_260px]">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Premio</p>
                        <h3 class="mt-1 text-3xl font-black tracking-tight">{{ $raffle->prize_title ?? 'Premio por definir' }}</h3>
                        <p class="mt-3 max-w-3xl whitespace-pre-line text-slate-600">{{ $raffle->prize_description }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-sm font-black text-slate-500">Precio por paquete</p>
                        <strong class="mt-1 block text-3xl font-black text-teal-700">₡{{ number_format($raffle->price_per_package, 0, ',', ' ') }}</strong>
                        <p class="mt-1 text-sm text-slate-500">Incluye {{ $raffle->numbers_per_package }} numero(s)</p>
                    </div>
                </div>
            </article>

            <section class="grid gap-4 md:grid-cols-3">
                <article class="surface p-5">
                    <span class="text-sm font-black text-slate-500">1. Escoge</span>
                    <h3 class="mt-1 text-xl font-black">Manual o al azar</h3>
                    <p class="mt-2 text-sm text-slate-600">Puedes seleccionar en la cuadricula o tomar paquetes automaticos.</p>
                </article>
                <article class="surface p-5">
                    <span class="text-sm font-black text-slate-500">2. Paga</span>
                    <h3 class="mt-1 text-xl font-black">Sube comprobante</h3>
                    <p class="mt-2 text-sm text-slate-600">La compra queda reservada mientras administracion valida.</p>
                </article>
                <article class="surface p-5">
                    <span class="text-sm font-black text-slate-500">3. Confirma</span>
                    <h3 class="mt-1 text-xl font-black">Correo y estado</h3>
                    <p class="mt-2 text-sm text-slate-600">Al aprobarse, los numeros pasan a vendidos.</p>
                </article>
            </section>

            <article class="surface p-6">
                <h3 class="text-2xl font-black tracking-tight">Como participar</h3>
                <p class="mt-4 whitespace-pre-line leading-7 text-slate-600">{{ $raffle->rules_text }}</p>
            </article>
        </div>

        <form class="surface sticky top-6 h-fit p-5 xl:p-6" method="post" action="{{ route('purchases.store', $raffle) }}" enctype="multipart/form-data" data-raffle-purchase data-random-url="{{ route('purchases.random', $raffle) }}" data-mode="{{ $raffle->assignment_mode }}">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Compra segura</p>
                    <h3 class="text-3xl font-black tracking-tight">Tus numeros</h3>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-2 text-sm font-black text-emerald-700">{{ $raffle->sale_enabled ? 'Venta activa' : 'Pausada' }}</span>
            </div>

            <div class="mt-5 grid gap-3">
                <label class="grid gap-1 text-sm font-black text-slate-600">Nombre completo<input class="field" name="buyer_name" value="{{ old('buyer_name') }}" autocomplete="name" required></label>
                <label class="grid gap-1 text-sm font-black text-slate-600">Telefono<input class="field" name="buyer_phone" value="{{ old('buyer_phone') }}" inputmode="tel" autocomplete="tel" required></label>
                <label class="grid gap-1 text-sm font-black text-slate-600">Correo<input class="field" type="email" name="buyer_email" value="{{ old('buyer_email') }}" autocomplete="email"></label>
            </div>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="font-black">Paquetes</p>
                    <span class="text-xs font-black uppercase text-slate-400">Hasta 5</span>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2" data-package-options>
                    @foreach ($packageOptions as $option)
                        <button class="rounded-xl border border-slate-200 bg-white px-3 py-4 text-center font-black leading-tight shadow-sm transition hover:border-red-400 hover:bg-red-50" type="button" data-package="{{ $option['packages'] }}" data-quantity="{{ $option['quantity'] }}" data-amount="{{ $option['amount'] }}">
                            {{ $raffle->assignment_mode === 'manual' ? 'Azar ' : '' }}{{ $option['quantity'] }}<br><span class="text-sm text-slate-500">numeros</span>
                        </button>
                    @endforeach
                </div>
                <input type="hidden" name="package_count" value="1" data-package-count>
                <p class="mt-3 text-sm leading-6 text-slate-600" data-package-help>Selecciona un paquete o escoge manualmente en la cuadricula.</p>
            </div>

            @if ($raffle->assignmentMode === 'manual')
            @endif

            @if ($raffle->assignment_mode === 'manual')
                <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="font-black">Cuadricula manual</p>
                        <span class="text-sm font-black text-slate-500">{{ number_format($raffle->available_count) }} disp.</span>
                    </div>
                    <div class="grid max-h-72 grid-cols-5 gap-2 overflow-auto pr-1 sm:grid-cols-6">
                        @foreach ($raffle->numbers()->where('status', 'available')->orderBy('number')->limit(180)->get() as $number)
                            <button type="button" class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-2 text-sm font-black transition hover:border-red-400 hover:bg-red-50" data-number-button="{{ $number->number }}">{{ $number->number }}</button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <p class="text-sm font-black text-slate-500">Seleccion actual</p>
                <div class="mt-2 flex min-h-12 flex-wrap gap-2 text-lg font-black" data-selected-list>Ninguno</div>
                <div data-hidden-numbers></div>
                <button class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-sm font-black text-red-700 transition hover:bg-red-100" type="button" data-clear-selection hidden>Eliminar seleccion</button>
                <p class="mt-3 text-2xl font-black text-teal-700" data-total>Total: ₡0</p>
            </div>

            <label class="mt-5 grid gap-1 text-sm font-black text-slate-600">Comprobante<input class="field" type="file" name="receipt" accept="image/*,.pdf" required></label>
            <button class="primary-action mt-5 w-full" type="submit">Enviar comprobante</button>
        </form>
    </section>
</x-layouts.app>

