<x-layouts.app title="Numeros - Sorteos CR" section="Inventario">
    <section class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
        <h2 class="text-2xl font-black">Control de numeros</h2>
        <form class="mt-4 grid gap-3 sm:grid-cols-3" method="get">
            <input class="rounded-lg border border-stone-300 px-3 py-3" name="search" value="{{ $search }}" placeholder="Buscar numero">
            <select class="rounded-lg border border-stone-300 px-3 py-3" name="status">
                <option value="">Todos</option>
                @foreach (['available' => 'Disponibles', 'reserved' => 'Reservados', 'sold' => 'Vendidos'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-cyan-700 px-4 py-3 font-black text-white">Filtrar</button>
        </form>
        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[680px] text-left text-sm">
                <thead><tr class="border-b"><th class="py-3">Numero</th><th>Estado</th><th>Reservado hasta</th></tr></thead>
                <tbody>
                    @foreach ($numbers as $number)
                        <tr class="border-b"><td class="py-3 font-black">{{ $number->number }}</td><td>{{ $number->status }}</td><td>{{ $number->reserved_until?->timezone('America/Costa_Rica')->format('d/m/Y H:i') ?? '-' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $numbers->links() }}</div>
    </section>
</x-layouts.app>
