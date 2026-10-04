# Products REST API

[Versión en español](README.md)

REST API in **plain PHP** (compatible with PHP 5.6 to 8.x) on **MySQL**, with a **jQuery + Ajax + Bootstrap client** that consumes it and shows every HTTP request with its response. Field names and messages are in Spanish.

![Client](docs/capturas/1-cliente.png)

## Endpoints
| Method | Route | Description | Responses |
|---|---|---|---|
| GET | `/api/productos` | List with filters, sorting and pagination | 200 |
| GET | `/api/productos/{id}` | A single product | 200, 400, 404 |
| POST | `/api/productos` | Create a product | 201 + `Location`, 400, 401, 409, 415, 422 |
| PUT | `/api/productos/{id}` | Replace a product | 200, 401, 404, 409, 422 |
| DELETE | `/api/productos/{id}` | Delete a product | 204, 401, 404 |
| GET | `/api/categorias` | Categories with product counts | 200 |

**List parameters:** `q` (name or SKU), `categoria`, `activo` (`true`/`false`), `sin_stock=1`, `orden` (`nombre`, `precio`, `stock`, `id`; a leading `-` means descending), `pagina`, `por_pagina` (max 50).

**Writes:** POST, PUT and DELETE require the `X-API-Key` header. If the host blocks PUT or DELETE, send them as POST with `X-HTTP-Method-Override: PUT`.

**Errors:** always in the same shape:
```json
{ "error": { "codigo": 422, "mensaje": "Hay datos inválidos.", "detalles": { "precio": "Debe ser un número mayor a 0." } } }
```

### curl examples
```bash
curl "http://localhost:8000/api.php/productos?q=yerba&orden=-precio"

curl -X POST http://localhost:8000/api.php/productos \
  -H "Content-Type: application/json" -H "X-API-Key: demo-clave-portafolio" \
  -d '{"sku":"BEB-010","nombre":"Jugo de naranja 1 L","categoria":"Bebidas","precio":1850,"stock":30}'

curl -X DELETE http://localhost:8000/api.php/productos/11 -H "X-API-Key: demo-clave-portafolio"
```

## How it is built
- `api.php` is the single entry point (front controller): it reads the route from `PATH_INFO`, resolves resource and method, and returns the right HTTP status (including `405` with an `Allow` header).
- Object-oriented and PHP 5 compatible: `ProductoRepositorio` (data access with PDO prepared statements), `ProductoValidador` (validation and normalization) and `Respuesta` (JSON output).
- Sorting uses a **whitelist** of columns: client input is never concatenated into SQL.
- The API key is compared with `hash_equals` to prevent timing attacks.
- CORS enabled so it can be consumed from another domain.
- `.htaccess` rewrites `/api/productos/5` to `api.php/productos/5`.

## Automated tests
`tests/prueba_api.php` tests the API end to end (16 cases: listing, sorting, 401, broken JSON, 422, 201 + Location, 409, PUT, search, 405, 204, 404 and 400), with no external libraries:
```
$ php tests/prueba_api.php http://localhost:8000/api.php
  OK    GET lista responde 200
  ...
16 pruebas OK, 0 fallas
```

## Installation
**Local (demo mode):** `php -S localhost:8000` and open `http://localhost:8000/cliente/`. It creates `data/demo.sqlite` with 10 products.

**Server with MySQL:** run `sql/schema.sql`, fill in `includes/config.php` (and change `API_KEY`), and upload the files.

## Screenshots
| Client-side validation |
|---|
| ![](docs/capturas/2-validacion.png) |
