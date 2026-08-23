<x-layouts.app title="Comprar numeros - Sorteos CR" section="Sitio publico">
    @php
        $soldPercent = $raffle->total_numbers > 0 ? min(100, round(($raffle->sold_count / $raffle->total_numbers) * 100)) : 0;
        $mediaItems = collect($raffle->media_paths ?? []);
    @endphp

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_410px]">
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


            @if ($mediaItems->isNotEmpty())
                <section class="surface p-5 sm:p-6">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.22em] text-teal-700">Galeria</p>
                            <h3 class="mt-1 text-2xl font-black tracking-tight">Fotos y videos del premio</h3>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-black uppercase text-slate-500">{{ $mediaItems->count() }} archivo(s)</span>
                    </div>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($mediaItems as $mediaPath)
                            @php $isVideo = in_array(strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION)), ['mp4', 'mov', 'webm'], true); @endphp
                            <figure class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm">
                                @if ($isVideo)
                                    <video class="aspect-video w-full bg-black object-cover" src="{{ Storage::url($mediaPath) }}" controls preload="metadata"></video>
                                @else
                                    <img class="aspect-video w-full object-cover" src="{{ Storage::url($mediaPath) }}" alt="Galeria {{ $raffle->name }}">
                                @endif
                            </figure>
                        @endforeach
                    </div>
                </section>
            @endif
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

            @if ($raffle->public_sales_text)
                <article class="overflow-hidden rounded-2xl border border-red-900/35 bg-[#090303] text-white shadow-2xl shadow-red-950/10">
                    <div class="border-b border-red-900/40 bg-gradient-to-r from-red-950 to-[#090303] p-5 sm:p-6">
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-red-300">Evento relacionado</p>
                        <h3 class="mt-2 text-3xl font-black tracking-tight">{{ $raffle->prize_title ?? $raffle->name }}</h3>
                    </div>
                    <div class="p-5 sm:p-6">
                        <p class="whitespace-pre-line text-lg leading-9 text-slate-300">{{ $raffle->public_sales_text }}</p>
                    </div>
                    <div class="grid gap-3 border-t border-red-900/40 p-5 sm:grid-cols-3 sm:p-6">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Disponibles</p>
                            <strong class="mt-1 block text-3xl font-black">{{ number_format($raffle->available_count) }}</strong>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Precio</p>
                            <strong class="mt-1 block text-3xl font-black text-red-400">₡{{ number_format($raffle->price_per_package, 0, ',', ' ') }}</strong>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Sorteo</p>
                            <strong class="mt-1 block text-xl font-black">{{ $raffle->draw_date ? $raffle->draw_date->format('d/m/Y') : 'Por definir' }}</strong>
                        </div>
                    </div>
                </article>
            @endif
            <article class="surface p-6">
                <h3 class="text-2xl font-black tracking-tight">Como participar</h3>
                <p class="mt-4 whitespace-pre-line leading-7 text-slate-600">{{ $raffle->rules_text }}</p>
            </article>
        </div>

        @if ($raffle->sale_enabled)
        <form class="surface sticky top-4 h-fit p-4 xl:p-5" method="post" action="{{ route('purchases.store', $raffle) }}" enctype="multipart/form-data" data-raffle-purchase data-random-url="{{ route('purchases.random', $raffle) }}" data-mode="{{ $raffle->assignment_mode }}" data-max-random-changes="{{ $raffle->max_random_changes }}" data-numbers-url="{{ route('raffles.numbers', $raffle) }}">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Compra segura</p>
                    <h3 class="text-2xl font-black tracking-tight">Tus numeros</h3>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-2 text-sm font-black text-emerald-700">{{ $raffle->sale_enabled ? 'Venta activa' : 'Pausada' }}</span>
            </div>

            <div class="mt-4 grid gap-3">
                <label class="grid gap-1 text-sm font-black text-slate-600">Nombre completo<input class="field" name="buyer_name" value="{{ old('buyer_name') }}" autocomplete="name" required></label>
                <label class="grid gap-1 text-sm font-black text-slate-600">Telefono<input class="field" name="buyer_phone" value="{{ old('buyer_phone') }}" inputmode="tel" autocomplete="tel" required></label>
                <label class="grid gap-1 text-sm font-black text-slate-600">Correo<input class="field" type="email" name="buyer_email" value="{{ old('buyer_email') }}" autocomplete="email"></label>
            </div>

            <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
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
            @if ($raffle->assignment_mode === 'manual')
                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-black">Cuadricula manual</p>
                            <p class="text-xs font-black uppercase tracking-wide text-slate-400" data-number-range>Cargando numeros...</p>
                        </div>
                        <span class="text-sm font-black text-slate-500">{{ number_format($raffle->available_count) }} disp.</span>
                    </div>
                    <div class="flex items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-2">
                        <button class="grid h-10 w-10 place-items-center rounded-xl bg-white text-xl font-black text-slate-800 shadow-sm transition hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-35" type="button" data-number-page-prev aria-label="Pagina anterior">‹</button>
                        <strong class="text-sm font-black text-slate-600" data-number-page-label>Pagina 1</strong>
                        <button class="grid h-10 w-10 place-items-center rounded-xl bg-white text-xl font-black text-slate-800 shadow-sm transition hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-35" type="button" data-number-page-next aria-label="Pagina siguiente">›</button>
                    </div>
                    <div class="mt-3 grid max-h-[22rem] grid-cols-4 gap-1.5 overflow-auto pr-1 min-[420px]:grid-cols-5 sm:grid-cols-5 xl:grid-cols-5" data-number-grid>
                        <p class="col-span-full rounded-xl border border-dashed border-slate-300 p-4 text-center text-sm font-black text-slate-400">Cargando numeros...</p>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-3 text-xs font-bold text-slate-500">
                        <span><span class="inline-block h-3 w-3 rounded bg-amber-400 align-middle"></span> Disponible</span>
                        <span><span class="inline-block h-3 w-3 rounded bg-slate-200 align-middle"></span> No disponible</span>
                    </div>
                </div>
            @endif

            <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <p class="text-sm font-black text-slate-500">Seleccion actual</p>
                <div class="mt-3 grid min-h-10 grid-cols-2 gap-2" data-selected-list>Ninguno</div>
                <div data-hidden-numbers></div>
                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    <button class="rounded-xl bg-amber-400 px-3 py-3 text-sm font-black text-slate-950 shadow-sm transition hover:bg-amber-300" type="button" data-reroll-selection hidden>Cambiar numeros (5 restantes)</button>
                    <button class="rounded-xl bg-red-50 px-3 py-3 text-sm font-black text-red-700 transition hover:bg-red-100" type="button" data-clear-selection hidden>Eliminar seleccion</button>
                </div>
                <p class="mt-3 text-xl font-black text-teal-700" data-total>Total: ₡0</p>
            </div>

            <label class="mt-5 grid gap-1 text-sm font-black text-slate-600">Comprobante<input class="field" type="file" name="receipt" accept="image/*,.pdf" required></label>
            <button class="primary-action mt-5 w-full" type="submit">Enviar comprobante</button>
        </form>
        @else
            <aside class="surface sticky top-4 h-fit border-amber-200 bg-amber-50 p-5">
                <p class="text-xs font-black uppercase tracking-wide text-amber-700">Venta pausada</p>
                <h3 class="mt-2 text-2xl font-black tracking-tight text-slate-950">Este sorteo esta pausado temporalmente</h3>
                <p class="mt-3 leading-7 text-slate-700">Por el momento no se pueden seleccionar numeros ni enviar comprobantes. La informacion del sorteo sigue visible y la venta puede reactivarse desde administracion.</p>
                <div class="mt-5 rounded-2xl border border-amber-200 bg-white p-4">
                    <p class="text-sm font-black text-slate-600">Estado actual</p>
                    <strong class="mt-1 block text-xl font-black text-amber-700">Pausada por administracion</strong>
                </div>
            </aside>
        @endif    </section>
</x-layouts.app>



