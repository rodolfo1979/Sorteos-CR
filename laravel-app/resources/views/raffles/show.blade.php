<x-layouts.app title="Comprar numeros - Sorteos CR" section="Sitio publico">
    <section class="grid gap-6 xl:grid-cols-[1fr_420px]">
        <div class="space-y-6">
            <div class="rounded-lg bg-red-500 p-5 text-white shadow-sm">
                <p class="text-sm font-black uppercase">Rifa activa</p>
                <h2 class="mt-1 text-3xl font-black">{{ $raffle->name }}</h2>
                <p class="mt-2 text-red-50">{{ $raffle->draw_date ? 'Sorteo: '.$raffle->draw_date->format('d/m/Y') : 'Fecha por definir' }}</p>
            </div>

            <article class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                <div class="grid min-h-72 place-items-center bg-stone-100">
                    @if ($raffle->image_path)
                        <img class="h-full w-full object-cover" src="{{ Storage::url($raffle->image_path) }}" alt="Premio {{ $raffle->name }}">
                    @else
                        <span class="font-black text-stone-500">Fotografia profesional del premio</span>
                    @endif
                </div>
                <div class="p-5">
                    <p class="text-xs font-black uppercase text-stone-500">Premio</p>
                    <h3 class="mt-1 text-2xl font-black">{{ $raffle->prize_title ?? 'Premio por definir' }}</h3>
                    <p class="mt-3 whitespace-pre-line text-stone-600">{{ $raffle->prize_description }}</p>
                </div>
            </article>

            <article class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
                <h3 class="text-xl font-black">Como participar</h3>
                <p class="mt-3 whitespace-pre-line text-stone-600">{{ $raffle->rules_text }}</p>
            </article>
        </div>

        <form class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm" method="post" action="{{ route('purchases.store', $raffle) }}" enctype="multipart/form-data" data-raffle-purchase data-random-url="{{ route('purchases.random', $raffle) }}" data-mode="{{ $raffle->assignment_mode }}">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase text-stone-500">Compra</p>
                    <h3 class="text-2xl font-black">Tus numeros</h3>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-2 text-sm font-black text-emerald-700">{{ $raffle->sale_enabled ? 'Venta activa' : 'Pausada' }}</span>
            </div>

            <div class="mt-5 grid gap-3">
                <label class="grid gap-1 text-sm font-bold text-stone-600">Nombre completo<input class="rounded-lg border border-stone-300 px-3 py-3" name="buyer_name" value="{{ old('buyer_name') }}" required></label>
                <label class="grid gap-1 text-sm font-bold text-stone-600">Telefono<input class="rounded-lg border border-stone-300 px-3 py-3" name="buyer_phone" value="{{ old('buyer_phone') }}" required></label>
                <label class="grid gap-1 text-sm font-bold text-stone-600">Correo<input class="rounded-lg border border-stone-300 px-3 py-3" type="email" name="buyer_email" value="{{ old('buyer_email') }}"></label>
            </div>

            <div class="mt-5 rounded-lg border border-stone-200 bg-stone-50 p-4">
                <p class="font-black">Paquetes de compra</p>
                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3" data-package-options>
                    @foreach ($packageOptions as $option)
                        <button class="rounded-lg border border-stone-300 bg-white px-3 py-3 font-black hover:border-red-400" type="button" data-package="{{ $option['packages'] }}" data-quantity="{{ $option['quantity'] }}" data-amount="{{ $option['amount'] }}">
                            {{ $raffle->assignment_mode === 'manual' ? 'Azar ' : '' }}{{ $option['quantity'] }} numeros
                        </button>
                    @endforeach
                </div>
                <input type="hidden" name="package_count" value="1" data-package-count>
                <p class="mt-3 text-sm text-stone-600" data-package-help>Selecciona un paquete o escoge manualmente en la cuadricula.</p>
            </div>

            @if ($raffle->assignment_mode === 'manual')
                <div class="mt-5 rounded-lg border border-stone-200 bg-white p-4">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="font-black">Cuadricula manual</p>
                        <span class="text-sm font-bold text-stone-500">Disponibles: {{ number_format($raffle->available_count) }}</span>
                    </div>
                    <div class="grid max-h-80 grid-cols-5 gap-2 overflow-auto pr-1 sm:grid-cols-6">
                        @foreach ($raffle->numbers()->where('status', 'available')->orderBy('number')->limit(180)->get() as $number)
                            <button type="button" class="rounded-lg border border-stone-200 bg-stone-50 px-2 py-2 text-sm font-black hover:border-red-400" data-number-button="{{ $number->number }}">{{ $number->number }}</button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-5 rounded-lg border border-dashed border-stone-300 bg-stone-50 p-4">
                <p class="text-sm font-bold text-stone-500">Numeros seleccionados</p>
                <div class="mt-2 flex flex-wrap gap-2 text-lg font-black" data-selected-list>Ninguno</div>
                <div data-hidden-numbers></div>
                <button class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm font-black text-red-700" type="button" data-clear-selection hidden>Eliminar seleccion</button>
                <p class="mt-3 font-black text-emerald-700" data-total>Total: ₡0</p>
            </div>

            <label class="mt-5 grid gap-1 text-sm font-bold text-stone-600">Comprobante<input class="rounded-lg border border-stone-300 px-3 py-3" type="file" name="receipt" accept="image/*,.pdf" required></label>

            <button class="mt-5 w-full rounded-lg bg-emerald-700 px-4 py-4 font-black text-white hover:bg-emerald-800" type="submit">Enviar comprobante</button>
        </form>
    </section>
</x-layouts.app>
