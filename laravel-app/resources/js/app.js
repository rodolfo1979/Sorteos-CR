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
                {
                    label: 'Vendidos',
                    data: data.sold || [],
                    backgroundColor: '#0f766e',
                    borderRadius: 10,
                },
                {
                    label: 'Reservados',
                    data: data.reserved || [],
                    backgroundColor: '#f59e0b',
                    borderRadius: 10,
                },
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
    const packageCountInput = form.querySelector('[data-package-count]');
    const packageHelp = form.querySelector('[data-package-help]');
    const selectedList = form.querySelector('[data-selected-list]');
    const hiddenNumbers = form.querySelector('[data-hidden-numbers]');
    const clearButton = form.querySelector('[data-clear-selection]');
    const totalLabel = form.querySelector('[data-total]');
    let selectedNumbers = [];
    let expectedQuantity = 0;
    let amount = 0;

    function setActivePackage(packageCount) {
        form.querySelectorAll('[data-package]').forEach((button) => {
            const isActive = Number(button.dataset.package) === Number(packageCount);
            button.classList.toggle('border-red-500', isActive);
            button.classList.toggle('bg-red-50', isActive);
            button.classList.toggle('text-red-700', isActive);
        });
    }

    function render() {
        selectedList.innerHTML = selectedNumbers.length
            ? selectedNumbers.map((number) => `<span class="rounded-xl bg-emerald-50 px-3 py-2 text-emerald-800 shadow-sm">${number}</span>`).join('')
            : '<span class="text-base font-bold text-slate-400">Ninguno</span>';

        hiddenNumbers.innerHTML = selectedNumbers
            .map((number) => `<input type="hidden" name="numbers[]" value="${number}">`)
            .join('');

        clearButton.hidden = selectedNumbers.length === 0;
        totalLabel.textContent = `Total: ${formatter.format(amount)}`;
    }

    async function takeRandom(packageCount, quantity, newAmount) {
        packageCountInput.value = packageCount;
        expectedQuantity = quantity;
        amount = newAmount;
        setActivePackage(packageCount);
        packageHelp.textContent = 'Generando numeros disponibles...';

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
        packageHelp.textContent = mode === 'manual'
            ? `Se asignaron ${quantity} numeros al azar. Puedes borrar la seleccion y escoger en la cuadricula.`
            : `El sistema asigno automaticamente ${quantity} numeros.`;
        render();
    }

    form.querySelectorAll('[data-package]').forEach((button) => {
        button.addEventListener('click', () => {
            takeRandom(Number(button.dataset.package), Number(button.dataset.quantity), Number(button.dataset.amount));
        });
    });

    form.querySelectorAll('[data-number-button]').forEach((button) => {
        button.addEventListener('click', () => {
            const number = button.dataset.numberButton;
            const baseQuantity = Number(form.querySelector('[data-package="1"]').dataset.quantity);
            selectedNumbers = selectedNumbers.includes(number)
                ? selectedNumbers.filter((selected) => selected !== number)
                : [...selectedNumbers, number];

            const packages = Math.min(5, Math.max(1, Math.ceil(selectedNumbers.length / baseQuantity)));
            const activePackage = form.querySelector(`[data-package="${packages}"]`);
            packageCountInput.value = packages;
            expectedQuantity = Number(activePackage.dataset.quantity);
            amount = Number(activePackage.dataset.amount);
            setActivePackage(packages);
            packageHelp.textContent = selectedNumbers.length === expectedQuantity
                ? `Seleccion completa de ${expectedQuantity} numeros.`
                : `Te falta ${expectedQuantity - selectedNumbers.length} numero(s) para completar la compra de ${expectedQuantity}.`;
            button.classList.toggle('border-red-500');
            button.classList.toggle('bg-red-50');
            button.classList.toggle('text-red-700');
            render();
        });
    });

    clearButton.addEventListener('click', () => {
        selectedNumbers = [];
        expectedQuantity = 0;
        amount = 0;
        packageHelp.textContent = mode === 'manual'
            ? 'Selecciona un paquete al azar o escoge manualmente en la cuadricula.'
            : 'Selecciona una cantidad para asignar numeros automaticamente.';
        form.querySelectorAll('[data-number-button]').forEach((button) => {
            button.classList.remove('border-red-500', 'bg-red-50', 'text-red-700');
        });
        form.querySelectorAll('[data-package]').forEach((button) => {
            button.classList.remove('border-red-500', 'bg-red-50', 'text-red-700');
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
});
