<?php
require_once dirname(__FILE__) . '/config.php';

/**
 * Conexión PDO única. En modo demo (sin DB_DSN) crea la base SQLite a partir de sql/schema.sql.
 */
function db()
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $opciones = array(
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    );

    if (DB_DSN !== '') {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, $opciones);
        return $pdo;
    }

    $archivo = dirname(dirname(__FILE__)) . '/data/demo.sqlite';
    $nueva = !file_exists($archivo);
    $pdo = new PDO('sqlite:' . $archivo, null, null, $opciones);
    if ($nueva) {
        $pdo->exec('CREATE TABLE productos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sku VARCHAR(20) NOT NULL UNIQUE,
            nombre VARCHAR(120) NOT NULL,
            categoria VARCHAR(40) NOT NULL,
            precio DECIMAL(12,2) NOT NULL,
            stock INTEGER NOT NULL DEFAULT 0,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            creado_en DATETIME NOT NULL,
            actualizado_en DATETIME NOT NULL
        )');
        // Reutiliza los INSERT del esquema MySQL, cambiando NOW() por la fecha actual
        $sql = file_get_contents(dirname(dirname(__FILE__)) . '/sql/schema.sql');
        if (preg_match('/INSERT INTO productos.*?;/s', $sql, $m)) {
            $pdo->exec(str_replace('NOW()', "'" . date('Y-m-d H:i:s') . "'", $m[0]));
        }
    }
    return $pdo;
}
