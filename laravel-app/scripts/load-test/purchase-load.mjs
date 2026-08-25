const baseUrl = (process.env.BASE_URL || 'https://sorteoscr.morpho3d.com').replace(/\/$/, '');
const totalUsers = Number(process.env.USERS || 25);
const concurrency = Number(process.env.CONCURRENCY || 5);
const packageCount = Number(process.env.PACKAGE_COUNT || 1);
const requestTimeoutMs = Number(process.env.TIMEOUT_MS || 20000);
const pauseMs = Number(process.env.PAUSE_MS || 250);

const stats = {
    ok: 0,
    failed: 0,
    conflicts: 0,
    validation: 0,
    insufficient: 0,
    busy: 0,
    redirects: 0,
    durations: [],
    errors: new Map(),
};

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

function rememberError(message) {
    stats.errors.set(message, (stats.errors.get(message) || 0) + 1);
}

function decodeHtml(value) {
    return String(value || '')
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&quot;/g, '"')
        .replace(/&#039;/g, "'")
        .replace(/&nbsp;/g, ' ');
}

function validationMessages(body) {
    if (!body.includes('Revisa la solicitud')) {
        return [];
    }

    return [...body.matchAll(/<li[^>]*>([\s\S]*?)<\/li>/gi)]
        .map((match) => decodeHtml(match[1].replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim()))
        .filter(Boolean)
        .slice(0, 3);
}

function classifyResponseBody(body) {
    if (body.includes('SQLSTATE') || body.includes('Operation not permitted')) {
        stats.busy += 1;
        return 'Servidor ocupado: error transitorio de base de datos';
    }

    if (body.includes('Estamos procesando muchas compras')) {
        stats.busy += 1;
        return 'Servidor ocupado: reintentar en segundos';
    }

    if (body.includes('Actualizamos la disponibilidad') || body.includes('ya no estan disponibles')) {
        stats.conflicts += 1;
        return 'Conflicto de disponibilidad';
    }

    const messages = validationMessages(body);
    if (messages.length) {
        stats.validation += 1;
        return `Validacion en formulario: ${messages.join(' | ')}`;
    }

    if (body.includes('Debes subir')
        || body.includes('Debes seleccionar')
        || body.includes('correo electronico valido')
        || body.includes('El comprobante debe')) {
        stats.validation += 1;
        return 'Validacion en formulario sin lista de errores';
    }

    return null;
}

function extract(pattern, text, label) {
    const match = text.match(pattern);
    if (!match) {
        throw new Error(`No se pudo encontrar ${label}`);
    }

    return match.slice(1).find(Boolean).replace(/&amp;/g, '&');
}

function mergeCookies(current, response) {
    const setCookie = response.headers.get('set-cookie');
    if (!setCookie) {
        return current;
    }

    const jar = new Map();
    current.split(';').map((item) => item.trim()).filter(Boolean).forEach((item) => {
        const [name, ...value] = item.split('=');
        jar.set(name, value.join('='));
    });

    setCookie.split(/,(?=\s*[^;,]+=)/).forEach((cookie) => {
        const first = cookie.split(';')[0]?.trim();
        if (!first) {
            return;
        }
        const [name, ...value] = first.split('=');
        jar.set(name, value.join('='));
    });

    return [...jar.entries()].map(([name, value]) => `${name}=${value}`).join('; ');
}

async function timedFetch(url, options = {}) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), requestTimeoutMs);

    try {
        return await fetch(url, { ...options, signal: controller.signal });
    } finally {
        clearTimeout(timeout);
    }
}

async function getCheckoutContext() {
    let cookies = '';
    const response = await timedFetch(baseUrl, {
        headers: { Accept: 'text/html' },
    });
    cookies = mergeCookies(cookies, response);

    if (!response.ok) {
        throw new Error(`La pagina de venta respondio HTTP ${response.status}`);
    }

    const html = await response.text();
    const csrf = extract(/<meta[^>]*name="csrf-token"[^>]*content="([^"]+)"|<input[^>]*name="_token"[^>]*value="([^"]+)"/, html, 'token CSRF');
    const formMatch = html.match(/<form[^>]*data-raffle-purchase[^>]*>/);
    if (!formMatch) {
        throw new Error('No se pudo encontrar el formulario publico de compra');
    }

    const formTag = formMatch[0];
    const randomUrl = new URL(extract(/data-random-url="([^"]+)"/, formTag, 'URL de azar'), baseUrl).toString();
    const purchaseUrl = new URL(extract(/action="([^"]+)"/, formTag, 'URL de compra'), baseUrl).toString();

    return { cookies, csrf, randomUrl, purchaseUrl };
}

