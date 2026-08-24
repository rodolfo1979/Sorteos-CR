<x-layouts.app title="Editar rifa - Sorteos CR" section="Administracion">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.22em] text-indigo-700">Configuracion de venta</p>
            <h2 class="mt-2 text-4xl font-black tracking-tight">Editar {{ $raffle->name }}</h2>
            <p class="mt-2 max-w-2xl text-slate-600">Ajusta el contenido que ve el cliente, las reglas de compra y el estado de venta del sorteo.</p>
        </div>
        <a class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-black text-white transition hover:bg-slate-700" href="{{ route('admin.dashboard') }}">Volver</a>
    </div>

    @if (session('status'))
        <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 font-black text-cyan-900">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800">
            <strong class="font-black">Revisa los campos marcados.</strong>
            <ul class="mt-2 list-disc pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]" method="post" action="{{ route('admin.raffles.update', $raffle) }}" enctype="multipart/form-data">
        @csrf
        @method('put')

        <section class="space-y-6">
            <article class="surface p-5">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Contenido publico</p>
                <h3 class="mt-1 text-2xl font-black tracking-tight">Texto visible en la pagina de venta</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Este texto se muestra al cliente como descripcion comercial del sorteo. Puedes escribir premios, condiciones, forma de participar y detalles importantes.</p>

                <div class="mt-5 grid gap-4">
                    <label class="grid gap-1 text-sm font-black text-slate-600">Nombre de la rifa
                        <input class="field" name="name" value="{{ old('name', $raffle->name) }}" required>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Titulo del premio
                        <input class="field" name="prize_title" value="{{ old('prize_title', $raffle->prize_title) }}">
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Descripcion corta del premio
                        <textarea class="field min-h-28" name="prize_description">{{ old('prize_description', $raffle->prize_description) }}</textarea>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Texto comercial de la pagina de venta
                        <textarea class="field min-h-72" name="public_sales_text" placeholder="Ejemplo: Por cada compra recibes 2 numeros digitales...">{{ old('public_sales_text', $raffle->public_sales_text) }}</textarea>
                    </label>
                </div>
            </article>
            <article class="surface p-5">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Fotos y videos</p>
                <h3 class="mt-1 text-2xl font-black tracking-tight">Galeria del premio</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Sube una imagen principal para la portada y, opcionalmente, hasta {{ $maxGalleryItems }} fotos o videos adicionales para la pagina de venta.</p>

                @if ($raffle->image_path || filled($raffle->media_paths))
                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @if ($raffle->image_path)
                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                <img class="h-36 w-full object-cover" src="{{ Storage::url($raffle->image_path) }}" alt="Imagen principal de {{ $raffle->name }}">
                                <div class="grid gap-3 p-3">
                                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Imagen principal</p>
                                    <label class="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-black text-red-700">
                                        <input class="h-4 w-4 accent-red-600" type="checkbox" name="remove_image" value="1">
                                        Eliminar imagen principal
                                    </label>
                                </div>
                            </div>
                        @endif
                        @foreach (($raffle->media_paths ?? []) as $mediaPath)
                            @php $isVideo = in_array(strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION)), ['mp4', 'mov', 'webm'], true); @endphp
                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                @if ($isVideo)
                                    <video class="h-36 w-full object-cover" src="{{ Storage::url($mediaPath) }}" controls muted></video>
                                @else
                                    <img class="h-36 w-full object-cover" src="{{ Storage::url($mediaPath) }}" alt="Galeria {{ $raffle->name }}">
                                @endif
                                <div class="grid gap-3 p-3">
                                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">{{ $isVideo ? 'Video' : 'Foto' }}</p>
                                    <label class="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-black text-red-700">
                                        <input class="h-4 w-4 accent-red-600" type="checkbox" name="remove_media[]" value="{{ $mediaPath }}">
                                        Eliminar {{ $isVideo ? 'video' : 'foto' }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-5 grid gap-4">
                    <label class="grid gap-1 text-sm font-black text-slate-600">Cambiar imagen principal
                        <input class="field" type="file" name="image" accept="image/jpeg,image/png,image/webp">
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Agregar fotos o videos adicionales
                        <input class="field" type="file" name="media_files[]" accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm" multiple>
                    </label>
                    <p class="text-xs font-bold leading-5 text-slate-500">Puedes tener hasta {{ $maxGalleryItems }} archivos adicionales en total. Se pueden subir hasta 8 archivos por vez. Formatos permitidos: JPG, PNG, WEBP, MP4, MOV y WEBM.</p>
                </div>
            </article>
            <article class="surface p-5">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Informacion operativa</p>
                <h3 class="mt-1 text-2xl font-black tracking-tight">Reglas y pago</h3>
                <div class="mt-5 grid gap-4">
                    <label class="grid gap-1 text-sm font-black text-slate-600">Instrucciones de pago
                        <textarea class="field min-h-40" name="payment_instructions">{{ old('payment_instructions', $raffle->payment_instructions) }}</textarea>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Reglas visibles
                        <textarea class="field min-h-48" name="rules_text">{{ old('rules_text', $raffle->rules_text) }}</textarea>
                    </label>
                </div>
            </article>
        </section>

        <aside class="space-y-6">
            <article class="surface p-5">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Venta</p>
                <h3 class="mt-1 text-2xl font-black tracking-tight">Configuracion</h3>
                <div class="mt-5 grid gap-4">
                    <label class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-black text-slate-700">
                        Venta activa
                        <input class="h-5 w-5 accent-cyan-700" type="checkbox" name="sale_enabled" value="1" @checked(old('sale_enabled', $raffle->sale_enabled))>
                    </label>
                    <label class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-black text-slate-700">
                        Mostrar en venta principal
                        <input class="h-5 w-5 accent-cyan-700" type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $raffle->is_featured))>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Modo de asignacion
                        <select class="field" name="assignment_mode">
                            <option value="manual" @selected(old('assignment_mode', $raffle->assignment_mode) === 'manual')>Manual y al azar</option>
                            <option value="random" @selected(old('assignment_mode', $raffle->assignment_mode) === 'random')>Solo al azar</option>
                        </select>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Fecha del sorteo
                        <input class="field" type="date" name="draw_date" value="{{ old('draw_date', optional($raffle->draw_date)->format('Y-m-d')) }}">
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Precio por paquete
                        <input class="field" type="number" name="price_per_package" min="1" value="{{ old('price_per_package', $raffle->price_per_package) }}" required>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Numeros por paquete
                        <input class="field" type="number" name="numbers_per_package" min="1" max="100" value="{{ old('numbers_per_package', $raffle->numbers_per_package) }}" required>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Cambios al azar permitidos
                        <input class="field" type="number" name="max_random_changes" min="0" max="50" value="{{ old('max_random_changes', $raffle->max_random_changes) }}" required>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Minutos de reserva
                        <input class="field" type="number" name="reservation_minutes" min="1" max="10080" value="{{ old('reservation_minutes', $raffle->reservation_minutes) }}" required>
                    </label>
                </div>
            </article>

            <article class="surface p-5">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Organizador</p>
                <div class="mt-4 grid gap-4">
                    <label class="grid gap-1 text-sm font-black text-slate-600">Nombre
                        <input class="field" name="organizer_name" value="{{ old('organizer_name', $raffle->organizer_name) }}">
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">WhatsApp
                        <input class="field" name="organizer_whatsapp" value="{{ old('organizer_whatsapp', $raffle->organizer_whatsapp) }}">
                    </label>
                </div>
            </article>

            <button class="primary-action w-full" type="submit">Guardar cambios</button>
        </aside>
    </form>
</x-layouts.app>

