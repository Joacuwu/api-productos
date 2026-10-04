<?php
/**
 * Pruebas de punta a punta de la API. No necesita librerías.
 * Uso: php tests/prueba_api.php http://localhost:8000/api.php
 */
if (PHP_SAPI !== 'cli') {
    exit('Solo por consola.');
}
require_once dirname(dirname(__FILE__)) . '/includes/config.php';

$base = isset($argv[1]) ? rtrim($argv[1], '/') : 'http://localhost:8000/api.php';
$ok = 0;
$fallas = 0;

function pedir($metodo, $url, $cuerpo = null, $conClave = true)
{
    $encabezados = array('Accept: application/json');
    if ($conClave) {
        $encabezados[] = 'X-API-Key: ' . API_KEY;
    }
    if ($cuerpo !== null) {
        $encabezados[] = 'Content-Type: application/json';
    }
    $contexto = stream_context_create(array('http' => array(
        'method'        => $metodo,
        'header'        => implode("\r\n", $encabezados),
        'content'       => $cuerpo === null ? '' : (is_string($cuerpo) ? $cuerpo : json_encode($cuerpo)),
        'ignore_errors' => true,
    )));
    $respuesta = file_get_contents($url, false, $contexto);
    preg_match('/\s(\d{3})\s/', $http_response_header[0], $m);
    return array('codigo' => (int) $m[1], 'json' => json_decode($respuesta, true), 'encabezados' => $http_response_header);
}

function comprobar($descripcion, $condicion)
{
    global $ok, $fallas;
    if ($condicion) {
        $ok++;
        echo "  OK    $descripcion\n";
    } else {
        $fallas++;
        echo "  FALLA $descripcion\n";
    }
}

$sku = 'TST-' . mt_rand(100000, 999999);
$producto = array('sku' => $sku, 'nombre' => 'Producto de prueba', 'categoria' => 'Pruebas', 'precio' => 1500.5, 'stock' => 3);

echo "Probando $base\n";

$r = pedir('GET', "$base/productos?por_pagina=5&orden=-precio");
comprobar('GET lista responde 200', $r['codigo'] === 200);
comprobar('GET lista respeta por_pagina', count($r['json']['datos']) <= 5);
comprobar('GET lista ordena por precio descendente',
    count($r['json']['datos']) < 2 || $r['json']['datos'][0]['precio'] >= $r['json']['datos'][1]['precio']);

$r = pedir('POST', "$base/productos", $producto, false);
comprobar('POST sin clave responde 401', $r['codigo'] === 401);

$r = pedir('POST', "$base/productos", '{"sku": "ROTO"', true);
comprobar('POST con JSON roto responde 400', $r['codigo'] === 400);

$r = pedir('POST', "$base/productos", array('sku' => 'x', 'nombre' => 'a', 'precio' => -1));
comprobar('POST inválido responde 422 con errores por campo',
    $r['codigo'] === 422 && isset($r['json']['error']['detalles']['sku'], $r['json']['error']['detalles']['precio']));

$r = pedir('POST', "$base/productos", $producto);
comprobar('POST válido responde 201', $r['codigo'] === 201);
$id = isset($r['json']['datos']['id']) ? $r['json']['datos']['id'] : 0;
comprobar('POST devuelve el encabezado Location', (bool) preg_grep('/^Location: .*\/productos\/' . $id . '$/i', $r['encabezados']));

$r = pedir('POST', "$base/productos", $producto);
comprobar('POST con SKU repetido responde 409', $r['codigo'] === 409);

$r = pedir('GET', "$base/productos/$id");
comprobar('GET por id devuelve el producto creado', $r['codigo'] === 200 && $r['json']['datos']['sku'] === $sku);

$producto['precio'] = 1999.99;
$r = pedir('PUT', "$base/productos/$id", $producto);
comprobar('PUT actualiza el precio', $r['codigo'] === 200 && $r['json']['datos']['precio'] == 1999.99);

$r = pedir('GET', "$base/productos?q=" . urlencode($sku));
comprobar('GET con búsqueda encuentra el producto', $r['json']['meta']['total'] === 1);

$r = pedir('PATCH', "$base/productos/$id", $producto);
comprobar('Método no soportado responde 405', $r['codigo'] === 405);

$r = pedir('DELETE', "$base/productos/$id");
comprobar('DELETE responde 204', $r['codigo'] === 204);

$r = pedir('GET', "$base/productos/$id");
comprobar('GET de un producto borrado responde 404', $r['codigo'] === 404);

$r = pedir('GET', "$base/productos/abc");
comprobar('GET con id no numérico responde 400', $r['codigo'] === 400);

echo "\n$ok pruebas OK, $fallas fallas\n";
exit($fallas ? 1 : 0);