async function buyTicket(index) {
    const started = performance.now();
    let context;

    try {
        context = await getCheckoutContext();

        const randomResponse = await timedFetch(context.randomUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': context.csrf,
                Cookie: context.cookies,
            },
            body: JSON.stringify({ package_count: packageCount }),
        });
        context.cookies = mergeCookies(context.cookies, randomResponse);

        const randomData = await randomResponse.json().catch(() => ({}));
        const expectedQuantity = Number(randomData.expected_quantity || randomData.quantity || 0);

        if (randomResponse.status === 409) {
            stats.insufficient += 1;
            rememberError(`Disponibilidad insuficiente en azar: ${randomData.quantity || 0}/${expectedQuantity || 'desconocido'} numero(s)`);
            return;
        }

        if (randomResponse.status === 503) {
            stats.busy += 1;
            rememberError(`Azar ocupado: ${randomData.message || 'reintentar'}`);
            return;
        }

        if (!randomResponse.ok) {
            throw new Error(`Azar HTTP ${randomResponse.status}`);
        }

        const numbers = randomData.numbers || [];
        if (!numbers.length) {
            stats.insufficient += 1;
            rememberError('Disponibilidad insuficiente en azar: 0 numeros');
            return;
        }

        if (expectedQuantity > 0 && numbers.length !== expectedQuantity) {
            stats.insufficient += 1;
            rememberError(`Disponibilidad insuficiente en azar: ${numbers.length}/${expectedQuantity} numero(s)`);
            return;
        }

        await sleep(pauseMs);

        const form = new FormData();
        form.set('_token', context.csrf);
        form.set('buyer_name', `Carga Cliente ${index}`);
        form.set('buyer_phone', `7000${String(index).padStart(4, '0')}`);
        form.set('buyer_email', `carga${index}@example.com`);
        form.set('package_count', String(packageCount));
        form.set('random_changes_used', '0');
        form.set('selection_source', 'random');
        numbers.forEach((number) => form.append('numbers[]', number));
        form.set('receipt', new Blob(['%PDF-1.4\n% load-test receipt\n'], { type: 'application/pdf' }), `comprobante-${index}.pdf`);

        const purchaseResponse = await timedFetch(context.purchaseUrl, {
            method: 'POST',
            headers: {
                Accept: 'text/html,application/xhtml+xml',
                Cookie: context.cookies,
            },
            body: form,
            redirect: 'manual',
        });
        context.cookies = mergeCookies(context.cookies, purchaseResponse);

        const location = purchaseResponse.headers.get('location') || '';
        const duration = performance.now() - started;
        stats.durations.push(duration);

        if ([302, 303].includes(purchaseResponse.status) && location.includes('/confirmacion/')) {
            stats.ok += 1;
            return;
        }

        if ([302, 303].includes(purchaseResponse.status)) {
            stats.redirects += 1;
            const redirectUrl = location ? new URL(location, baseUrl).toString() : baseUrl;

            try {
                const redirectedResponse = await timedFetch(redirectUrl, {
                    headers: {
                        Accept: 'text/html,application/xhtml+xml',
                        Cookie: context.cookies,
                    },
                });
                context.cookies = mergeCookies(context.cookies, redirectedResponse);
                const redirectedBody = await redirectedResponse.text();
                const reason = classifyResponseBody(redirectedBody) || `HTTP ${redirectedResponse.status} sin mensaje reconocido`;
                rememberError(`Redireccion al formulario motivo=${reason}`);
            } catch (redirectError) {
                const message = redirectError instanceof Error ? redirectError.message : String(redirectError);
                rememberError(`Redireccion al formulario sin clasificar: ${message}`);
            }

            return;
        }

        const body = await purchaseResponse.text();
        const reason = classifyResponseBody(body);
        if (reason) {
            rememberError(reason);
            return;
        }

        throw new Error(`Compra HTTP ${purchaseResponse.status} location=${location || 'sin-location'}`);
    } catch (error) {
        stats.failed += 1;
        rememberError(error instanceof Error ? error.message : String(error));
    }
}

async function runPool() {
    let next = 1;

    async function worker() {
        while (next <= totalUsers) {
            const index = next;
            next += 1;
            await buyTicket(index);
            process.stdout.write('.');
        }
    }

    const started = performance.now();
    await Promise.all(Array.from({ length: Math.min(concurrency, totalUsers) }, () => worker()));
    const totalDuration = performance.now() - started;
    process.stdout.write('\n');

    const sorted = [...stats.durations].sort((a, b) => a - b);
    const percentile = (p) => sorted.length ? sorted[Math.min(sorted.length - 1, Math.floor((p / 100) * sorted.length))] : 0;

    console.log('\nResultado de carga');
    console.log('------------------');
    console.log(`URL: ${baseUrl}`);
    console.log(`Usuarios simulados: ${totalUsers}`);
    console.log(`Concurrencia: ${concurrency}`);
    console.log(`Paquetes por compra: ${packageCount}`);
    console.log(`Duracion total: ${(totalDuration / 1000).toFixed(2)}s`);
    console.log(`Compras creadas: ${stats.ok}`);
    console.log(`Conflictos controlados: ${stats.conflicts}`);
    console.log(`Validaciones: ${stats.validation}`);
    console.log(`Disponibilidad insuficiente: ${stats.insufficient}`);
    console.log(`Servidor ocupado: ${stats.busy}`);
    console.log(`Redirecciones al formulario: ${stats.redirects}`);
    console.log(`Fallos tecnicos: ${stats.failed}`);
    console.log(`p50: ${percentile(50).toFixed(0)}ms`);
    console.log(`p90: ${percentile(90).toFixed(0)}ms`);
    console.log(`p95: ${percentile(95).toFixed(0)}ms`);

    if (stats.errors.size) {
        console.log('\nErrores agrupados');
        [...stats.errors.entries()].sort((a, b) => b[1] - a[1]).forEach(([message, count]) => {
            console.log(`- ${count}x ${message}`);
        });
    }

    if (stats.failed > 0) {
        process.exitCode = 1;
    }
}

console.log(`Iniciando prueba contra ${baseUrl} con ${totalUsers} usuarios y concurrencia ${concurrency}...`);
runPool();
