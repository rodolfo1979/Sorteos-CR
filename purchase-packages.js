(() => {
  if (document.body.dataset.page !== "public") return;

  const maxPackages = 5;
  let selectionSource = "manual";
  const packageStyles = document.createElement("style");
  packageStyles.textContent = `
    .package-selector {
      border: 1px solid var(--line);
      border-radius: 8px;
      background: #ffffff;
      padding: 14px;
      display: grid;
      gap: 10px;
    }

    .package-selector > span {
      color: var(--muted);
      font-size: 0.85rem;
      font-weight: 800;
    }

    .package-options {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(118px, 1fr));
      gap: 8px;
    }

    .package-option {
      border: 1px solid var(--line);
      border-radius: 8px;
      background: #f8faf7;
      color: var(--ink);
      cursor: pointer;
      font-weight: 900;
      min-height: 54px;
      padding: 10px 8px;
      white-space: normal;
    }

    .package-option.active {
      background: var(--accent);
      border-color: var(--accent);
      color: #ffffff;
    }

    .package-selector small {
      color: var(--muted);
      line-height: 1.45;
    }

    .clear-selection-button {
      border: 1px solid rgba(185, 50, 50, 0.22);
      border-radius: 8px;
      background: #ffe4e4;
      color: var(--danger);
      cursor: pointer;
      font-weight: 900;
      padding: 10px 12px;
    }

    .clear-selection-button[hidden] {
      display: none !important;
    }

    @media (max-width: 560px) {
      .package-options {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }
  `;
  document.head.appendChild(packageStyles);

  function getEls() {
    return {
      form: document.querySelector("#purchaseForm"),
      receipt: document.querySelector("#paymentReceipt"),
      buyerName: document.querySelector("#buyerName"),
      buyerPhone: document.querySelector("#buyerPhone"),
      buyerEmail: document.querySelector("#buyerEmail"),
      selectedNumbersText: document.querySelector("#selectedNumbersText"),
      purchaseTotalText: document.querySelector("#purchaseTotalText"),
      numberGrid: document.querySelector("#numberGrid"),
      numberSearch: document.querySelector("#numberSearch"),
      prevPage: document.querySelector("#prevPage"),
      nextPage: document.querySelector("#nextPage"),
      pageInfo: document.querySelector("#pageInfo"),
      packageOptions: document.querySelector("#packageOptions"),
      packageHelp: document.querySelector("#packageHelp"),
      clearSelectionButton: document.querySelector("#clearSelectionButton"),
      randomChangesPill: document.querySelector("#randomChangesPill"),
      randomNumbers: document.querySelector("#randomNumbers"),
      inverseOffer: document.querySelector("#inverseOffer"),
      randomHelp: document.querySelector("#randomHelp"),
      generateRandomButton: document.querySelector("#generateRandomButton"),
      changeRandomButton: document.querySelector("#changeRandomButton"),
    };
  }

  function replaceNode(selector) {
    const node = document.querySelector(selector);
    if (!node || node.dataset.packagePatch === "true") return;
    const clone = node.cloneNode(true);
    clone.dataset.packagePatch = "true";
    node.replaceWith(clone);
  }

    function installClearSelectionButton() {
    const help = document.querySelector("#packageHelp");
    if (!help || document.querySelector("#clearSelectionButton")) return;
    help.insertAdjacentHTML(
      "afterend",
      `<button id="clearSelectionButton" class="clear-selection-button" type="button" hidden>Eliminar selección</button>`,
    );
  }
function detachOriginalPublicHandlers() {
    installClearSelectionButton();
    [
      "#purchaseForm",
      "#numberGrid",
      "#numberSearch",
      "#prevPage",
      "#nextPage",
      "#generateRandomButton",
      "#changeRandomButton",
      "#inverseOffer",
    ].forEach(replaceNode);
  }

  function expectedSelectionCount(raffle = activeRaffle()) {
    return Math.max(1, raffle.numbersPerOrder) * packageCount;
  }

  function packageLabel(raffle, count) {
    const quantity = Math.max(1, raffle.numbersPerOrder) * count;
    return `${quantity} número${quantity === 1 ? "" : "s"}`;
  }

  function normalizePackageForSelection(raffle = activeRaffle()) {
    const base = Math.max(1, raffle.numbersPerOrder);
    const needed = Math.max(1, Math.ceil(selectedNumbers.length / base));
    packageCount = Math.min(maxPackages, Math.max(packageCount, needed));
  }

  function renderPackageOptions() {
    const els = getEls();
    const raffle = activeRaffle();
    if (!els.packageOptions || !els.packageHelp) return;

    els.packageOptions.innerHTML = Array.from({ length: maxPackages }, (_, index) => index + 1)
      .map(
        (count) => `
          <button class="package-option ${count === packageCount ? "active" : ""}" data-package-count="${count}" type="button">
            ${raffle.assignmentMode === "manual" ? `Azar ${packageLabel(raffle, count)}` : packageLabel(raffle, count)}
          </button>
        `,
      )
      .join("");

        els.packageHelp.textContent = raffle.assignmentMode === "random"
      ? `Al escoger una opción, el sistema asigna automáticamente ${expectedSelectionCount(raffle)} número(s). Total actual: ${money.format(raffle.price * packageCount)}.`
      : `Puedes escoger en la cuadrícula o tomar ${expectedSelectionCount(raffle)} número(s) al azar. Total actual: ${money.format(raffle.price * packageCount)}.`;
  }

  function renderSelection() {
    const els = getEls();
    const raffle = activeRaffle();
    const expected = expectedSelectionCount(raffle);
    els.selectedNumbersText.textContent = selectedNumbers.length
      ? `${selectedNumbers.join(", ")} (${selectedNumbers.length}/${expected})`
      : "Ninguno";
    els.purchaseTotalText.textContent = `Total: ${money.format(raffle.price * packageCount)}`;
    if (els.clearSelectionButton) els.clearSelectionButton.hidden = !selectedNumbers.length;
  }

  function renderNumberGrid() {
    const els = getEls();
    const raffle = activeRaffle();
    if (raffle.assignmentMode !== "manual" || !raffle.saleEnabled) {
      els.numberGrid.innerHTML = "";
      return;
    }

    const search = els.numberSearch.value.trim();
    const totalPages = Math.max(1, Math.ceil(raffle.totalNumbers / pageSize));
    currentPage = Math.min(currentPage, totalPages);

    let numbers = [];
    if (search) {
      const exact = Number(search);
      if (Number.isInteger(exact) && exact >= 1 && exact <= raffle.totalNumbers) numbers = [exact];
    } else {
      const start = (currentPage - 1) * pageSize + 1;
      const end = Math.min(start + pageSize - 1, raffle.totalNumbers);
      for (let number = start; number <= end; number += 1) numbers.push(number);
    }

    els.numberGrid.innerHTML = numbers
      .map((number) => {
        const value = formatNumber(number, raffle);
        const status = numberStatus(number, raffle);
        const selected = selectedNumbers.includes(value);
        const disabled = status !== "available";
        return `<button class="number-button ${status} ${selected ? "selected" : ""}" data-number="${value}" ${disabled ? "disabled" : ""}>${value}</button>`;
      })
      .join("");

    if (!numbers.length) {
      els.numberGrid.innerHTML = `<div class="empty">No se encontró ese número en esta rifa.</div>`;
    }

    els.pageInfo.textContent = search ? "Resultado de búsqueda" : `Página ${currentPage} de ${totalPages}`;
    els.prevPage.disabled = currentPage <= 1 || Boolean(search);
    els.nextPage.disabled = currentPage >= totalPages || Boolean(search);
  }

  function renderInverseOffer() {
    const els = getEls();
    const raffle = activeRaffle();
    inverseOffer = packageCount === 1 ? findInverseOffer(raffle) : null;

    if (raffle.assignmentMode !== "random" || !selectedNumbers.length || !raffle.saleEnabled) {
      els.inverseOffer.hidden = true;
      els.inverseOffer.innerHTML = "";
      return;
    }

    const inverseRows = selectedNumbers
      .map((number) => {
        const inverse = inverseNumber(number, raffle);
        const available = isAvailableValue(inverse, raffle);
        if (!available) return "";

        return `
          <li>
            <span>${escapeHTML(number)} → ${escapeHTML(inverse)}</span>
            <strong class="available-text">disponible</strong>
          </li>
        `;
      })
      .filter(Boolean);

    if (!inverseOffer || !inverseRows.length) {
      els.inverseOffer.hidden = true;
      els.inverseOffer.innerHTML = "";
      return;
    }

    els.inverseOffer.hidden = false;
    els.inverseOffer.innerHTML = `
      <div>
        <strong>Inverso disponible: ${escapeHTML(inverseOffer.inverse)}</strong>
        <ul class="inverse-list">${inverseRows.join("")}</ul>
        <p class="meta">Puedes agregar el inverso y otro número al azar por ${money.format(raffle.price)} adicionales. Total: ${money.format(raffle.price * 2)}.</p>
      </div>
      <button class="action-button approve" id="acceptInverseButton" type="button">Agregar inverso</button>
    `;
  }

  function renderRandomPanel() {
    const els = getEls();
    const raffle = activeRaffle();
    const remainingChanges = Math.max(0, raffle.maxRandomChanges - randomChangesUsed);
    els.randomChangesPill.textContent = `${remainingChanges} cambio(s)`;
    els.randomNumbers.innerHTML = selectedNumbers.length
      ? selectedNumbers.map((number) => `<span>${number}</span>`).join("")
      : "Presiona generar para recibir tus números.";
    els.generateRandomButton.hidden = selectedNumbers.length > 0;
    els.generateRandomButton.disabled = !raffle.saleEnabled;
    els.changeRandomButton.disabled = !raffle.saleEnabled || !selectedNumbers.length || remainingChanges <= 0;
    els.randomHelp.textContent = !raffle.saleEnabled
      ? "La venta de esta rifa está pausada por administración."
      : selectedNumbers.length
        ? `Puedes cambiar este grupo ${remainingChanges} vez/veces más antes de enviar el comprobante.`
        : `Esta rifa permite cambiar los números hasta ${raffle.maxRandomChanges} vez/veces.`;
    renderInverseOffer();
  }

  function renderPatchedPublic() {
    const els = getEls();
    const raffle = activeRaffle();
    renderPackageOptions();
    renderSelection();

    els.form.classList.toggle("form-disabled", !raffle.saleEnabled);
    els.form.querySelectorAll("input, button").forEach((field) => {
      field.disabled = !raffle.saleEnabled;
    });

    if (raffle.assignmentMode === "manual") {
      renderNumberGrid();
    }

    if (raffle.assignmentMode === "random") {
      if (raffle.saleEnabled && !selectedNumbers.length) assignRandomNumbers(false);
      renderRandomPanel();
    }
  }

  function assignRandomNumbers(countAsChange) {
    const raffle = activeRaffle();
    if (!raffle.saleEnabled) {
      alert("La venta de esta rifa está pausada.");
      return;
    }

    const quantity = expectedSelectionCount(raffle);
    const picks = pickRandomNumbers(quantity, raffle);
    if (picks.length !== quantity) {
      alert(`No hay suficientes números disponibles para asignar ${quantity} número(s).`);
      return;
    }

    selectedNumbers = picks;
    selectionSource = "random";
    inverseOffer = null;
    if (countAsChange) randomChangesUsed += 1;
    renderPackageOptions();
    renderSelection();
    renderRandomPanel();
  }

  function attachPatchedHandlers() {
    const els = getEls();

    els.packageOptions.addEventListener("click", (event) => {
      const button = event.target.closest("[data-package-count]");
      if (!button) return;

      const raffle = activeRaffle();
      packageCount = Number(button.dataset.packageCount);
      inverseOffer = null;

      assignRandomNumbers(false);
      if (raffle.assignmentMode === "manual") renderNumberGrid();
      return;
    });

    els.numberGrid.addEventListener("click", (event) => {
      const raffle = activeRaffle();
      if (!raffle.saleEnabled) return;
      const button = event.target.closest(".number-button");
      if (!button || button.disabled) return;

      const value = button.dataset.number;
      const maxNumbers = Math.max(1, raffle.numbersPerOrder) * maxPackages;
      if (selectedNumbers.includes(value)) {
        selectedNumbers = selectedNumbers.filter((number) => number !== value);
      } else if (selectedNumbers.length < maxNumbers) {
        selectedNumbers.push(value);
        selectionSource = "manual";
        normalizePackageForSelection(raffle);
      } else {
        alert(`Esta compra permite hasta ${maxNumbers} número(s).`);
      }

      renderPatchedPublic();
    });

    els.prevPage.addEventListener("click", () => {
      if (activeRaffle().assignmentMode !== "manual") return;
      currentPage = Math.max(1, currentPage - 1);
      renderNumberGrid();
    });

    els.nextPage.addEventListener("click", () => {
      if (activeRaffle().assignmentMode !== "manual") return;
      currentPage += 1;
      renderNumberGrid();
    });

    els.numberSearch.addEventListener("input", () => {
      if (activeRaffle().assignmentMode === "manual") renderNumberGrid();
    });

    els.generateRandomButton.addEventListener("click", () => assignRandomNumbers(false));
    els.changeRandomButton.addEventListener("click", () => assignRandomNumbers(true));

    els.inverseOffer.addEventListener("click", (event) => {
      if (!event.target.closest("#acceptInverseButton") || !inverseOffer) return;
      selectedNumbers = [...selectedNumbers, inverseOffer.inverse, inverseOffer.filler];
      packageCount = 2;
      inverseOffer = null;
      renderPatchedPublic();
    });

        els.clearSelectionButton?.addEventListener("click", () => {
      selectedNumbers = [];
      inverseOffer = null;
      selectionSource = "manual";
      renderPatchedPublic();
    });
els.form.addEventListener("submit", async (event) => {
      event.preventDefault();
      const raffle = activeRaffle();
      if (!raffle.saleEnabled) {
        alert("La venta de esta rifa está pausada.");
        return;
      }

      const expected = expectedSelectionCount(raffle);
      if (selectedNumbers.length !== expected) {
        const missing = expected - selectedNumbers.length;
        if (missing > 0) {
          alert(`Te falta ${missing} número(s) para completar la compra de ${expected} número(s).`);
        } else {
          alert(`Esta compra debe tener exactamente ${expected} número(s).`);
        }
        return;
      }

      let receipt;
      try {
        receipt = await readReceiptFile(els.receipt.files[0]);
      } catch (error) {
        alert(error.message);
        return;
      }

      const orderId = crypto.randomUUID();
      raffle.numbers.reserved.push(...selectedNumbers);
      state.orders.push({
        id: orderId,
        raffleId: raffle.id,
        buyerName: els.buyerName.value.trim(),
        buyerPhone: els.buyerPhone.value.trim(),
        buyerEmail: els.buyerEmail.value.trim(),
        receiptName: receipt.name,
        receiptType: receipt.type,
        receiptData: receipt.data,
        numbers: [...selectedNumbers],
        price: raffle.price * packageCount,
        packageCount,
        assignmentMode: raffle.assignmentMode,
        randomChangesUsed,
        status: "pending",
        emailSent: false,
        createdAt: new Date().toISOString(),
      });

      saveState();
      els.form.reset();
      selectedNumbers = [];
      selectionSource = "manual";
      randomChangesUsed = 0;
      packageCount = 1;
      inverseOffer = null;
      window.location.href = `confirmacion.html?id=${orderId}`;
    });

    document.querySelector("#activeRaffleSelect").addEventListener("change", () => {
      setTimeout(renderPatchedPublic, 0);
    });
  }

  detachOriginalPublicHandlers();
  attachPatchedHandlers();
  renderPatchedPublic();
})();


