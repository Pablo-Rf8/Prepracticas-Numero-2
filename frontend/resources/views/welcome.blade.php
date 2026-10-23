<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proyecto 2</title>
    <link rel="stylesheet" href="{{ route('frontend.styles') }}">
    <script src="{{ route('frontend.script') }}" defer></script>
</head>
<body>
<main>
    <header>
        <div>
            <h1>Proyecto 2</h1>
            <p class="muted">Usuarios, productos y ventas</p>
        </div>
        <div id="session" hidden>
            <p id="session-name"></p>
            <button id="sign-out" type="button">Cerrar sesión</button>
        </div>
    </header>

    <nav aria-label="Secciones">
        <button type="button" data-section="users" aria-pressed="true">Usuarios</button>
        <button type="button" data-section="products" aria-pressed="false">Productos</button>
        <button type="button" data-section="sales" aria-pressed="false">Ventas</button>
    </nav>

    <details id="login">
        <summary>Iniciar sesión</summary>
        <form id="login-form">
            <div>
                <label for="email">Correo electrónico</label>
                <input id="email" type="email" autocomplete="username" required>
            </div>
            <div>
                <label for="password">Contraseña</label>
                <div class="password">
                    <input id="password" type="password" autocomplete="current-password" required>
                    <button id="show-password" type="button" aria-controls="password" aria-pressed="false">Mostrar</button>
                </div>
            </div>
            <button id="sign-in" class="primary" type="submit">Entrar</button>
        </form>
    </details>

    <section aria-labelledby="title">
        <div class="section-heading">
            <h2 id="title">Usuarios</h2>
            <span id="total" class="muted"></span>
        </div>
        <p id="message" role="status" aria-live="polite"></p>
        <div id="table-container" class="table-container" hidden>
            <table aria-labelledby="title">
                <caption id="empty" hidden>No hay registros para mostrar.</caption>
                <thead><tr id="headings"></tr></thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <footer id="pagination" hidden>
            <p id="page"></p>
            <div class="pagination">
                <button id="previous" type="button">Anterior</button>
                <button id="next" type="button">Siguiente</button>
            </div>
        </footer>
    </section>
    <noscript>Activá JavaScript para consultar los registros.</noscript>
</main>
</body>
</html>
