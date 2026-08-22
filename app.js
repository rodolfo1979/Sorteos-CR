const pageSize = 120;
const storageKey = "rifas-panel-state";
const money = new Intl.NumberFormat("es-CR", {
  style: "currency",
  currency: "CRC",
  maximumFractionDigits: 0,
});

const defaultState = {
  activeRaffleId: "rifa-demo",
  raffles: [
    {
      id: "rifa-demo",
      name: "Rifa Moto 2026",
      totalNumbers: 10000,
      price: 4000,
      numbersPerOrder: 2,
      maxRandomChanges: 5,
      reservationMinutes: 45,
      assignmentMode: "manual",
      saleEnabled: true,
      drawDate: "2026-09-30",
      prize: "Moto nueva y casco",
      imageData: "",
      organizerName: "Rifas CR",
      organizerWhatsapp: "8888-8888",
      paymentInfo: "Sube una imagen o captura de tu comprobante para validar tu participación.",
      rulesText:
        "Validación: es obligatorio subir la foto del comprobante.\nReserva: los números apartados quedan pendientes hasta validar el pago.\nSi el pago es rechazado, los números vuelven a estar disponibles.",
      numbers: {
        sold: ["00007", "00120", "01450"],
        reserved: ["00100", "00901"],
      },
    },
    {
      id: "rifa-azar",
      name: "Rifa Pantalla Smart",
      totalNumbers: 5000,
      price: 4000,
      numbersPerOrder: 1,
      maxRandomChanges: 5,
      reservationMinutes: 30,
      assignmentMode: "random",
      saleEnabled: true,
      drawDate: "2026-10-15",
      prize: "Pantalla 65 pulgadas",
      imageData: "",
      organizerName: "Rifas CR",
      organizerWhatsapp: "8888-8888",
      paymentInfo: "Sube una imagen o captura de tu comprobante para validar tu participación.",
      rulesText:
        "Validación: es obligatorio subir la foto del comprobante.\nReserva: los números apartados quedan pendientes hasta validar el pago.\nSi el pago es rechazado, los números vuelven a estar disponibles.",
      numbers: {
        sold: ["0001", "0300"],
        reserved: [],
      },
    },
  ],
  orders: [],
};

let state = loadState();
let selectedNumbers = [];
let currentPage = 1;
let randomChangesUsed = 0;
let packageCount = 1;
let inverseOffer = null;
const page = document.body.dataset.page;

function $(selector) {
  return document.querySelector(selector);
}

function escapeHTML(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function safeImageSource(value) {
  const source = String(value || "");
  return source.startsWith("data:image/") ? source : "";
}

function readImageFile(file) {
  return new Promise((resolve, reject) => {
    if (!file) {
      resolve("");
      return;
    }

    if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
      reject(new Error("La imagen debe ser JPG, PNG o WebP."));
      return;
    }

    if (file.size > 1024 * 1024) {
      reject(new Error("La imagen no debe superar 1 MB en este prototipo."));
      return;
    }

    const reader = new FileReader();
    reader.addEventListener("load", () => resolve(String(reader.result || "")));
    reader.addEventListener("error", () => reject(new Error("No se pudo leer la imagen.")));
    reader.readAsDataURL(file);
  });
}

function readReceiptFile(file) {
  return new Promise((resolve, reject) => {
    if (!file) {
      resolve({ name: "", type: "", data: "" });
      return;
    }

    const allowedTypes = ["image/jpeg", "image/png", "image/webp", "application/pdf"];
    if (!allowedTypes.includes(file.type)) {
      reject(new Error("El comprobante debe ser JPG, PNG, WebP o PDF."));
      return;
    }

    if (file.size > 2 * 1024 * 1024) {
      reject(new Error("El comprobante no debe superar 2 MB en este prototipo."));
      return;
    }

    const reader = new FileReader();
    reader.addEventListener("load", () =>
      resolve({
        name: file.name,
        type: file.type,
        data: String(reader.result || ""),
      }),
    );
    reader.addEventListener("error", () => reject(new Error("No se pudo leer el comprobante.")));
    reader.readAsDataURL(file);
  });
}

function renderReceiptPreview(order) {
  if (!order.receiptData) {
    return `<div class="receipt-preview empty-receipt">Sin vista previa del comprobante</div>`;
  }

  if (String(order.receiptType || "").startsWith("image/")) {
    return `
      <figure class="receipt-preview">
        <img src="${order.receiptData}" alt="Comprobante de ${escapeHTML(order.buyerName)}" />
        <figcaption>${escapeHTML(order.receiptName || "Comprobante")}</figcaption>
      </figure>
    `;
  }

  if (order.receiptType === "application/pdf") {
    return `
      <div class="receipt-preview pdf-receipt">
        <strong>PDF adjunto</strong>
        <span>${escapeHTML(order.receiptName || "Comprobante.pdf")}</span>
        <a class="action-button" href="${order.receiptData}" target="_blank" rel="noreferrer">Abrir PDF</a>
      </div>
    `;
  }

  return `<div class="receipt-preview empty-receipt">${escapeHTML(order.receiptName || "Comprobante adjunto")}</div>`;
}

