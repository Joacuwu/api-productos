<?php
/**
 * Envía respuestas JSON con el código HTTP correspondiente.
 */
class Respuesta
{
    public static function json($datos, $codigo = 200, array $encabezados = array())
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        foreach ($encabezados as $nombre => $valor) {
            header($nombre . ': ' . $valor);
        }
        if ($datos !== null) {
            echo json_encode($datos, defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0);
        }
        exit;
    }

    public static function error($codigo, $mensaje, array $detalles = array(), array $encabezados = array())
    {
        $cuerpo = array('error' => array('codigo' => $codigo, 'mensaje' => $mensaje));
        if ($detalles) {
            $cuerpo['error']['detalles'] = $detalles;
        }
        self::json($cuerpo, $codigo, $encabezados);
    }

    public static function sinContenido()
    {
        http_response_code(204);
        exit;
    }
}
