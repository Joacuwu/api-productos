<?php
/**
 * Configuración. Compatible con PHP 5.6+ (sin sintaxis de PHP 7).
 * Si DB_DSN queda vacío se usa SQLite en /data (modo demo local).
 */
define('DB_DSN',  '');   // ej: 'mysql:host=localhost;dbname=api_productos;charset=utf8'
define('DB_USER', '');
define('DB_PASS', '');

// Clave para las operaciones de escritura (POST, PUT, DELETE). Cambiarla en producción.
define('API_KEY', 'demo-clave-portafolio');

define('POR_PAGINA_MAX', 50);

ini_set('display_errors', '0');
error_reporting(E_ALL);
date_default_timezone_set('America/Argentina/Buenos_Aires');
