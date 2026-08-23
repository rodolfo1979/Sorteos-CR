<x-layouts.app title="Comprar numeros - Sorteos CR" section="Sitio publico">
    @php
        $occupiedCount = ($raffle->sold_count ?? 0) + ($raffle->reserved_count ?? 0);
        $soldPercent = $raffle->total_numbers > 0 ? min(100, round(($occupiedCount / $raffle->total_numbers) * 100, 1)) : 0;
        $mediaItems = collect($raffle->media_paths ?? []);
        $heroImage = $raffle->image_path ? Storage::url($raffle->image_path) : null;
    @endphp

    <section class="mx-auto max-w-7xl space-y-5 pb-24 sm:space-y-6 xl:pb-8">
        <header class="relative overflow-hidden rounded-[1.5rem] bg-[#06110f] text-white shadow-2xl shadow-teal-950/25 sm:rounded-[2rem]">
            <div class="absolute inset-0">
                @if ($heroImage)
                    <img class="h-full w-full object-cover opacity-55" src="{{ $heroImage }}" alt="Premio {{ $raffle->name }}">
                @else
                    <div class="h-full w-full bg-[radial-gradient(circle_at_top_left,#13c296,transparent_34%),linear-gradient(135deg,#071914,#12231f_45%,#0b0b0b)]"></div>
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/58 to-black/18"></div>
            </div>

            <div class="relative grid min-h-[420px] content-end gap-5 p-5 sm:min-h-[500px] sm:p-7 lg:grid-cols-[1fr_340px] lg:items-end lg:p-8">
                <div class="max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-amber-300 px-3 py-1.5 text-xs font-black uppercase tracking-wide text-[#063d32]">{{ $raffle->sale_enabled ? 'Venta activa' : 'Venta pausada' }}</span>
                        <span class="rounded-full bg-white/12 px-3 py-1.5 text-xs font-black uppercase tracking-wide text-white ring-1 ring-white/15">{{ number_format($raffle->available_count) }} disponibles</span>
                    </div>
                    <p class="mt-5 text-xs font-black uppercase tracking-[0.24em] text-amber-200">Sorteos CR</p>
                    <h1 class="mt-2 text-4xl font-black leading-none tracking-tight sm:text-6xl lg:text-7xl">{{ $raffle->name }}</h1>
                    <p class="mt-4 max-w-2xl text-lg font-bold leading-7 text-white/86 sm:text-xl">{{ $raffle->prize_title ?? 'Premio principal' }}</p>
                    <p class="mt-2 text-base font-black text-amber-200">{{ $raffle->draw_date ? 'Sorteo: '.$raffle->draw_date->format('d/m/Y') : 'Fecha por definir' }}</p>
                </div>

                <div class="rounded-2xl border border-white/15 bg-white/12 p-4 backdrop-blur-md sm:p-5">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-white/60">Precio</p>
                            <strong class="mt-1 block text-3xl font-black text-amber-300">₡{{ number_format($raffle->price_per_package, 0, ',', ' ') }}</strong>
                            <p class="mt-1 text-sm font-bold text-white/70">{{ $raffle->numbers_per_package }} numero(s) por paquete</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-black uppercase tracking-wide text-white/60">Ocupado</p>
                            <strong class="text-2xl font-black">{{ $soldPercent }}%</strong>
                        </div>
                    </div>
                    <div class="mt-4 h-3 overflow-hidden rounded-full bg-white/18">
                        <div class="h-full rounded-full bg-gradient-to-r from-amber-300 to-teal-300" style="width: {{ $soldPercent }}%"></div>
                    </div>
                </div>
            </div>
        </header>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px] xl:items-start">
            @if ($raffle->sale_enabled)
                <form class="order-1 overflow-hidden rounded-[1.35rem] border border-teal-200 bg-white shadow-2xl shadow-teal-950/10 xl:sticky xl:top-4 xl:order-2" method="post" action="{{ route('purchases.store', $raffle) }}" enctype="multipart/form-data" data-raffle-purchase data-random-url="{{ route('purchases.random', $raffle) }}" data-mode="{{ $raffle->assignment_mode }}" data-max-random-changes="{{ $raffle->max_random_changes }}" data-numbers-url="{{ route('raffles.numbers', $raffle) }}">
                    @csrf
                    <div class="bg-gradient-to-br from-[#063d32] to-[#0f766e] p-5 text-white">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-200">Compra segura</p>
                                <h2 class="mt-1 text-3xl font-black tracking-tight">Tus numeros</h2>
                            </div>
                            <span class="rounded-full bg-amber-200 px-3 py-2 text-xs font-black text-[#063d32]">Activa</span>
                        </div>
                        <p class="mt-3 text-sm font-semibold leading-6 text-teal-50">Elige un paquete, cambia al azar si quieres y sube el comprobante para reservar.</p>
                    </div>

                    <div class="grid gap-4 p-4 sm:p-5">
                        <div class="grid gap-3">
                            <label class="grid gap-1 text-sm font-black text-slate-600">Nombre completo<input class="field" name="buyer_name" value="{{ old('buyer_name') }}" autocomplete="name" required></label>
                            <label class="grid gap-1 text-sm font-black text-slate-600">Telefono<input class="field" name="buyer_phone" value="{{ old('buyer_phone') }}" inputmode="tel" autocomplete="tel" required></label>
                            <label class="grid gap-1 text-sm font-black text-slate-600">Correo<input class="field" type="email" name="buyer_email" value="{{ old('buyer_email') }}" autocomplete="email"></label>
                        </div>

                        <section class="rounded-2xl border border-teal-100 bg-teal-50/80 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-black text-slate-950">Paquetes rapidos</p>
                                <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black uppercase text-teal-700">Hasta 5</span>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2" data-package-options>
                                @foreach ($packageOptions as $option)
                                    <button class="rounded-2xl border border-white bg-white px-3 py-4 text-center font-black leading-tight text-slate-950 shadow-sm transition hover:border-amber-400 hover:bg-amber-50" type="button" data-package="{{ $option['packages'] }}" data-quantity="{{ $option['quantity'] }}" data-amount="{{ $option['amount'] }}">
                                        {{ $raffle->assignment_mode === 'manual' ? 'Azar ' : '' }}{{ $option['quantity'] }}<br><span class="text-sm text-slate-500">numeros</span>
                                    </button>
                                @endforeach
                            </div>
                            <input type="hidden" name="package_count" value="1" data-package-count>
                            <p class="mt-3 text-sm font-semibold leading-6 text-slate-600" data-package-help>Selecciona un paquete o escoge manualmente en la cuadricula.</p>
                        </section>

                        @if ($raffle->assignment_mode === 'manual')
                            <section class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="font-black text-slate-950">Cuadricula manual</p>
                                        <p class="text-xs font-black uppercase tracking-wide text-slate-400" data-number-range>Cargando numeros...</p>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-500">{{ number_format($raffle->available_count) }} disp.</span>
                                </div>
                                <div class="flex items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-2">
                                    <button class="grid h-10 w-10 place-items-center rounded-xl bg-white text-xl font-black text-slate-800 shadow-sm transition hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-35" type="button" data-number-page-prev aria-label="Pagina anterior">‹</button>
                                    <strong class="text-sm font-black text-slate-600" data-number-page-label>Pagina 1</strong>
                                    <button class="grid h-10 w-10 place-items-center rounded-xl bg-white text-xl font-black text-slate-800 shadow-sm transition hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-35" type="button" data-number-page-next aria-label="Pagina siguiente">›</button>
                                </div>
                                <div class="mt-3 grid max-h-[18rem] grid-cols-4 gap-1.5 overflow-auto pr-1 min-[420px]:grid-cols-5 sm:grid-cols-5 xl:grid-cols-5" data-number-grid>
                                    <p class="col-span-full rounded-xl border border-dashed border-slate-300 p-4 text-center text-sm font-black text-slate-400">Cargando numeros...</p>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-3 text-xs font-bold text-slate-500">
                                    <span><span class="inline-block h-3 w-3 rounded bg-teal-50 ring-1 ring-teal-300 align-middle"></span> Disponible</span>
                                    <span><span class="inline-block h-3 w-3 rounded bg-teal-700 align-middle"></span> Tu seleccion</span>
                                    <span><span class="inline-block h-3 w-3 rounded bg-slate-200 opacity-45 align-middle"></span> No disponible</span>
                                </div>
                            </section>
                        @endif

                        <section class="rounded-2xl border border-dashed border-amber-300 bg-gradient-to-br from-amber-50 to-white p-4">
                            <p class="text-sm font-black text-[#063d32]">Seleccion actual</p>
                            <div class="mt-3 grid min-h-10 grid-cols-2 gap-2" data-selected-list>Ninguno</div>
                            <div data-hidden-numbers></div>
                            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                <button class="rounded-xl bg-amber-400 px-3 py-3 text-sm font-black text-slate-950 shadow-sm transition hover:bg-amber-300" type="button" data-reroll-selection hidden>Cambiar numeros (5 restantes)</button>
                                <button class="rounded-xl bg-red-50 px-3 py-3 text-sm font-black text-red-700 transition hover:bg-red-100" type="button" data-clear-selection hidden>Eliminar seleccion</button>
                            </div>
                            <p class="mt-3 text-2xl font-black text-teal-700" data-total>Total: ₡0</p>
                        </section>

                        <label class="grid gap-1 text-sm font-black text-slate-600">Comprobante<input class="field" type="file" name="receipt" accept="image/*,.pdf" required></label>
                        <button class="primary-action w-full text-base" type="submit">Enviar comprobante</button>
                    </div>
                </form>
            @else
                <aside class="order-1 rounded-[1.35rem] border border-amber-200 bg-amber-50 p-5 shadow-xl shadow-amber-950/5 xl:sticky xl:top-4 xl:order-2">
                    <p class="text-xs font-black uppercase tracking-wide text-amber-700">Venta pausada</p>
                    <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950">Este sorteo esta pausado temporalmente</h2>
                    <p class="mt-3 leading-7 text-slate-700">Por el momento no se pueden seleccionar numeros ni enviar comprobantes. La informacion del sorteo sigue visible y la venta puede reactivarse desde administracion.</p>
                    <div class="mt-5 rounded-2xl border border-amber-200 bg-white p-4">
                        <p class="text-sm font-black text-slate-600">Estado actual</p>
                        <strong class="mt-1 block text-xl font-black text-amber-700">Pausada por administracion</strong>
                    </div>
                </aside>
            @endif

            <div class="order-2 min-w-0 space-y-5 xl:order-1">
                <section class="grid gap-3 sm:grid-cols-3">
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <span class="text-xs font-black uppercase tracking-wide text-teal-700">1. Escoge</span>
                        <h3 class="mt-1 text-lg font-black">Manual o al azar</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Selecciona paquetes automaticos o usa la cuadricula.</p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <span class="text-xs font-black uppercase tracking-wide text-teal-700">2. Paga</span>
                        <h3 class="mt-1 text-lg font-black">Sube comprobante</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Tus numeros quedan reservados para validacion.</p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <span class="text-xs font-black uppercase tracking-wide text-teal-700">3. Confirma</span>
                        <h3 class="mt-1 text-lg font-black">Correo y estado</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Al aprobarse, pasan a vendidos.</p>
                    </article>
                </section>

                <article class="overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white shadow-xl shadow-slate-950/5">
                    <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-[1fr_230px]">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Premio</p>
                            <h2 class="mt-1 text-3xl font-black tracking-tight">{{ $raffle->prize_title ?? 'Premio por definir' }}</h2>
                            <p class="mt-3 max-w-3xl whitespace-pre-line leading-7 text-slate-600">{{ $raffle->prize_description }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-950 p-4 text-white">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-400">Paquete</p>
                            <strong class="mt-1 block text-3xl font-black text-amber-300">₡{{ number_format($raffle->price_per_package, 0, ',', ' ') }}</strong>
                            <p class="mt-1 text-sm text-slate-300">Incluye {{ $raffle->numbers_per_package }} numero(s)</p>
                        </div>
                    </div>
                </article>

                @if ($mediaItems->isNotEmpty())
                    <section class="rounded-[1.35rem] border border-slate-200 bg-white p-5 shadow-xl shadow-slate-950/5 sm:p-6">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.22em] text-teal-700">Galeria</p>
                                <h2 class="mt-1 text-2xl font-black tracking-tight">Fotos y videos del premio</h2>
                            </div>
                            <span class="rounded-full bg-amber-50 px-3 py-2 text-xs font-black uppercase text-[#063d32]">{{ $mediaItems->count() }} archivo(s)</span>
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

                @if ($raffle->public_sales_text)
                    <article class="overflow-hidden rounded-[1.35rem] border border-teal-900/25 bg-[#06110f] text-white shadow-2xl shadow-teal-950/10">
                        <div class="border-b border-white/10 bg-gradient-to-r from-[#063d32] to-[#06110f] p-5 sm:p-6">
                            <p class="text-xs font-black uppercase tracking-[0.22em] text-amber-300">Evento relacionado</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight">{{ $raffle->prize_title ?? $raffle->name }}</h2>
                        </div>
                        <div class="p-5 sm:p-6">
                            <p class="whitespace-pre-line text-lg leading-9 text-slate-300">{{ $raffle->public_sales_text }}</p>
                        </div>
                        <div class="grid gap-3 border-t border-white/10 p-5 sm:grid-cols-3 sm:p-6">
                            <div><p class="text-xs font-black uppercase tracking-wide text-slate-500">Disponibles</p><strong class="mt-1 block text-3xl font-black">{{ number_format($raffle->available_count) }}</strong></div>
                            <div><p class="text-xs font-black uppercase tracking-wide text-slate-500">Precio</p><strong class="mt-1 block text-3xl font-black text-amber-300">₡{{ number_format($raffle->price_per_package, 0, ',', ' ') }}</strong></div>
                            <div><p class="text-xs font-black uppercase tracking-wide text-slate-500">Sorteo</p><strong class="mt-1 block text-xl font-black">{{ $raffle->draw_date ? $raffle->draw_date->format('d/m/Y') : 'Por definir' }}</strong></div>
                        </div>
                    </article>
                @endif

                <article class="rounded-[1.35rem] border border-slate-200 bg-white p-5 shadow-xl shadow-slate-950/5 sm:p-6">
                    <h2 class="text-2xl font-black tracking-tight">Como participar</h2>
                    <p class="mt-4 whitespace-pre-line leading-7 text-slate-600">{{ $raffle->rules_text }}</p>
                </article>
            </div>
        </div>
    </section>
</x-layouts.app>





