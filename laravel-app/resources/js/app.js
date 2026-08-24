import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const formatter = new Intl.NumberFormat('es-CR', {
    style: 'currency',
    currency: 'CRC',
    maximumFractionDigits: 0,
});

document.querySelectorAll('[data-admin-sales-chart]').forEach((canvas) => {
    const data = JSON.parse(canvas.dataset.adminSalesChart || '{}');
    const adminChart = new Chart(canvas, {
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

    canvas.adminSalesChart = adminChart;
});
const adminRealtimeRoots = document.querySelectorAll('[data-admin-realtime-url]');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
const statusLabels = {
    pending: 'Pendiente',
    approved: 'Aprobada',
    rejected: 'Rechazada',
};

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#039;',
        '"': '&quot;',
    }[char]));
}

function renderRecentOrder(order) {
    const numbers = (order.numbers || []).join(', ');
    const status = statusLabels[order.status] || order.status;

    return `
        <article class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4">
            <div>
                <strong>${escapeHtml(order.buyer_name)}</strong>
                <p class="text-sm text-slate-500">${escapeHtml(order.raffle_name)} · ${escapeHtml(numbers)}</p>
            </div>
            <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-black text-amber-700">${escapeHtml(status)}</span>
        </article>
    `;
}

function renderPendingOrder(order) {
    const numbers = (order.numbers || []).join(', ');
    const receipt = order.receipt_url
        ? `<a class="inline-flex rounded-xl bg-slate-100 px-4 py-2 font-black text-slate-800 transition hover:bg-slate-200" href="${escapeHtml(order.receipt_url)}" target="_blank" rel="noopener">Ver comprobante</a>`
        : '<span class="inline-flex rounded-xl bg-red-50 px-4 py-2 font-black text-red-700">Sin comprobante</span>';

    return `
        <article class="rounded-2xl border border-slate-200 p-4 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="font-black">${escapeHtml(order.buyer_name)} - ${escapeHtml(order.raffle_name)}</h3>
                    <p class="text-sm text-slate-500">${escapeHtml(order.buyer_phone)} · ${escapeHtml(order.buyer_email)}</p>
                    <p class="mt-1 text-xs font-bold text-slate-400">Orden ${escapeHtml(order.order_code)} · ${escapeHtml(order.created_at)}</p>
                </div>
                <span class="rounded-full bg-amber-50 px-3 py-1 font-black text-amber-700">${escapeHtml(order.amount)}</span>
            </div>
            <p class="mt-3 text-sm text-slate-700">Numeros: <strong>${escapeHtml(numbers)}</strong></p>
            <div class="mt-4 flex flex-wrap gap-2">
                ${receipt}
                <form method="post" action="${escapeHtml(order.approve_url)}">
                    <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                    <button class="rounded-xl bg-indigo-700 px-4 py-2 font-black text-white transition hover:bg-indigo-800">Aprobar</button>
                </form>
                <form method="post" action="${escapeHtml(order.reject_url)}">
                    <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                    <button class="rounded-xl bg-red-50 px-4 py-2 font-black text-red-700 transition hover:bg-red-100">Rechazar</button>
                </form>
            </div>
        </article>
    `;
}

function refreshAdminRealtime(root) {
    if (document.visibilityState === 'hidden') {
        return;
    }

    fetch(root.dataset.adminRealtimeUrl, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        cache: 'no-store',
    })
        .then((response) => (response.ok ? response.json() : Promise.reject(response)))
        .then((data) => {
            Object.entries(data.stats || {}).forEach(([key, value]) => {
                root.querySelectorAll(`[data-admin-stat="${key}"]`).forEach((element) => {
                    element.textContent = value;
                });
            });

            root.querySelectorAll('[data-admin-updated-at]').forEach((element) => {
                element.textContent = `Actualizado ${data.updated_at}`;
            });

            const chartCanvas = root.querySelector('[data-admin-sales-chart]');
            if (chartCanvas?.adminSalesChart && data.sales_chart) {
                chartCanvas.adminSalesChart.data.labels = data.sales_chart.labels || [];
                chartCanvas.adminSalesChart.data.datasets[0].data = data.sales_chart.sold || [];
                chartCanvas.adminSalesChart.data.datasets[1].data = data.sales_chart.reserved || [];
                chartCanvas.adminSalesChart.update('none');
            }

            const recentList = root.querySelector('[data-admin-recent-list]');
            if (recentList) {
                const orders = data.recent_orders || [];
                recentList.innerHTML = orders.length
                    ? orders.map((order) => renderRecentOrder(order)).join('')
                    : '<p class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-slate-500">Aun no hay compras.</p>';
            }

            const recentCount = root.querySelector('[data-admin-recent-count]');
            if (recentCount) {
                recentCount.textContent = `${(data.recent_orders || []).length} movimiento(s) recientes`;
            }

            const pendingList = root.querySelector('[data-admin-pending-list]');
            if (pendingList) {
                const orders = data.pending_orders || [];
                pendingList.innerHTML = orders.length
                    ? orders.map((order) => renderPendingOrder(order)).join('')
                    : '<p class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-slate-500">No hay comprobantes pendientes.</p>';
            }

            const pendingCount = root.querySelector('[data-admin-pending-count]');
            if (pendingCount) {
                pendingCount.textContent = `${data.stats?.pending_payments || 0} pendiente(s)`;
            }
        })
        .catch(() => {
            root.querySelectorAll('[data-admin-updated-at]').forEach((element) => {
                element.textContent = 'Sin conexion en vivo';
            });
        });
}

adminRealtimeRoots.forEach((root) => {
    refreshAdminRealtime(root);
    setInterval(() => refreshAdminRealtime(root), 5000);
});

document.querySelectorAll('[data-raffle-purchase]').forEach((form) => {
    const mode = form.dataset.mode;
    const randomUrl = form.dataset.randomUrl;
    const numbersUrl = form.dataset.numbersUrl;
    const maxRandomChanges = Number(form.dataset.maxRandomChanges || 5);
    const packageCountInput = form.querySelector('[data-package-count]');
    const packageHelp = form.querySelector('[data-package-help]');
    const selectionSourceInput = form.querySelector('[data-selection-source]');
    const randomChangesInput = form.querySelector('[data-random-changes-used]');
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
        if (selectionSourceInput) {
            selectionSourceInput.value = 'random';
        }
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
            if (randomChangesInput) {
                randomChangesInput.value = randomChangesUsed;
            }
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
        if (selectionSourceInput) {
            selectionSourceInput.value = 'manual';
        }
        if (randomChangesInput) {
            randomChangesInput.value = randomChangesUsed;
        }
        packageHelp.textContent = selectedNumbers.length === expectedQuantity
            ? `Seleccion completa de ${expectedQuantity} numeros. Puedes cambiarlos al azar hasta ${maxRandomChanges} vez/veces.`
            : `Te falta ${expectedQuantity - selectedNumbers.length} numero(s) para completar la compra de ${expectedQuantity}.`;
        render();
    }

    form.querySelectorAll('[data-package]').forEach((button) => {
        button.addEventListener('click', () => {
            randomChangesUsed = 0;
            if (randomChangesInput) {
                randomChangesInput.value = randomChangesUsed;
            }
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
        if (selectionSourceInput) {
            selectionSourceInput.value = 'manual';
        }
        if (randomChangesInput) {
            randomChangesInput.value = randomChangesUsed;
        }
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