function loadState() {
  const saved = localStorage.getItem(storageKey);
  const loaded = saved ? JSON.parse(saved) : defaultState;
  loaded.raffles = loaded.raffles.map((raffle) => ({
    ...raffle,
    maxRandomChanges: raffle.maxRandomChanges ?? 5,
    saleEnabled: raffle.saleEnabled ?? true,
    imageData: raffle.imageData || "",
    organizerName: raffle.organizerName || "Rifas CR",
    organizerWhatsapp: raffle.organizerWhatsapp || "8888-8888",
    paymentInfo: raffle.paymentInfo || "Sube una imagen o captura de tu comprobante para validar tu participación.",
    rulesText:
      raffle.rulesText ||
      "Validación: es obligatorio subir la foto del comprobante.\nReserva: los números apartados quedan pendientes hasta validar el pago.\nSi el pago es rechazado, los números vuelven a estar disponibles.",
    numbers: raffle.numbers || { sold: [], reserved: [] },
  }));
  loaded.orders = (loaded.orders || []).map((order) => ({
    buyerEmail: "",
    emailSent: false,
    packageCount: 1,
    receiptType: "",
    receiptData: "",
    ...order,
  }));
  return loaded;
}

function saveState() {
  localStorage.setItem(storageKey, JSON.stringify(state));
}

function activeRaffle() {
  return state.raffles.find((raffle) => raffle.id === state.activeRaffleId) || state.raffles[0];
}

function numberWidth(raffle) {
  return String(raffle.totalNumbers).length;
}

function formatNumber(number, raffle = activeRaffle()) {
  return String(number).padStart(numberWidth(raffle), "0");
}

function unavailableSet(raffle) {
  return new Set([...raffle.numbers.sold, ...raffle.numbers.reserved]);
}

function numberStatus(number, raffle = activeRaffle()) {
  const value = formatNumber(number, raffle);
  if (raffle.numbers.sold.includes(value)) return "sold";
  if (raffle.numbers.reserved.includes(value)) return "reserved";
  return "available";
}

function availableNumbers(raffle = activeRaffle()) {
  const unavailable = unavailableSet(raffle);
  const list = [];
  for (let number = 1; number <= raffle.totalNumbers; number += 1) {
    const value = formatNumber(number, raffle);
    if (!unavailable.has(value)) list.push(value);
  }
  return list;
}

function pickRandomNumbers(quantity, raffle = activeRaffle()) {
  const pool = availableNumbers(raffle);
  const picks = [];
  while (picks.length < quantity && pool.length) {
    const index = Math.floor(Math.random() * pool.length);
    picks.push(pool.splice(index, 1)[0]);
  }
  return picks;
}

function inverseNumber(value, raffle = activeRaffle()) {
  const padded = String(value).padStart(numberWidth(raffle), "0");
  if (padded.length < 3) return padded.split("").reverse().join("");

  const lastDigit = padded.slice(-1);
  const body = padded.slice(0, -1);
  const half = Math.floor(body.length / 2);
  return `${body.slice(half)}${body.slice(0, half)}${lastDigit}`;
}

function isAvailableValue(value, raffle = activeRaffle()) {
  const numeric = Number(value);
  if (!Number.isInteger(numeric) || numeric < 1 || numeric > raffle.totalNumbers) return false;
  if (selectedNumbers.includes(value)) return false;
  return !unavailableSet(raffle).has(value);
}

function findInverseOffer(raffle = activeRaffle()) {
  if (raffle.assignmentMode !== "random" || selectedNumbers.length < raffle.numbersPerOrder) return null;

  const inverse = selectedNumbers
    .map((number) => inverseNumber(number, raffle))
    .find((candidate) => isAvailableValue(candidate, raffle));

  if (!inverse) return null;

  const fillerPool = availableNumbers(raffle).filter(
    (number) => number !== inverse && !selectedNumbers.includes(number),
  );
  const filler = fillerPool[Math.floor(Math.random() * fillerPool.length)];

  if (!filler) return null;
  return { inverse, filler };
}

function orderLabel(order) {
  const raffle = state.raffles.find((item) => item.id === order.raffleId);
  return `${order.buyerName} - ${raffle?.name || "Rifa eliminada"}`;
}

function whatsappURL(phone, message = "") {
  const digits = String(phone || "").replace(/\D/g, "");
  const fullPhone = digits.length === 8 ? `506${digits}` : digits;
  return fullPhone ? `https://wa.me/${fullPhone}${message ? `?text=${encodeURIComponent(message)}` : ""}` : "#";
}

function drawBarChart(canvas, data) {
  if (!canvas) return;
  const ctx = canvas.getContext("2d");
  const { width, height } = canvas;
  ctx.clearRect(0, 0, width, height);

  const padding = 34;
  const max = Math.max(...data.map((item) => item.value), 1);
  const barGap = 18;
  const barWidth = Math.max(24, (width - padding * 2 - barGap * (data.length - 1)) / Math.max(data.length, 1));

  ctx.fillStyle = "#eef2f0";
  ctx.fillRect(0, 0, width, height);
  ctx.fillStyle = "#66726a";
  ctx.font = "13px Segoe UI";
  ctx.fillText("Monto aprobado", padding, 24);

  data.forEach((item, index) => {
    const x = padding + index * (barWidth + barGap);
    const chartHeight = height - 86;
    const barHeight = (item.value / max) * chartHeight;
    const y = height - 48 - barHeight;
    const gradient = ctx.createLinearGradient(0, y, 0, height - 48);
    gradient.addColorStop(0, "#0e7c66");
    gradient.addColorStop(1, "#78d5a7");

    ctx.fillStyle = gradient;
    roundRect(ctx, x, y, barWidth, barHeight, 8);
    ctx.fill();

    ctx.fillStyle = "#1f2a24";
    ctx.font = "700 12px Segoe UI";
    ctx.fillText(money.format(item.value), x, Math.max(42, y - 8));
    ctx.fillStyle = "#66726a";
    ctx.font = "12px Segoe UI";
    ctx.fillText(item.label.slice(0, 14), x, height - 22);
  });
}

