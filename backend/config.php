<?php
/**
 * Configuración de conexión a la base de datos.
 * Completar con los datos reales del hosting antes de desplegar.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'vnt_actividades');
define('DB_USER', 'CAMBIAR_USUARIO');
define('DB_PASS', 'CAMBIAR_PASSWORD');
define('DB_CHARSET', 'utf8mb4');

// Dominio(s) desde el que se permite llamar a esta API (CORS).
// Si el HTML y el backend se sirven desde el mismo dominio, dejar '*'.
define('CORS_ALLOWED_ORIGIN', '*');
