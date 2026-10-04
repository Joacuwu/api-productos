<?php
/**
 * API REST de productos.
 *
 *   GET    /api/productos             lista (q, categoria, activo, sin_stock, orden, pagina, por_pagina)
 *   GET    /api/productos/{id}        un producto
 *   POST   /api/productos             crear          (requiere X-API-Key)
 *   PUT    /api/productos/{id}        reemplazar     (requiere X-API-Key)
 *   DELETE /api/productos/{id}        eliminar       (requiere X-API-Key)
 *   GET    /api/categorias            categorías con cantidad de productos
 *
 * Sin reescritura de URLs también funciona como /api.php/productos/{id}.
 */
require_once dirname(__FILE__) . '/includes/db.php';
require_once dirname(__FILE__) . '/includes/Respuesta.php';
require_once dirname(__FILE__) . '/includes/ProductoRepositorio.php';
require_once dirname(__FILE__) . '/includes/ProductoValidador.php';

// CORS: permite consumir la API desde otro dominio (por ejemplo, el portafolio)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, X-HTTP-Method-Override');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

$metodo = $_SERVER['REQUEST_METHOD'];
// Algunos hostings compartidos bloquean PUT y DELETE: se aceptan como POST + X-HTTP-Method-Override
if ($metodo === 'POST' && isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
    $override = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
    if (in_array($override, array('PUT', 'DELETE'), true)) {
        $metodo = $override;
    }
}
if ($metodo === 'OPTIONS') {
    Respuesta::sinContenido();
}

$ruta = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$partes = $ruta === '' ? array() : explode('/', $ruta);
$recurso = isset($partes[0]) ? $partes[0] : '';
$id = isset($partes[1]) ? $partes[1] : null;

if ($id !== null && !ctype_digit($id)) {
    Respuesta::error(400, 'El id debe ser un número entero.');
}

function requiere_clave()
{
    $clave = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
    if (!hash_equals(API_KEY, $clave)) {
        Respuesta::error(401, 'Falta la clave de API o es incorrecta (encabezado X-API-Key).');
    }
}

function leer_json()
{
    $tipo = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (stripos($tipo, 'application/json') !== 0) {
        Respuesta::error(415, 'El cuerpo debe enviarse como application/json.');
    }
    $datos = json_decode(file_get_contents('php://input'), true);
    if (!is_array($datos)) {
        Respuesta::error(400, 'JSON inválido.');
    }
    return $datos;
}

function base_url()
{
    $script = dirname($_SERVER['SCRIPT_NAME']);
    return rtrim($script, '/') . '/api';
}

try {
    $repo = new ProductoRepositorio(db());

    if ($recurso === 'categorias' && $id === null) {
        if ($metodo !== 'GET') {
            Respuesta::error(405, 'Método no permitido.', array(), array('Allow' => 'GET'));
        }
        Respuesta::json(array('datos' => $repo->categorias()));
    }

    if ($recurso !== 'productos') {
        Respuesta::error(404, 'Recurso no encontrado. Probá con /api/productos.');
    }

    // Colección: /productos
    if ($id === null) {
        if ($metodo === 'GET') {
            $pagina = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
            $porPagina = isset($_GET['por_pagina']) ? (int) $_GET['por_pagina'] : 10;
            $porPagina = min(POR_PAGINA_MAX, max(1, $porPagina));
            $filtros = array(
                'q'         => isset($_GET['q']) ? trim($_GET['q']) : '',
                'categoria' => isset($_GET['categoria']) ? trim($_GET['categoria']) : '',
                'activo'    => isset($_GET['activo']) ? ($_GET['activo'] === 'true' || $_GET['activo'] === '1' ? 1 : 0) : '',
                'sin_stock' => !empty($_GET['sin_stock']),
            );
            $orden = isset($_GET['orden']) ? $_GET['orden'] : 'nombre';
            $r = $repo->listar($filtros, $orden, $pagina, $porPagina);

            Respuesta::json(array(
                'datos' => $r['datos'],
                'meta'  => array(
                    'total'      => $r['total'],
                    'pagina'     => $pagina,
                    'por_pagina' => $porPagina,
                    'paginas'    => (int) ceil($r['total'] / $porPagina),
                ),
            ));
        }

        if ($metodo === 'POST') {
            requiere_clave();
            $v = new ProductoValidador();
            if (!$v->validar(leer_json())) {
                Respuesta::error(422, 'Hay datos inválidos.', $v->errores());
            }
            $d = $v->datos();
            if ($repo->existeSku($d['sku'])) {
                Respuesta::error(409, 'Ya existe un producto con el SKU ' . $d['sku'] . '.');
            }
            $nuevo = $repo->crear($d);
            Respuesta::json(array('datos' => $nuevo), 201, array('Location' => base_url() . '/productos/' . $nuevo['id']));
        }

        Respuesta::error(405, 'Método no permitido.', array(), array('Allow' => 'GET, POST'));
    }

    // Elemento: /productos/{id}
    $producto = $repo->buscar($id);
    if ($producto === null) {
        Respuesta::error(404, 'No existe el producto ' . $id . '.');
    }

    switch ($metodo) {
        case 'GET':
            Respuesta::json(array('datos' => $producto));
            break;

        case 'PUT':
            requiere_clave();
            $v = new ProductoValidador();
            if (!$v->validar(leer_json())) {
                Respuesta::error(422, 'Hay datos inválidos.', $v->errores());
            }
            $d = $v->datos();
            if ($repo->existeSku($d['sku'], $id)) {
                Respuesta::error(409, 'Ya existe otro producto con el SKU ' . $d['sku'] . '.');
            }
            Respuesta::json(array('datos' => $repo->actualizar($id, $d)));
            break;

        case 'DELETE':
            requiere_clave();
            $repo->eliminar($id);
            Respuesta::sinContenido();
            break;

        default:
            Respuesta::error(405, 'Método no permitido.', array(), array('Allow' => 'GET, PUT, DELETE'));
    }
} catch (Exception $e) {
    error_log('api.php: ' . $e->getMessage());
    Respuesta::error(500, 'Error interno del servidor.');
}