function drawDonutChart(canvas, data) {
  if (!canvas) return;
  const ctx = canvas.getContext("2d");
  const { width, height } = canvas;
  ctx.clearRect(0, 0, width, height);

  const colors = ["#e7a12f", "#0e7c66", "#b93232"];
  const total = data.reduce((sum, item) => sum + item.value, 0) || 1;
  const cx = width / 2;
  const cy = height / 2 - 12;
  const radius = Math.min(width, height) * 0.28;
  let start = -Math.PI / 2;

  ctx.fillStyle = "#eef2f0";
  ctx.fillRect(0, 0, width, height);

  data.forEach((item, index) => {
    const angle = (item.value / total) * Math.PI * 2;
    ctx.beginPath();
    ctx.moveTo(cx, cy);
    ctx.arc(cx, cy, radius, start, start + angle);
    ctx.closePath();
    ctx.fillStyle = colors[index];
    ctx.fill();
    start += angle;
  });

  ctx.beginPath();
  ctx.arc(cx, cy, radius * 0.58, 0, Math.PI * 2);
  ctx.fillStyle = "#ffffff";
  ctx.fill();
  ctx.fillStyle = "#1f2a24";
  ctx.font = "800 22px Segoe UI";
  ctx.textAlign = "center";
  ctx.fillText(String(total === 1 && data.every((item) => item.value === 0) ? 0 : total), cx, cy + 7);
  ctx.textAlign = "left";

  data.forEach((item, index) => {
    const x = 22 + index * 112;
    const y = height - 28;
    ctx.fillStyle = colors[index];
    ctx.fillRect(x, y - 11, 12, 12);
    ctx.fillStyle = "#66726a";
    ctx.font = "12px Segoe UI";
    ctx.fillText(`${item.label}: ${item.value}`, x + 18, y);
  });
}

function roundRect(ctx, x, y, width, height, radius) {
  const safeRadius = Math.min(radius, width / 2, Math.abs(height) / 2);
  ctx.beginPath();
  ctx.moveTo(x + safeRadius, y);
  ctx.arcTo(x + width, y, x + width, y + height, safeRadius);
  ctx.arcTo(x + width, y + height, x, y + height, safeRadius);
  ctx.arcTo(x, y + height, x, y, safeRadius);
  ctx.arcTo(x, y, x + width, y, safeRadius);
  ctx.closePath();
}

