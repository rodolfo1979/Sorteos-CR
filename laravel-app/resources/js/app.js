import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const formatter = new Intl.NumberFormat('es-CR', {
    style: 'currency',
    currency: 'CRC',
    maximumFractionDigits: 0,
});

document.querySelectorAll('[data-admin-sales-chart]').forEach((canvas) => {
    const data = JSON.parse(canvas.dataset.adminSalesChart || '{}');
    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: data.labels || [],
            datasets: [
                { label: 'Vendidos', data: data.sold || [], backgroundColor: '#0891b2', borderRadius: 10 },
                { label: 'Reservados', data: data.reserved || [], backgroundColor: '#f59e0b', borderRadius: 10 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { boxWidth: 12, font: { weight: 'bold' } } },
                tooltip: { backgroundColor: '#0f172a', padding: 12 },
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { weight: 'bold' } } },
                y: { beginAtZero: true, grid: { color: '#e2e8f0' } },
            },
        },
    });
});

document.querySelectorAll('[data-raffle-purchase]').forEach((form) => {
    const mode = form.dataset.mode;
    const randomUrl = form.dataset.randomUrl;
    const numbersUrl = form.dataset.numbersUrl;
    const maxRandomChanges = Number(form.dataset.maxRandomChanges || 5);
    const packageCountInput = form.querySelector('[data-package-count]');
    const packageHelp = form.querySelector('[data-package-help]');
    const selectedList = form.querySelector('[data-selected-list]');
    const hiddenNumbers = form.querySelector('[data-hidden-numbers]');
    const clearButton = form.querySelector('[data-clear-selection]');
    const rerollButton = form.querySelector('[data-reroll-selection]');
    const totalLabel = form.querySelector('[data-total]');
    const numberGrid = form.querySelector('[data-number-grid]');
    const numberRange = form.querySelector('[data-number-range]');
    const pageLabel = form.querySelector('[data-number-page-label]');
    const prevPageButton = form.querySelector('[data-number-page-prev]');
    const nextPageButton = form.querySelector('[data-number-page-next]');
    let selectedNumbers = [];
    let expectedQuantity = 0;
    let amount = 0;
    let currentPackageCount = 1;
    let randomChangesUsed = 0;
    let currentNumberPage = 1;
    let totalNumberPages = 1;
    let loadedNumbers = [];

    function remainingChanges() {
        return Math.max(0, maxRandomChanges - randomChangesUsed);
    }

    function setActivePackage(packageCount) {
        form.querySelectorAll('[data-package]').forEach((button) => {
            const isActive = Number(button.dataset.package) === Number(packageCount);
            button.classList.toggle('ring-2', isActive);
            button.classList.toggle('ring-white', isActive);
            button.classList.toggle('ring-offset-2', isActive);
            button.classList.toggle('ring-offset-slate-950', isActive);
            button.classList.toggle('scale-[1.02]', isActive);
            button.classList.toggle('shadow-xl', isActive);
        });
    }

    function ticketMarkup(number) {
        return `
            <span class="relative flex min-h-12 items-center justify-center overflow-hidden rounded-xl bg-cyan-700 px-3 py-2 text-lg font-black tracking-wide text-white shadow-lg shadow-cyan-900/10 ring-1 ring-amber-400/45">
                <span class="absolute -left-2 top-1/2 h-4 w-4 -translate-y-1/2 rounded-full bg-slate-50"></span>
                <span class="absolute -right-2 top-1/2 h-4 w-4 -translate-y-1/2 rounded-full bg-slate-50"></span>
                <svg class="mr-2 h-5 w-5 text-amber-200/80" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h11A2.5 2.5 0 0 1 20 8.5v2a2 2 0 0 0 0 3v2A2.5 2.5 0 0 1 17.5 18h-11A2.5 2.5 0 0 1 4 15.5v-2a2 2 0 0 0 0-3v-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M9 8v8M15 8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="1 3"/>
                </svg>
                ${number}
            </span>
        `;
    }
    function numberButtonMarkup(item) {
        const selected = selectedNumbers.includes(item.number);
        const disabled = !item.available && !selected;
        const stateClass = selected
            ? 'border-cyan-900 bg-cyan-700 text-white shadow-md shadow-cyan-900/10'
            : (item.available
                ? 'border-amber-300 bg-amber-50 text-slate-950 hover:border-cyan-400 hover:bg-cyan-50 hover:shadow-sm'
                : 'cursor-not-allowed border-slate-200 bg-white/45 text-slate-400 opacity-45 grayscale');
        const label = item.available ? 'Disponible' : 'No disponible';

        return `
            <button type="button" class="relative min-h-10 min-w-14 overflow-hidden rounded-xl border px-2 py-1.5 text-center text-[0.82rem] font-black tracking-wide transition ${stateClass}" data-number-button="${item.number}" title="${label}: ${item.number}" ${disabled ? 'disabled aria-disabled="true"' : ''}>
                <span class="absolute -left-1.5 top-1/2 h-3 w-3 -translate-y-1/2 rounded-full bg-white"></span>
                <span class="absolute -right-1.5 top-1/2 h-3 w-3 -translate-y-1/2 rounded-full bg-white"></span>
                <span class="relative z-10">${item.number}</span>
            </button>
        `;
    }
    function renderNumberGrid() {
        if (!numberGrid) {
            return;
        }

        numberGrid.innerHTML = loadedNumbers.length
            ? loadedNumbers.map((item) => numberButtonMarkup(item)).join('')
            : '<p class="col-span-full rounded-xl border border-dashed border-slate-300 p-4 text-center text-sm font-black text-slate-400">No hay numeros en esta pagina.</p>';

        if (pageLabel) {
            pageLabel.textContent = `Pagina ${currentNumberPage} de ${totalNumberPages}`;
        }
        if (prevPageButton) {
            prevPageButton.disabled = currentNumberPage <= 1;
        }
        if (nextPageButton) {
            nextPageButton.disabled = currentNumberPage >= totalNumberPages;
        }
    }

    async function loadNumberPage(page = 1) {
        if (!numbersUrl || !numberGrid) {
            return;
        }

        numberGrid.innerHTML = '<p class="col-span-full rounded-xl border border-dashed border-slate-300 p-4 text-center text-sm font-black text-slate-400">Cargando numeros...</p>';
        const url = new URL(numbersUrl, window.location.origin);
        url.searchParams.set('page', page);
        url.searchParams.set('per_page', 50);

        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            numberGrid.innerHTML = '<p class="col-span-full rounded-xl border border-amber-200 bg-amber-50 p-4 text-center text-sm font-black text-amber-800">No se pudo cargar esta pagina de numeros.</p>';
            return;
        }

        const data = await response.json();
        loadedNumbers = data.numbers || [];
        currentNumberPage = Number(data.page || page);
        totalNumberPages = Number(data.total_pages || 1);
        if (numberRange) {
            numberRange.textContent = data.range || '';
        }
        renderNumberGrid();
    }

    function render() {
        selectedList.innerHTML = selectedNumbers.length
            ? selectedNumbers.map((number) => ticketMarkup(number)).join('')
            : '<span class="text-base font-bold text-slate-400">Ninguno</span>';

        hiddenNumbers.innerHTML = selectedNumbers
            .map((number) => `<input type="hidden" name="numbers[]" value="${number}">`)
            .join('');

        clearButton.hidden = selectedNumbers.length === 0;
        if (rerollButton) {
            const canReroll = selectedNumbers.length > 0 && expectedQuantity > 0 && remainingChanges() > 0;
            rerollButton.hidden = !canReroll;
            rerollButton.disabled = !canReroll;
            rerollButton.textContent = `Cambiar numeros (${remainingChanges()} restantes)`;
        }
        totalLabel.textContent = `Total: ${formatter.format(amount)}`;
        renderNumberGrid();
    }

    async function takeRandom(packageCount, quantity, newAmount, options = {}) {
        const countAsChange = options.countAsChange || false;
        if (countAsChange && remainingChanges() <= 0) {
            packageHelp.textContent = 'Ya usaste todos los cambios permitidos para esta compra.';
            render();
            return;
        }

        packageCountInput.value = packageCount;
        currentPackageCount = packageCount;
        expectedQuantity = quantity;
        amount = newAmount;
        setActivePackage(packageCount);
        packageHelp.textContent = countAsChange ? 'Cambiando numeros al azar...' : 'Generando numeros disponibles...';

        const response = await fetch(randomUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ package_count: packageCount }),
        });

        if (!response.ok) {
            packageHelp.textContent = 'No se pudo asignar al azar. Intenta nuevamente.';
            return;
        }

        const data = await response.json();
        selectedNumbers = data.numbers || [];
        if (countAsChange) {
            randomChangesUsed += 1;
        }
        packageHelp.textContent = countAsChange
            ? `Numeros actualizados. Te quedan ${remainingChanges()} cambio(s).`
            : (mode === 'manual'
                ? `Se asignaron ${quantity} numeros al azar. Puedes cambiarlos o borrar la seleccion y escoger en la cuadricula.`
                : `El sistema asigno automaticamente ${quantity} numeros. Puedes cambiarlos hasta ${maxRandomChanges} vez/veces.`);
        render();
    }

    function selectManualNumber(number) {
        const baseQuantity = Number(form.querySelector('[data-package="1"]').dataset.quantity);
        selectedNumbers = selectedNumbers.includes(number)
            ? selectedNumbers.filter((selected) => selected !== number)
            : [...selectedNumbers, number];

        const packages = Math.min(5, Math.max(1, Math.ceil(selectedNumbers.length / baseQuantity)));
        const activePackage = form.querySelector(`[data-package="${packages}"]`);
        packageCountInput.value = packages;
        currentPackageCount = packages;
        expectedQuantity = Number(activePackage.dataset.quantity);
        amount = Number(activePackage.dataset.amount);
        setActivePackage(packages);
        randomChangesUsed = 0;
        packageHelp.textContent = selectedNumbers.length === expectedQuantity
            ? `Seleccion completa de ${expectedQuantity} numeros. Puedes cambiarlos al azar hasta ${maxRandomChanges} vez/veces.`
            : `Te falta ${expectedQuantity - selectedNumbers.length} numero(s) para completar la compra de ${expectedQuantity}.`;
        render();
    }

    form.querySelectorAll('[data-package]').forEach((button) => {
        button.addEventListener('click', () => {
            randomChangesUsed = 0;
            takeRandom(Number(button.dataset.package), Number(button.dataset.quantity), Number(button.dataset.amount));
        });
    });

    if (rerollButton) {
        rerollButton.addEventListener('click', () => {
            const activePackage = form.querySelector(`[data-package="${currentPackageCount}"]`) || form.querySelector('[data-package="1"]');
            takeRandom(Number(activePackage.dataset.package), Number(activePackage.dataset.quantity), Number(activePackage.dataset.amount), { countAsChange: true });
        });
    }

    if (numberGrid) {
        numberGrid.addEventListener('click', (event) => {
            const button = event.target.closest('[data-number-button]');
            if (!button || button.disabled) {
                return;
            }
            selectManualNumber(button.dataset.numberButton);
        });
    }

    if (prevPageButton) {
        prevPageButton.addEventListener('click', () => loadNumberPage(currentNumberPage - 1));
    }

    if (nextPageButton) {
        nextPageButton.addEventListener('click', () => loadNumberPage(currentNumberPage + 1));
    }

    clearButton.addEventListener('click', () => {
        selectedNumbers = [];
        expectedQuantity = 0;
        amount = 0;
        currentPackageCount = 1;
        randomChangesUsed = 0;
        packageHelp.textContent = mode === 'manual'
            ? 'Selecciona un paquete al azar o escoge manualmente en la cuadricula.'
            : 'Selecciona una cantidad para asignar numeros automaticamente.';
        form.querySelectorAll('[data-package]').forEach((button) => {
            button.classList.remove('ring-2', 'ring-white', 'ring-offset-2', 'ring-offset-slate-950', 'scale-[1.02]', 'shadow-xl');
        });
        render();
    });

    form.addEventListener('submit', (event) => {
        if (!selectedNumbers.length || selectedNumbers.length !== expectedQuantity) {
            event.preventDefault();
            const missing = Math.max(0, expectedQuantity - selectedNumbers.length);
            alert(missing > 0 ? `Te falta ${missing} numero(s) para completar la compra.` : 'Selecciona los numeros de la compra.');
        }
    });

    render();
    loadNumberPage(1);
});




