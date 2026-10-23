const elements = Object.fromEntries([
    'title', 'total', 'message', 'table-container', 'headings', 'rows', 'empty',
    'pagination', 'page', 'previous', 'next', 'login', 'login-form', 'email',
    'password', 'show-password', 'sign-in', 'session', 'session-name', 'sign-out',
].map((id) => [id, document.getElementById(id)]));

const sections = { users: 'Usuarios', products: 'Productos', sales: 'Ventas' };
const money = new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'GTQ' });
const number = new Intl.NumberFormat('es-GT');
const date = new Intl.DateTimeFormat('es-GT');
const statuses = { pending: 'Pendiente', paid: 'Pagada', cancelled: 'Cancelada' };
let section = 'users';
let page = 1;
let lastPage = 1;
let token = null;
let account = null;
let listRequest = null;

function showMessage(text, error = false) {
    elements.message.textContent = text;
    elements.message.classList.toggle('error', error);
}

function renderSession() {
    elements.login.hidden = Boolean(account);
    elements.session.hidden = !account;
    elements['session-name'].textContent = account ? account.name : '';
}

async function request(path, options = {}) {
    const response = await fetch(`/api${path}`, {
        ...options,
        headers: {
            Accept: 'application/json',
            ...(options.body ? { 'Content-Type': 'application/json' } : {}),
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
    });
    const data = response.status === 204 ? null : await response.json();
    if (!response.ok) {
        if (response.status === 401 && token) {
            token = null;
            account = null;
            renderSession();
        }
        const error = new Error(data?.errors
            ? Object.values(data.errors).flat().join(' ')
            : data?.message || 'No se pudo completar la solicitud.');
        error.status = response.status;
        throw error;
    }
    return data;
}

function columns() {
    if (section === 'users') {
        const fields = [['ID', (row) => row.id], ['Nombre', (row) => row.name]];
        if (account?.is_admin) {
            fields.push(['Correo', (row) => row.email]);
        }
        fields.push(['Registro', (row) => date.format(new Date(row.created_at))]);
        return fields;
    }
    if (section === 'products') {
        return [
            ['ID', (row) => row.id], ['Producto', (row) => row.name],
            ['Código', (row) => row.sku], ['Precio', (row) => money.format(row.price_cents / 100)],
            ['Estado', (row) => row.active ? 'Activo' : 'Inactivo'],
        ];
    }
    return [
        ['ID', (row) => row.id], ['Usuario', (row) => row.user.name],
        ['Productos', (row) => row.items.map((item) => `${item.product.name} × ${item.quantity}`).join('\n')],
        ['Total', (row) => money.format(row.total_cents / 100)],
        ['Estado', (row) => statuses[row.status] || row.status],
    ];
}

function renderRows(data) {
    const fields = columns();
    elements.rows.closest('table').dataset.section = section;
    elements.headings.replaceChildren();
    for (const [label] of fields) {
        const heading = document.createElement('th');
        heading.scope = 'col';
        heading.textContent = label;
        elements.headings.append(heading);
    }
    const rows = data.map((record) => {
        const row = document.createElement('tr');
        for (const [label, value] of fields) {
            const cell = document.createElement('td');
            cell.dataset.label = label;
            if (['Total', 'Precio', 'Estado', 'ID'].includes(label)) {
                cell.classList.add('compact');
            }
            if (label === 'Productos') {
                cell.classList.add('sale-products');
            }
            cell.textContent = value(record);
            row.append(cell);
        }
        return row;
    });
    elements.rows.replaceChildren(...rows);
    elements.empty.hidden = data.length > 0;
}

async function loadRecords() {
    listRequest?.abort();
    const controller = new AbortController();
    listRequest = controller;
    elements.title.textContent = sections[section];
    elements.total.textContent = '';
    elements['table-container'].hidden = true;
    elements.pagination.hidden = true;
    for (const button of document.querySelectorAll('[data-section]')) {
        button.setAttribute('aria-pressed', String(button.dataset.section === section));
    }
    if (section === 'sales' && !account) {
        showMessage('Iniciá sesión para consultar las ventas.');
        elements.login.open = true;
        return;
    }
    showMessage('Cargando…');
    let path = account?.is_admin ? `/private/${section}` : `/public/${section}`;
    if (section === 'sales' && !account?.is_admin) {
        path = '/my/sales';
    }
    try {
        const result = await request(`${path}?page=${page}&per_page=10`, { signal: controller.signal });
        if (controller.signal.aborted) {
            return;
        }
        renderRows(result.data);
        page = result.meta.current_page;
        lastPage = result.meta.last_page;
        elements.total.textContent = `${number.format(result.meta.total)} ${result.meta.total === 1 ? 'registro' : 'registros'}`;
        elements.page.textContent = `Página ${number.format(page)} de ${number.format(lastPage)}`;
        elements.previous.disabled = page <= 1;
        elements.next.disabled = page >= lastPage;
        elements['table-container'].hidden = false;
        elements.pagination.hidden = result.meta.total === 0;
        showMessage('');
    } catch (error) {
        if (error.name !== 'AbortError') {
            showMessage(error.status === 401 ? 'Tu sesión venció. Volvé a iniciar sesión.' : error.message, true);
        }
    }
}

for (const button of document.querySelectorAll('[data-section]')) {
    button.addEventListener('click', () => {
        section = button.dataset.section;
        page = 1;
        loadRecords();
    });
}

elements.previous.addEventListener('click', () => {
    page = Math.max(1, page - 1);
    loadRecords();
});
elements.next.addEventListener('click', () => {
    page = Math.min(lastPage, page + 1);
    loadRecords();
});
elements['show-password'].addEventListener('click', () => {
    const visible = elements.password.type === 'password';
    elements.password.type = visible ? 'text' : 'password';
    elements['show-password'].textContent = visible ? 'Ocultar' : 'Mostrar';
    elements['show-password'].setAttribute('aria-pressed', String(visible));
});

elements['login-form'].addEventListener('submit', async (event) => {
    event.preventDefault();
    elements['sign-in'].disabled = true;
    showMessage('Iniciando sesión…');
    try {
        const result = await request('/auth/login', {
            method: 'POST',
            body: JSON.stringify({ email: elements.email.value, password: elements.password.value }),
        });
        token = result.token;
        account = result.data;
        elements['login-form'].reset();
        elements.password.type = 'password';
        elements['show-password'].textContent = 'Mostrar';
        elements['show-password'].setAttribute('aria-pressed', 'false');
        renderSession();
        page = 1;
        await loadRecords();
    } catch (error) {
        showMessage(error.status === 422 ? 'El correo o la contraseña no son correctos.' : error.message, true);
    } finally {
        elements['sign-in'].disabled = false;
    }
});

elements['sign-out'].addEventListener('click', async () => {
    elements['sign-out'].disabled = true;
    listRequest?.abort();
    try {
        await request('/auth/logout', { method: 'POST' });
        token = null;
        account = null;
        renderSession();
        section = 'users';
        page = 1;
        await loadRecords();
    } catch (error) {
        if (error.status === 401) {
            await loadRecords();
        } else {
            showMessage(error.message, true);
        }
    } finally {
        elements['sign-out'].disabled = false;
    }
});

loadRecords();