function initPublicPage() {
  const els = {
    activeRaffleName: $("#activeRaffleName"),
    activeRaffleSelect: $("#activeRaffleSelect"),
    publicPrice: $("#publicPrice"),
    publicNumbersPerOrder: $("#publicNumbersPerOrder"),
    publicMode: $("#publicMode"),
    publicAvailable: $("#publicAvailable"),
    raffleImage: $("#raffleImage"),
    raffleImageWrap: $(".product-image-wrap"),
    publicPrize: $("#publicPrize"),
    publicDrawDate: $("#publicDrawDate"),
    saleProgressBar: $("#saleProgressBar"),
    saleProgressText: $("#saleProgressText"),
    publicOrganizerName: $("#publicOrganizerName"),
    organizerWhatsappLink: $("#organizerWhatsappLink"),
    topWhatsappLink: $("#topWhatsappLink"),
    stickyOrganizerName: $("#stickyOrganizerName"),
    stickyOrganizerPhone: $("#stickyOrganizerPhone"),
    stickyWhatsappLink: $("#stickyWhatsappLink"),
    publicPaymentInfo: $("#publicPaymentInfo"),
    publicRulesList: $("#publicRulesList"),
    selectionHint: $("#selectionHint"),
    purchaseForm: $("#purchaseForm"),
    buyerName: $("#buyerName"),
    buyerPhone: $("#buyerPhone"),
    buyerEmail: $("#buyerEmail"),
    paymentReceipt: $("#paymentReceipt"),
    selectedNumbersText: $("#selectedNumbersText"),
    purchaseTotalText: $("#purchaseTotalText"),
    manualPanel: $("#manualPanel"),
    randomPanel: $("#randomPanel"),
    numberSearch: $("#numberSearch"),
    numberGrid: $("#numberGrid"),
    prevPage: $("#prevPage"),
    nextPage: $("#nextPage"),
    pageInfo: $("#pageInfo"),
    randomChangesPill: $("#randomChangesPill"),
    randomNumbers: $("#randomNumbers"),
    inverseOffer: $("#inverseOffer"),
    randomHelp: $("#randomHelp"),
    generateRandomButton: $("#generateRandomButton"),
    changeRandomButton: $("#changeRandomButton"),
  };

  function renderPublic() {
    const raffle = activeRaffle();
    const soldPercent = Math.round((raffle.numbers.sold.length / raffle.totalNumbers) * 100);
    const whatsappLink = whatsappURL(
      raffle.organizerWhatsapp,
      `Hola, quiero participar en ${raffle.name}`,
    );
    els.activeRaffleName.textContent = raffle.name;
    els.publicPrice.textContent = money.format(raffle.price);
    els.publicNumbersPerOrder.textContent = raffle.numbersPerOrder;
    els.publicMode.textContent = raffle.assignmentMode === "manual" ? "Escoge cliente" : "Al azar";
    els.publicAvailable.textContent = availableNumbers(raffle).length.toLocaleString("es-CR");
    els.publicPrize.textContent = raffle.prize || "Premio por definir";
    els.publicDrawDate.textContent = raffle.drawDate
      ? `Sorteo: ${new Date(`${raffle.drawDate}T00:00:00`).toLocaleDateString("es-CR")}`
      : "Fecha del sorteo por definir";
    els.saleProgressBar.style.width = `${soldPercent}%`;
    els.saleProgressText.textContent = `${soldPercent}% vendido`;
    els.publicOrganizerName.textContent = raffle.organizerName;
    els.stickyOrganizerName.textContent = raffle.organizerName;
    els.stickyOrganizerPhone.textContent = raffle.organizerWhatsapp;
    els.organizerWhatsappLink.href = whatsappLink;
    els.topWhatsappLink.href = whatsappLink;
    els.stickyWhatsappLink.href = whatsappLink;
    els.publicPaymentInfo.textContent = raffle.paymentInfo;
    els.publicRulesList.innerHTML = raffle.rulesText
      .split("\n")
      .filter((line) => line.trim())
      .map((line) => `<li>${escapeHTML(line.trim())}</li>`)
      .join("");
    els.raffleImage.src = safeImageSource(raffle.imageData);
    els.raffleImage.hidden = !safeImageSource(raffle.imageData);
    els.raffleImageWrap.classList.toggle("empty-image", !safeImageSource(raffle.imageData));
    els.selectionHint.textContent =
      !raffle.saleEnabled
        ? "Venta pausada"
        : raffle.assignmentMode === "manual"
          ? "El cliente escoge"
          : "Sistema al azar";
    els.purchaseForm.classList.toggle("form-disabled", !raffle.saleEnabled);
    els.purchaseForm.querySelectorAll("input, button").forEach((field) => {
      field.disabled = !raffle.saleEnabled;
    });
    document.body.classList.toggle("mode-manual", raffle.assignmentMode === "manual");
    document.body.classList.toggle("mode-random", raffle.assignmentMode === "random");
    els.manualPanel.hidden = raffle.assignmentMode !== "manual";
    els.randomPanel.hidden = raffle.assignmentMode !== "random";

    renderRaffleSelect();
    if (raffle.assignmentMode === "random" && raffle.saleEnabled && !selectedNumbers.length) {
      assignRandomNumbers(false);
      return;
    }

    renderSelection();
    if (raffle.assignmentMode === "manual" && raffle.saleEnabled) {
      renderNumberGrid();
    } else {
      els.numberGrid.innerHTML = "";
      els.numberSearch.value = "";
      els.pageInfo.textContent = "";
    }
    if (raffle.assignmentMode === "random") renderRandomPanel();
  }

  function renderRaffleSelect() {
    els.activeRaffleSelect.innerHTML = state.raffles
      .map((raffle) => `<option value="${escapeHTML(raffle.id)}">${escapeHTML(raffle.name)}</option>`)
      .join("");
    els.activeRaffleSelect.value = state.activeRaffleId;
  }

  function renderNumberGrid() {
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

  function renderSelection() {
    const raffle = activeRaffle();
    const expected = raffle.numbersPerOrder * packageCount;
    els.selectedNumbersText.textContent = selectedNumbers.length
      ? `${selectedNumbers.join(", ")} (${selectedNumbers.length}/${expected})`
      : "Ninguno";
    els.purchaseTotalText.textContent = `Total: ${money.format(raffle.price * packageCount)}`;
  }

  function renderRandomPanel() {
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

  function renderInverseOffer() {
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

  function assignRandomNumbers(countAsChange) {
    const raffle = activeRaffle();
    if (!raffle.saleEnabled) {
      alert("La venta de esta rifa está pausada.");
      return;
    }

    const picks = pickRandomNumbers(raffle.numbersPerOrder, raffle);
    if (picks.length !== raffle.numbersPerOrder) {
      alert("No hay suficientes números disponibles para esta compra.");
      return;
    }
    selectedNumbers = picks;
    packageCount = 1;
    inverseOffer = null;
    if (countAsChange) randomChangesUsed += 1;
    renderSelection();
    renderRandomPanel();
  }

  els.activeRaffleSelect.addEventListener("change", () => {
    state.activeRaffleId = els.activeRaffleSelect.value;
    selectedNumbers = [];
    currentPage = 1;
    randomChangesUsed = 0;
    packageCount = 1;
    inverseOffer = null;
    saveState();
    renderPublic();
  });

  els.numberGrid.addEventListener("click", (event) => {
    if (!activeRaffle().saleEnabled) return;
    const button = event.target.closest(".number-button");
    if (!button || button.disabled) return;

    const raffle = activeRaffle();
    const value = button.dataset.number;
    if (selectedNumbers.includes(value)) {
      selectedNumbers = selectedNumbers.filter((number) => number !== value);
    } else if (selectedNumbers.length < raffle.numbersPerOrder) {
      selectedNumbers.push(value);
    }
    renderNumberGrid();
    renderSelection();
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
    renderSelection();
    renderRandomPanel();
  });

  els.purchaseForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const raffle = activeRaffle();
    if (!raffle.saleEnabled) {
      alert("La venta de esta rifa está pausada.");
      return;
    }

    if (selectedNumbers.length !== raffle.numbersPerOrder * packageCount) {
      alert(`Esta compra debe tener ${raffle.numbersPerOrder * packageCount} número(s).`);
      return;
    }

    let receipt;
    try {
      receipt = await readReceiptFile(els.paymentReceipt.files[0]);
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
    els.purchaseForm.reset();
    selectedNumbers = [];
    randomChangesUsed = 0;
    packageCount = 1;
    inverseOffer = null;
    window.location.href = `confirmacion.html?id=${orderId}`;
  });

  renderPublic();
}

function initAdminPage() {
  const els = {
    raffleForm: $("#raffleForm"),
    raffleId: $("#raffleId"),
    raffleName: $("#raffleName"),
    raffleImageInput: $("#raffleImageInput"),
    adminImagePreview: $("#adminImagePreview"),
    totalNumbers: $("#totalNumbers"),
    price: $("#price"),
    numbersPerOrder: $("#numbersPerOrder"),
    maxRandomChanges: $("#maxRandomChanges"),
    reservationMinutes: $("#reservationMinutes"),
    assignmentMode: $("#assignmentMode"),
    drawDate: $("#drawDate"),
    prize: $("#prize"),
    organizerName: $("#organizerName"),
    organizerWhatsapp: $("#organizerWhatsapp"),
    paymentInfo: $("#paymentInfo"),
    rulesText: $("#rulesText"),
    raffleList: $("#raffleList"),
    adminRevenue: $("#adminRevenue"),
    adminPending: $("#adminPending"),
    adminSold: $("#adminSold"),
    adminAvailable: $("#adminAvailable"),
    livePulse: $("#livePulse"),
    salesChart: $("#salesChart"),
    statusChart: $("#statusChart"),
    adminActivityList: $("#adminActivityList"),
  };

  function setDefaults() {
    if (els.raffleId.value) return;
    els.totalNumbers.value ||= 10000;
    els.price.value ||= 4000;
    els.numbersPerOrder.value ||= 1;
    els.maxRandomChanges.value ||= 5;
    els.reservationMinutes.value ||= 45;
    els.organizerName.value ||= "Rifas CR";
    els.organizerWhatsapp.value ||= "8888-8888";
    els.paymentInfo.value ||= "Sube una imagen o captura de tu comprobante para validar tu participación.";
    els.rulesText.value ||= "Validación: es obligatorio subir la foto del comprobante.\nReserva: los números apartados quedan pendientes hasta validar el pago.\nSi el pago es rechazado, los números vuelven a estar disponibles.";
  }

  function renderRaffleList() {
    els.raffleList.innerHTML = state.raffles
      .map((raffle) => {
        const sold = raffle.numbers.sold.length;
        const reserved = raffle.numbers.reserved.length;
        const mode = raffle.assignmentMode === "manual" ? "Cliente escoge" : "Sistema al azar";
        const image = safeImageSource(raffle.imageData)
          ? `<img src="${safeImageSource(raffle.imageData)}" alt="Premio de ${escapeHTML(raffle.name)}" />`
          : `<div class="image-placeholder">Sin fotografía</div>`;
        return `
          <article class="raffle-card">
            <header>
              ${image}
              <div>
                <h4>${escapeHTML(raffle.name)}</h4>
                <div class="meta">${mode} · ${raffle.totalNumbers.toLocaleString("es-CR")} números · ${money.format(raffle.price)} compra</div>
              </div>
              <div class="badge-stack">
                <span class="pill">${state.activeRaffleId === raffle.id ? "Principal" : "No principal"}</span>
                <span class="status-badge ${raffle.saleEnabled ? "approved" : "rejected"}">${raffle.saleEnabled ? "Venta activa" : "Venta pausada"}</span>
              </div>
            </header>
            <div class="meta">${raffle.numbersPerOrder} número(s) por compra · ${raffle.maxRandomChanges} cambio(s) al azar · ${raffle.reservationMinutes} min reserva</div>
            <div class="meta">Vendidos: ${sold} · Reservados: ${reserved} · Sorteo: ${raffle.drawDate || "Sin fecha"}</div>
            <div class="card-actions">
              <button class="action-button" data-edit="${escapeHTML(raffle.id)}" type="button">Editar</button>
              <button class="action-button" data-activate="${escapeHTML(raffle.id)}" type="button">Hacer principal</button>
              <button class="action-button ${raffle.saleEnabled ? "danger" : "approve"}" data-toggle-sale="${escapeHTML(raffle.id)}" type="button">${raffle.saleEnabled ? "Pausar venta" : "Reactivar venta"}</button>
            </div>
          </article>
        `;
      })
      .join("");
  }

  function adminMetrics() {
    const approvedOrders = state.orders.filter((order) => order.status === "approved");
    const pendingOrders = state.orders.filter((order) => order.status === "pending");
    const revenue = approvedOrders.reduce((sum, order) => sum + Number(order.price || 0), 0);
    const sold = state.raffles.reduce((sum, raffle) => sum + raffle.numbers.sold.length, 0);
    const available = state.raffles.reduce((sum, raffle) => sum + availableNumbers(raffle).length, 0);
    return { revenue, pending: pendingOrders.length, sold, available };
  }

  function renderAdminDashboard() {
    const metrics = adminMetrics();
    els.adminRevenue.textContent = money.format(metrics.revenue);
    els.adminPending.textContent = metrics.pending.toLocaleString("es-CR");
    els.adminSold.textContent = metrics.sold.toLocaleString("es-CR");
    els.adminAvailable.textContent = metrics.available.toLocaleString("es-CR");
    els.livePulse.textContent = `Actualizado ${new Date().toLocaleTimeString("es-CR", {
      hour: "2-digit",
      minute: "2-digit",
    })}`;

    renderSalesChart();
    renderStatusChart();
    renderActivity();
  }

  function renderSalesChart() {
    const data = state.raffles.map((raffle) => {
      const total = state.orders
        .filter((order) => order.status === "approved" && order.raffleId === raffle.id)
        .reduce((sum, order) => sum + Number(order.price || 0), 0);
      return { label: raffle.name, value: total };
    });

    drawBarChart(els.salesChart, data);
  }

  function renderStatusChart() {
    const counts = ["pending", "approved", "rejected"].map((status) => ({
      label: statusText(status),
      value: state.orders.filter((order) => order.status === status).length,
    }));
    drawDonutChart(els.statusChart, counts);
  }

  function renderActivity() {
    const recent = state.orders.slice().reverse().slice(0, 6);
    if (!recent.length) {
      els.adminActivityList.innerHTML = `<div class="empty">Aún no hay actividad de compradores.</div>`;
      return;
    }

    els.adminActivityList.innerHTML = recent
      .map((order) => {
        const raffle = state.raffles.find((item) => item.id === order.raffleId);
        return `
          <article>
            <div>
              <strong>${escapeHTML(order.buyerName || "Comprador")}</strong>
              <span>${escapeHTML(raffle?.name || "Rifa eliminada")} · ${escapeHTML(order.numbers.join(", "))}</span>
            </div>
            <span class="status-badge ${escapeHTML(order.status)}">${statusText(order.status)}</span>
          </article>
        `;
      })
      .join("");
  }

  els.raffleForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const id = els.raffleId.value || crypto.randomUUID();
    const existing = state.raffles.find((raffle) => raffle.id === id);
    let imageData = existing?.imageData || "";
    try {
      imageData = (await readImageFile(els.raffleImageInput.files[0])) || imageData;
    } catch (error) {
      alert(error.message);
      return;
    }

    const raffle = {
      id,
      name: els.raffleName.value.trim(),
      totalNumbers: Number(els.totalNumbers.value),
      price: Number(els.price.value),
      numbersPerOrder: Number(els.numbersPerOrder.value),
      maxRandomChanges: Number(els.maxRandomChanges.value),
      reservationMinutes: Number(els.reservationMinutes.value),
      assignmentMode: els.assignmentMode.value,
      saleEnabled: existing?.saleEnabled ?? true,
      drawDate: els.drawDate.value,
      prize: els.prize.value.trim(),
      imageData,
      organizerName: els.organizerName.value.trim() || "Rifas CR",
      organizerWhatsapp: els.organizerWhatsapp.value.trim() || "8888-8888",
      paymentInfo: els.paymentInfo.value.trim(),
      rulesText: els.rulesText.value.trim(),
      numbers: existing?.numbers || { sold: [], reserved: [] },
    };

    if (raffle.numbersPerOrder > raffle.totalNumbers) {
      alert("Los números por compra no pueden superar la cantidad total de números.");
      return;
    }

    if (existing) {
      state.raffles = state.raffles.map((item) => (item.id === id ? raffle : item));
    } else {
      state.raffles.push(raffle);
      state.activeRaffleId = raffle.id;
    }

    els.raffleForm.reset();
    els.raffleId.value = "";
    els.adminImagePreview.innerHTML = "";
    saveState();
    setDefaults();
    renderAdminDashboard();
    renderRaffleList();
  });

  els.raffleList.addEventListener("click", (event) => {
    const editId = event.target.dataset.edit;
    const activateId = event.target.dataset.activate;
    const toggleSaleId = event.target.dataset.toggleSale;

    if (toggleSaleId) {
      const raffle = state.raffles.find((item) => item.id === toggleSaleId);
      if (!raffle) return;
      raffle.saleEnabled = !raffle.saleEnabled;
      saveState();
      renderAdminDashboard();
      renderRaffleList();
      return;
    }

    if (activateId) {
      state.activeRaffleId = activateId;
      saveState();
      renderAdminDashboard();
      renderRaffleList();
    }

    if (editId) {
      const raffle = state.raffles.find((item) => item.id === editId);
      els.raffleId.value = raffle.id;
      els.raffleName.value = raffle.name;
      els.totalNumbers.value = raffle.totalNumbers;
      els.price.value = raffle.price;
      els.numbersPerOrder.value = raffle.numbersPerOrder;
      els.maxRandomChanges.value = raffle.maxRandomChanges;
      els.reservationMinutes.value = raffle.reservationMinutes;
      els.assignmentMode.value = raffle.assignmentMode;
      els.drawDate.value = raffle.drawDate;
      els.prize.value = raffle.prize;
      els.organizerName.value = raffle.organizerName;
      els.organizerWhatsapp.value = raffle.organizerWhatsapp;
      els.paymentInfo.value = raffle.paymentInfo;
      els.rulesText.value = raffle.rulesText;
      els.adminImagePreview.innerHTML = safeImageSource(raffle.imageData)
        ? `<img src="${safeImageSource(raffle.imageData)}" alt="Vista previa del premio" />`
        : `<div class="image-placeholder">Sin fotografía cargada</div>`;
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
  });

  els.raffleImageInput.addEventListener("change", async () => {
    try {
      const preview = await readImageFile(els.raffleImageInput.files[0]);
      els.adminImagePreview.innerHTML = preview
        ? `<img src="${preview}" alt="Vista previa del premio" />`
        : "";
    } catch (error) {
      els.raffleImageInput.value = "";
      alert(error.message);
    }
  });

  setDefaults();
  renderAdminDashboard();
  renderRaffleList();

  window.addEventListener("storage", (event) => {
    if (event.key !== storageKey) return;
    state = loadState();
    renderAdminDashboard();
    renderRaffleList();
  });

  setInterval(renderAdminDashboard, 4000);
}

function initPaymentsPage() {
  const paymentsList = $("#paymentsList");

  function renderPayments() {
    const pending = state.orders.filter((order) => order.status === "pending");
    if (!pending.length) {
      paymentsList.innerHTML = `<div class="empty">No hay comprobantes pendientes.</div>`;
      return;
    }

    paymentsList.innerHTML = pending
      .map((order) => {
        const raffle = state.raffles.find((item) => item.id === order.raffleId);
        return `
          <article class="payment-card">
            <header>
              <div>
                <h4>${escapeHTML(orderLabel(order))}</h4>
                <div class="meta">${escapeHTML(order.buyerPhone)} · ${escapeHTML(order.buyerEmail)}</div>
              </div>
              <span class="pill">${money.format(order.price)}</span>
            </header>
            <div>Números: <strong>${escapeHTML(order.numbers.join(", "))}</strong></div>
            <div class="meta">Modo: ${order.assignmentMode === "random" ? "Al azar" : "Manual"} · Comprobante: ${escapeHTML(order.receiptName || "No adjuntado")} · Rifa: ${escapeHTML(raffle?.name || "Rifa eliminada")}</div>
            ${renderReceiptPreview(order)}
            <div class="card-actions">
              <button class="action-button approve" data-approve="${escapeHTML(order.id)}" type="button">Aprobar y enviar correo</button>
              <button class="action-button danger" data-reject="${escapeHTML(order.id)}" type="button">Rechazar</button>
            </div>
          </article>
        `;
      })
      .join("");
  }

  paymentsList.addEventListener("click", (event) => {
    const approveId = event.target.dataset.approve;
    const rejectId = event.target.dataset.reject;
    const orderId = approveId || rejectId;
    if (!orderId) return;

    const order = state.orders.find((item) => item.id === orderId);
    if (!order) return;
    const raffle = state.raffles.find((item) => item.id === order.raffleId);
    if (!raffle) return;

    raffle.numbers.reserved = raffle.numbers.reserved.filter((number) => !order.numbers.includes(number));
    if (approveId) {
      raffle.numbers.sold.push(...order.numbers);
      order.status = "approved";
      order.emailSent = true;
      order.emailPreview = `Correo enviado a ${order.buyerEmail}: pago aprobado para ${raffle.name}, números ${order.numbers.join(", ")}.`;
      alert(order.emailPreview);
    } else {
      order.status = "rejected";
      order.emailSent = false;
      alert(`Pago rechazado para ${order.buyerName}. Los números fueron liberados.`);
    }

    saveState();
    renderPayments();
  });

  renderPayments();
}

function initConfirmationPage() {
  const detail = $("#confirmationDetail");
  const params = new URLSearchParams(window.location.search);
  const order = state.orders.find((item) => item.id === params.get("id"));

  if (!order) {
    detail.innerHTML = `
      <div class="empty">No se encontró esta orden. Puedes volver a la página de venta y registrar una nueva compra.</div>
    `;
    return;
  }

  const raffle = state.raffles.find((item) => item.id === order.raffleId);
  detail.innerHTML = `
    <div class="section-title">
      <div>
        <p class="eyebrow">Orden ${order.id.slice(0, 8)}</p>
        <h3>${escapeHTML(raffle?.name || "Rifa")}</h3>
      </div>
      <span class="status-badge ${order.status}">${statusText(order.status)}</span>
    </div>
    <div class="summary-grid compact-summary">
      <article>
        <span>Comprador</span>
        <strong>${escapeHTML(order.buyerName)}</strong>
      </article>
      <article>
        <span>Monto</span>
        <strong>${money.format(order.price)}</strong>
      </article>
      <article>
        <span>Comprobante</span>
        <strong>${escapeHTML(order.receiptName || "Adjunto")}</strong>
      </article>
      <article>
        <span>Correo</span>
        <strong>${escapeHTML(order.buyerEmail)}</strong>
      </article>
    </div>
    <div class="selected-box number-confirmation">
      <span>Números reservados</span>
      <strong>${escapeHTML(order.numbers.join(", "))}</strong>
    </div>
    <p class="meta">Cuando administración valide el pago, el sistema enviará un correo de confirmación a ${escapeHTML(order.buyerEmail)}.</p>
  `;
}

function initReportsPage() {
  const reportSummary = $("#reportSummary");
  const reportFilter = $("#reportFilter");
  const reportList = $("#reportList");

  function renderReports() {
    const orders = state.orders;
    const approved = orders.filter((order) => order.status === "approved");
    const pending = orders.filter((order) => order.status === "pending");
    const rejected = orders.filter((order) => order.status === "rejected");
    const revenue = approved.reduce((sum, order) => sum + order.price, 0);

    reportSummary.innerHTML = `
      <article>
        <span>Aprobadas</span>
        <strong>${approved.length}</strong>
      </article>
      <article>
        <span>Pendientes</span>
        <strong>${pending.length}</strong>
      </article>
      <article>
        <span>Rechazadas</span>
        <strong>${rejected.length}</strong>
      </article>
      <article>
        <span>Ventas aprobadas</span>
        <strong>${money.format(revenue)}</strong>
      </article>
    `;

    const selected = reportFilter.value;
    const visible = selected === "all" ? orders : orders.filter((order) => order.status === selected);

    if (!visible.length) {
      reportList.innerHTML = `<div class="empty">No hay ordenes para este filtro.</div>`;
      return;
    }

    reportList.innerHTML = visible
      .slice()
      .reverse()
      .map((order) => {
        const raffle = state.raffles.find((item) => item.id === order.raffleId);
        return `
          <article class="payment-card">
            <header>
              <div>
                <h4>${escapeHTML(orderLabel(order))}</h4>
                <div class="meta">${new Date(order.createdAt).toLocaleString("es-CR")} · ${escapeHTML(raffle?.name || "Rifa eliminada")}</div>
              </div>
              <span class="status-badge ${order.status}">${statusText(order.status)}</span>
            </header>
            <div>Números: <strong>${escapeHTML(order.numbers.join(", "))}</strong></div>
            <div class="meta">${escapeHTML(order.buyerPhone)} · ${escapeHTML(order.buyerEmail)} · ${money.format(order.price)} · ${escapeHTML(order.receiptName || "Sin nombre de archivo")}</div>
            <div class="meta">${order.emailSent ? "Correo de confirmación enviado" : "Correo no enviado"}</div>
          </article>
        `;
      })
      .join("");
  }

  reportFilter.addEventListener("change", renderReports);
  renderReports();
}

function statusText(status) {
  const labels = {
    pending: "Pendiente",
    approved: "Aprobado",
    rejected: "Rechazado",
    available: "Disponible",
    reserved: "Reservado",
    sold: "Vendido",
  };
  return labels[status] || status;
}

function initNumbersPage() {
  const pageSizeNumbers = 100;
  let pageNumber = 1;
  const els = {
    summary: $("#numbersSummary"),
    raffleFilter: $("#numbersRaffleFilter"),
    statusFilter: $("#numbersStatusFilter"),
    search: $("#numbersSearch"),
    table: $("#numbersTable"),
    prev: $("#numbersPrevPage"),
    next: $("#numbersNextPage"),
    pageInfo: $("#numbersPageInfo"),
  };

  function renderRaffleOptions() {
    els.raffleFilter.innerHTML = state.raffles
      .map((raffle) => `<option value="${escapeHTML(raffle.id)}">${escapeHTML(raffle.name)}</option>`)
      .join("");
    els.raffleFilter.value = state.activeRaffleId;
  }

  function selectedRaffle() {
    return state.raffles.find((raffle) => raffle.id === els.raffleFilter.value) || activeRaffle();
  }

  function orderForNumber(raffle, number) {
    return state.orders.find(
      (order) =>
        order.raffleId === raffle.id &&
        order.numbers.includes(number) &&
        ["pending", "approved"].includes(order.status),
    );
  }

  function buildRows() {
    const raffle = selectedRaffle();
    const sold = new Set(raffle.numbers.sold);
    const reserved = new Set(raffle.numbers.reserved);
    const status = els.statusFilter.value;
    const search = els.search.value.trim();

    const rows = [];
    if (search) {
      const number = search.padStart(numberWidth(raffle), "0");
      if (Number(search) >= 1 && Number(search) <= raffle.totalNumbers) {
        rows.push(rowForNumber(raffle, number, sold, reserved));
      }
      return rows.filter((row) => status === "all" || row.status === status);
    }

    for (let value = 1; value <= raffle.totalNumbers; value += 1) {
      const number = formatNumber(value, raffle);
      const row = rowForNumber(raffle, number, sold, reserved);
      if (status === "all" || row.status === status) rows.push(row);
    }

    return rows;
  }

  function rowForNumber(raffle, number, sold, reserved) {
    const status = sold.has(number) ? "sold" : reserved.has(number) ? "reserved" : "available";
    const order = status === "available" ? null : orderForNumber(raffle, number);
    return {
      number,
      status,
      buyerName: order?.buyerName || "",
      buyerPhone: order?.buyerPhone || "",
      orderId: order?.id || "",
      createdAt: order?.createdAt || "",
    };
  }

  function renderSummary(rows) {
    const raffle = selectedRaffle();
    const sold = raffle.numbers.sold.length;
    const reserved = raffle.numbers.reserved.length;
    const available = Math.max(0, raffle.totalNumbers - sold - reserved);
    const soldPercent = Math.round((sold / raffle.totalNumbers) * 100);
    els.summary.innerHTML = `
      <article>
        <span>Vendidos</span>
        <strong>${sold.toLocaleString("es-CR")}</strong>
      </article>
      <article>
        <span>Reservados</span>
        <strong>${reserved.toLocaleString("es-CR")}</strong>
      </article>
      <article>
        <span>Disponibles</span>
        <strong>${available.toLocaleString("es-CR")}</strong>
      </article>
      <article>
        <span>Resultado filtro</span>
        <strong>${rows.length.toLocaleString("es-CR")}</strong>
      </article>
      <article>
        <span>Avance vendido</span>
        <strong>${soldPercent}%</strong>
      </article>
    `;
  }

  function renderTable() {
    const rows = buildRows();
    const totalPages = Math.max(1, Math.ceil(rows.length / pageSizeNumbers));
    pageNumber = Math.min(pageNumber, totalPages);
    const visible = rows.slice((pageNumber - 1) * pageSizeNumbers, pageNumber * pageSizeNumbers);
    renderSummary(rows);

    if (!visible.length) {
      els.table.innerHTML = `<div class="empty">No hay números para este filtro.</div>`;
    } else {
      els.table.innerHTML = `
        <table>
          <thead>
            <tr>
              <th>Número</th>
              <th>Estado</th>
              <th>Comprador</th>
              <th>Teléfono</th>
              <th>Orden</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
            ${visible
              .map(
                (row) => `
                  <tr>
                    <td><strong>${escapeHTML(row.number)}</strong></td>
                    <td><span class="status-badge ${escapeHTML(row.status)}">${statusText(row.status)}</span></td>
                    <td>${escapeHTML(row.buyerName || "-")}</td>
                    <td>${escapeHTML(row.buyerPhone || "-")}</td>
                    <td>${row.orderId ? `#${escapeHTML(row.orderId.slice(0, 8))}` : "-"}</td>
                    <td>${row.createdAt ? new Date(row.createdAt).toLocaleString("es-CR") : "-"}</td>
                  </tr>
                `,
              )
              .join("")}
          </tbody>
        </table>
      `;
    }

    els.pageInfo.textContent = `Página ${pageNumber} de ${totalPages}`;
    els.prev.disabled = pageNumber <= 1;
    els.next.disabled = pageNumber >= totalPages;
  }

  function resetAndRender() {
    pageNumber = 1;
    renderTable();
  }

  els.raffleFilter.addEventListener("change", resetAndRender);
  els.statusFilter.addEventListener("change", resetAndRender);
  els.search.addEventListener("input", resetAndRender);
  els.prev.addEventListener("click", () => {
    pageNumber = Math.max(1, pageNumber - 1);
    renderTable();
  });
  els.next.addEventListener("click", () => {
    pageNumber += 1;
    renderTable();
  });

  renderRaffleOptions();
  renderTable();
}

if (page === "public") initPublicPage();
if (page === "admin") initAdminPage();
if (page === "payments") initPaymentsPage();
if (page === "confirmation") initConfirmationPage();
if (page === "reports") initReportsPage();
if (page === "numbers") initNumbersPage();
