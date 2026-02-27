<?php

/**
 * Configuration file for cinema admin panel
 * Follows PSR-1 and PSR-12 standards
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration constants
define('DB_HOST', '134.90.167.42');
define('DB_PORT', '10306');
define('DB_USER', 'Kozlov');
define('DB_PASSWORD', 'uwn.[H.NYJa7wxpT');
define('DEFAULT_DB', 'project_Kozlov');

// Display settings
define('ROWS_PER_PAGE', 30);
define('MAX_TEXT_LENGTH', 50);

// Theme settings
$theme = $_SESSION['theme'] ?? 'light';

// Autoloader for Cinema namespace
spl_autoload_register(function ($class) {
    $prefix = 'Cinema\\';
    $base_dir = __DIR__ . '/classes/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});