<?php
/**
 * Acceso a la tabla productos. Todas las consultas son preparadas.
 */
class ProductoRepositorio
{
    /** @var PDO */
    private $pdo;

    /** Columnas por las que se permite ordenar (lista blanca: nunca se concatena lo que manda el cliente). */
    private static $ordenes = array(
        'nombre'  => 'nombre ASC',
        '-nombre' => 'nombre DESC',
        'precio'  => 'precio ASC',
        '-precio' => 'precio DESC',
        'stock'   => 'stock ASC',
        '-stock'  => 'stock DESC',
        'id'      => 'id ASC',
        '-id'     => 'id DESC',
    );

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista con filtros, orden y paginación. Devuelve array('datos' => [...], 'total' => int).
     */
    public function listar(array $filtros, $orden, $pagina, $porPagina)
    {
        $where = array();
        $params = array();

        if (!empty($filtros['q'])) {
            $where[] = '(nombre LIKE :q1 OR sku LIKE :q2)';
            $params[':q1'] = $params[':q2'] = '%' . $filtros['q'] . '%';
        }
        if (!empty($filtros['categoria'])) {
            $where[] = 'categoria = :categoria';
            $params[':categoria'] = $filtros['categoria'];
        }
        if (isset($filtros['activo']) && $filtros['activo'] !== '') {
            $where[] = 'activo = :activo';
            $params[':activo'] = $filtros['activo'] ? 1 : 0;
        }
        if (!empty($filtros['sin_stock'])) {
            $where[] = 'stock = 0';
        }
        $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $sqlOrden = isset(self::$ordenes[$orden]) ? self::$ordenes[$orden] : self::$ordenes['nombre'];

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM productos' . $sqlWhere);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare('SELECT * FROM productos' . $sqlWhere . ' ORDER BY ' . $sqlOrden . ' LIMIT :limite OFFSET :offset');
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limite', (int) $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) (($pagina - 1) * $porPagina), PDO::PARAM_INT);
        $stmt->execute();

        return array('datos' => array_map(array($this, 'formatear'), $stmt->fetchAll()), 'total' => $total);
    }

    public function buscar($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM productos WHERE id = ?');
        $stmt->execute(array((int) $id));
        $fila = $stmt->fetch();
        return $fila ? $this->formatear($fila) : null;
    }

    public function existeSku($sku, $exceptoId = 0)
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM productos WHERE sku = ? AND id <> ?');
        $stmt->execute(array($sku, (int) $exceptoId));
        return (bool) $stmt->fetchColumn();
    }

    public function crear(array $d)
    {
        $ahora = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('INSERT INTO productos (sku, nombre, categoria, precio, stock, activo, creado_en, actualizado_en)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute(array($d['sku'], $d['nombre'], $d['categoria'], $d['precio'], $d['stock'], $d['activo'] ? 1 : 0, $ahora, $ahora));
        return $this->buscar($this->pdo->lastInsertId());
    }

    public function actualizar($id, array $d)
    {
        $stmt = $this->pdo->prepare('UPDATE productos SET sku = ?, nombre = ?, categoria = ?, precio = ?, stock = ?, activo = ?, actualizado_en = ?
                                     WHERE id = ?');
        $stmt->execute(array($d['sku'], $d['nombre'], $d['categoria'], $d['precio'], $d['stock'], $d['activo'] ? 1 : 0,
                             date('Y-m-d H:i:s'), (int) $id));
        return $this->buscar($id);
    }

    public function eliminar($id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM productos WHERE id = ?');
        $stmt->execute(array((int) $id));
        return $stmt->rowCount() > 0;
    }

    public function categorias()
    {
        return $this->pdo->query('SELECT categoria, COUNT(*) AS cantidad FROM productos GROUP BY categoria ORDER BY categoria')->fetchAll();
    }

    /** Convierte los tipos que PDO devuelve como texto. */
    private function formatear(array $f)
    {
        return array(
            'id'             => (int) $f['id'],
            'sku'            => $f['sku'],
            'nombre'         => $f['nombre'],
            'categoria'      => $f['categoria'],
            'precio'         => (float) $f['precio'],
            'stock'          => (int) $f['stock'],
            'activo'         => (bool) $f['activo'],
            'creado_en'      => $f['creado_en'],
            'actualizado_en' => $f['actualizado_en'],
        );
    }
}
