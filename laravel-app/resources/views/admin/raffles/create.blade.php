<x-layouts.app title="Crear sorteo - Sorteos CR" section="Administracion">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.22em] text-teal-700">Nuevo sorteo</p>
            <h2 class="mt-2 text-4xl font-black tracking-tight">Crear sorteo</h2>
            <p class="mt-2 max-w-2xl text-slate-600">Configura la cantidad de numeros, paquetes, modo de compra y texto que vera el cliente.</p>
        </div>
        <a class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-black text-white transition hover:bg-slate-700" href="{{ route('admin.dashboard') }}">Volver</a>
    </div>

    <form class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]" method="post" action="{{ route('admin.raffles.store') }}" enctype="multipart/form-data">
        @csrf

        <section class="space-y-6">
            <article class="surface p-5">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Base del sorteo</p>
                <h3 class="mt-1 text-2xl font-black tracking-tight">Datos principales</h3>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="grid gap-1 text-sm font-black text-slate-600 md:col-span-2">Nombre del sorteo
                        <input class="field" name="name" value="{{ old('name') }}" placeholder="Rifa Moto 2026" required>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Total de numeros
                        <input class="field" type="number" name="total_numbers" min="1" max="100000" value="{{ old('total_numbers', 10000) }}" required>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Digitos visibles
                        <input class="field" type="number" name="number_width" min="2" max="6" value="{{ old('number_width', 4) }}" required>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Fecha del sorteo
                        <input class="field" type="date" name="draw_date" value="{{ old('draw_date') }}">
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Modo de asignacion
                        <select class="field" name="assignment_mode">
                            <option value="manual" @selected(old('assignment_mode', 'manual') === 'manual')>Manual y al azar</option>
                            <option value="random" @selected(old('assignment_mode') === 'random')>Solo al azar</option>
                        </select>
                    </label>
                </div>
            </article>


            <article class="surface p-5">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Fotos y videos</p>
                <h3 class="mt-1 text-2xl font-black tracking-tight">Galeria del premio</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">La imagen principal se usa como portada en la pagina de venta. Tambien puedes subir fotos o videos adicionales para mostrar mejor el premio.</p>
                <div class="mt-5 grid gap-4">
                    <label class="grid gap-1 text-sm font-black text-slate-600">Imagen principal del premio
                        <input class="field" type="file" name="image" accept="image/jpeg,image/png,image/webp">
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Fotos o videos adicionales opcionales
                        <input class="field" type="file" name="media_files[]" accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm" multiple>
                    </label>
                    <p class="text-xs font-bold leading-5 text-slate-500">Formatos permitidos: JPG, PNG, WEBP, MP4, MOV y WEBM. Recomendado: imagenes horizontales y videos cortos.</p>
                </div>
            </article>
            <article class="surface p-5">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Contenido publico</p>
                <h3 class="mt-1 text-2xl font-black tracking-tight">Lo que vera el cliente</h3>
                <div class="mt-5 grid gap-4">
                    <label class="grid gap-1 text-sm font-black text-slate-600">Titulo del premio
                        <input class="field" name="prize_title" value="{{ old('prize_title') }}" placeholder="Moto nueva y casco">
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Descripcion corta
                        <textarea class="field min-h-28" name="prize_description">{{ old('prize_description') }}</textarea>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Texto comercial de venta
                        <textarea class="field min-h-64" name="public_sales_text" placeholder="Describe premios, forma de participar y condiciones importantes.">{{ old('public_sales_text') }}</textarea>
                    </label>
                </div>
            </article>
        </section>

        <aside class="space-y-6">
            <article class="surface p-5">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Venta</p>
                <div class="mt-5 grid gap-4">
                    <label class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-black text-slate-700">
                        Venta activa
                        <input class="h-5 w-5 accent-teal-700" type="checkbox" name="sale_enabled" value="1" @checked(old('sale_enabled', true))>
                    </label>
                    <label class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-black text-slate-700">
                        Mostrar como principal
                        <input class="h-5 w-5 accent-teal-700" type="checkbox" name="is_featured" value="1" @checked(old('is_featured', true))>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Precio por paquete
                        <input class="field" type="number" name="price_per_package" min="1" value="{{ old('price_per_package', 4000) }}" required>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Numeros por paquete
                        <input class="field" type="number" name="numbers_per_package" min="1" max="100" value="{{ old('numbers_per_package', 2) }}" required>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Cambios al azar permitidos
                        <input class="field" type="number" name="max_random_changes" min="0" max="50" value="{{ old('max_random_changes', 5) }}" required>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Minutos de reserva
                        <input class="field" type="number" name="reservation_minutes" min="1" max="10080" value="{{ old('reservation_minutes', 45) }}" required>
                    </label>
                </div>
            </article>

            <article class="surface p-5">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Organizador</p>
                <div class="mt-4 grid gap-4">
                    <label class="grid gap-1 text-sm font-black text-slate-600">Nombre
                        <input class="field" name="organizer_name" value="{{ old('organizer_name', 'Rifas CR') }}">
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">WhatsApp
                        <input class="field" name="organizer_whatsapp" value="{{ old('organizer_whatsapp') }}">
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Instrucciones de pago
                        <textarea class="field min-h-32" name="payment_instructions">{{ old('payment_instructions', 'Sube una imagen o captura de tu comprobante para validar tu participacion.') }}</textarea>
                    </label>
                    <label class="grid gap-1 text-sm font-black text-slate-600">Reglas visibles
                        <textarea class="field min-h-40" name="rules_text">{{ old('rules_text', "Validacion: es obligatorio subir la foto del comprobante.\nReserva: los numeros apartados quedan pendientes hasta validar el pago.\nSi el pago es rechazado, los numeros vuelven a estar disponibles.") }}</textarea>
                    </label>
                </div>
            </article>

            <button class="primary-action w-full" type="submit">Crear sorteo</button>
        </aside>
    </form>
</x-layouts.app>

