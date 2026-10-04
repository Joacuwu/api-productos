<?php
/**
 * Valida y normaliza el cuerpo JSON de un producto.
 */
class ProductoValidador
{
    private $errores = array();
    private $datos = array();

    /**
     * @param array $entrada  cuerpo recibido
     * @return bool           true si es válido; los datos limpios quedan en datos()
     */
    public function validar(array $entrada)
    {
        $this->errores = array();
        $this->datos = array();

        $sku = isset($entrada['sku']) ? strtoupper(trim((string) $entrada['sku'])) : '';
        if (!preg_match('/^[A-Z]{3}-\d{3,6}$/', $sku)) {
            $this->errores['sku'] = 'Formato esperado: tres letras, guion y número (ej. ALM-001).';
        }

        $nombre = isset($entrada['nombre']) ? trim((string) $entrada['nombre']) : '';
        $largo = mb_strlen($nombre, 'UTF-8');
        if ($largo < 3 || $largo > 120) {
            $this->errores['nombre'] = 'Debe tener entre 3 y 120 caracteres.';
        }

        $categoria = isset($entrada['categoria']) ? trim((string) $entrada['categoria']) : '';
        if ($categoria === '' || mb_strlen($categoria, 'UTF-8') > 40) {
            $this->errores['categoria'] = 'Es obligatoria (hasta 40 caracteres).';
        }

        $precio = isset($entrada['precio']) ? $entrada['precio'] : null;
        if (!is_numeric($precio) || $precio <= 0 || $precio > 99999999) {
            $this->errores['precio'] = 'Debe ser un número mayor a 0.';
        }

        $stock = isset($entrada['stock']) ? $entrada['stock'] : 0;
        if (filter_var($stock, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0))) === false) {
            $this->errores['stock'] = 'Debe ser un entero mayor o igual a 0.';
        }

        $activo = isset($entrada['activo']) ? $entrada['activo'] : true;
        if (!is_bool($activo) && !in_array($activo, array(0, 1, '0', '1'), true)) {
            $this->errores['activo'] = 'Debe ser true o false.';
        }

        if ($this->errores) {
            return false;
        }

        $this->datos = array(
            'sku'       => $sku,
            'nombre'    => $nombre,
            'categoria' => $categoria,
            'precio'    => round((float) $precio, 2),
            'stock'     => (int) $stock,
            'activo'    => (bool) $activo,
        );
        return true;
    }

    public function errores()
    {
        return $this->errores;
    }

    public function datos()
    {
        return $this->datos;
    }
}
