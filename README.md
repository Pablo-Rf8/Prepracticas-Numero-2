# Preprácticas número 2

API de usuarios, productos y ventas hecha con Laravel 13, PHP 8.4, Sanctum y SQLite. Una venta pertenece a un usuario y contiene uno o varios productos.

## Carpetas

| Carpeta | Contenido |
| --- | --- |
| `backend` | Laravel, rutas, controladores, modelos, servicios y pruebas. Aquí se ejecutan Artisan y Composer. |
| `frontend` | Vista básica, CSS, JavaScript, colección de Postman y contrato `openapi.json`. |
| `bd` | Base SQLite, migraciones, factories y seeders. |
| `scripts` | Activación del PHP local y arranque del servidor. |

La página de inicio permite consultar usuarios, productos y ventas en tablas de diez registros. Para las ventas se inicia sesión con una cuenta existente: el administrador ve todas y cada cliente ve las suyas. La interfaz consume las mismas rutas de la API. Los archivos de la vista están en `frontend/resources` y no requieren compilar ni ejecutar npm.

## Ejecutar en Visual Studio Code

Abrir `C:\Prepracticas-Numero-2`. En **Terminal → Run Task**, elegir **API: iniciar servidor**. El servidor queda en la terminal integrada.

- Inicio: <http://127.0.0.1:8002>
- Ejemplo público: <http://127.0.0.1:8002/api/public/products>

También se puede iniciar desde PowerShell, en la raíz:

```powershell
.\scripts\dev.ps1
```

Antes de ejecutar PHP o Composer manualmente, activar las herramientas de este proyecto:

```powershell
. .\scripts\Activate-Project.ps1
Set-Location backend
php artisan route:list --path=api
```

La carpeta privada `.tools` contiene PHP y Composer para esta computadora. En otra instalación se necesita PHP 8.4 con SQLite y Composer. Instalar las dependencias en `backend`, copiar `.env.example` a `.env`, generar la clave con `php artisan key:generate` y ejecutar las migraciones con `php artisan migrate`.

## Base de datos de práctica

La base local está en `bd/database.sqlite` y contiene:

| Tabla | Registros |
| --- | ---: |
| Usuarios | 100,000 |
| Productos | 1,000 |
| Ventas | 200,000 |
| Detalles de venta | 400,000 |

Los 100,000 usuarios incluyen una cuenta administradora. El resto son datos ficticios. Hay compras de un producto y compras de varios productos, relacionadas mediante claves foráneas.

Para repetir la carga, desde `backend`:

```powershell
php artisan demo:seed --users=100000 --products=1000 --sales=200000
```

El comando trabaja por lotes y reconoce los registros ya generados para evitar duplicar la misma carga. `php artisan db:seed` ejecuta la carga con esas cantidades por defecto. Requiere `DEMO_ADMIN_PASSWORD` de al menos 12 caracteres en `backend/.env`. Repetir la carga conserva los registros existentes; cambiar esa variable no cambia la contraseña de una cuenta ya creada.

## Rutas

Todas las rutas de la tabla parten de `http://127.0.0.1:8002/api`.

| Acceso | Método | Ruta | Función |
| --- | --- | --- | --- |
| Público | GET | `/public/users`, `/public/users/{id}` | Listar o consultar usuarios sin mostrar correo ni contraseña. |
| Público | GET | `/public/products`, `/public/products/{id}` | Listar o consultar productos. |
| Público | POST | `/auth/register` | Registrar un cliente y obtener un token. |
| Público | POST | `/auth/login` | Iniciar sesión y obtener un token. |
| Con token | GET | `/auth/me` | Consultar la cuenta autenticada. |
| Con token | POST | `/auth/logout` | Revocar el token actual. |
| Con token | GET | `/my/sales` | Consultar únicamente las compras de esa cuenta. |
| Administrador | GET, POST | `/private/users`, `/private/products`, `/private/sales` | Listar o crear registros. |
| Administrador | GET, PUT, PATCH, DELETE | `/private/users/{id}`, `/private/products/{id}`, `/private/sales/{id}` | Consultar, actualizar o eliminar un registro. |

Las listas tienen 10 resultados por página. Se puede usar `?page=2&per_page=10`; el máximo por página es 100. Incluyen `data`, `links` y `meta`.

Enviar `Accept: application/json` y, para escribir JSON, `Content-Type: application/json`. Las rutas privadas usan `Authorization: Bearer <token>`. Los tokens duran ocho horas. Un cliente recibe 403 si intenta administrar usuarios, productos o ventas; sin token se devuelve 401.

La cuenta administradora es `admin@prepracticas.test`. Su contraseña local está guardada en `backend/.env` y en el entorno privado `frontend/postman/Local.local.json`. Esos archivos están excluidos de Git. Las contraseñas de usuarios se guardan mediante hash y nunca se incluyen en las respuestas.

## Crear una venta

Ejemplo de cuerpo para `POST /private/sales` con token de administrador. Los identificadores deben existir:

```json
{
  "user_id": 2,
  "status": "paid",
  "notes": "Compra de práctica",
  "items": [
    { "product_id": 1, "quantity": 2 },
    { "product_id": 2, "quantity": 1 }
  ]
}
```

Los estados son `pending`, `paid` y `cancelled`. Los precios y totales son enteros en centavos de quetzal: `100` equivale a Q1.00. El servidor obtiene los precios y calcula el total; no acepta un total enviado por el cliente. Cada detalle conserva el precio de la compra. Un PATCH del estado no recalcula esos precios; enviar nuevos `items` reemplaza los detalles y calcula el total con los precios actuales.

PUT exige todos los campos principales del recurso; PATCH permite modificar solo algunos. En usuarios, cambiar la contraseña es opcional y revoca sus tokens. Los campos opcionales `notes` y `description` pueden enviarse con `null` para vaciarlos.

Los errores de validación devuelven 422 y los registros inexistentes 404. Eliminar un usuario o producto con ventas devuelve 409. También se impide eliminar la cuenta administradora. Eliminar una venta elimina sus detalles dentro de la relación de base de datos.

## Probar con Postman

1. Importar `frontend/postman/Proyecto2.postman_collection.json`.
2. Importar el entorno privado local `frontend/postman/Local.local.json` y seleccionarlo. Para compartir se usa `Local.postman_environment.json`, que deja la contraseña vacía.
3. Abrir la colección y ejecutar **Run** con todas sus peticiones en el orden guardado.

La colección inicia sesión, guarda los tokens durante la ejecución y prueba CRUD completo, permisos, validaciones, relaciones, cálculos y cierre de sesión. Crea sus propios registros temporales y los elimina al terminar correctamente. Debe ejecutarse contra una base de práctica. No publicar el entorno privado ni los tokens.

Desde VS Code también está la tarea **API: pruebas Postman**. Desde PowerShell, en la raíz y con el servidor activo:

```powershell
postman collection run frontend/postman/Proyecto2.postman_collection.json -e frontend/postman/Local.local.json --no-report-events
```

El contrato completo de rutas, cuerpos, respuestas y autenticación está en `frontend/openapi.json`.

## Pruebas realizadas

El 23 de octubre de 2026, contra la API local y la base cargada, Postman CLI completó **47 peticiones y 99 comprobaciones**, sin fallos. Al terminar se verificaron las cantidades de la base y la eliminación de los registros temporales.

Las pruebas de Laravel usan SQLite en memoria, separada de la base de práctica: **13 pruebas y 124 comprobaciones aprobadas**. Para ejecutarlas desde `backend`, después de activar PHP:

```powershell
php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml
```
