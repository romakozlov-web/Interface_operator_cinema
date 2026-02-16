<?php
session_start();

// Конфигурация базы данных
define('DB_HOST', '134.90.167.42::10306');
define('DB_PORT', '10306');
define('DB_USER', 'Kozlov');
define('DB_PASSWORD', 'uwn.[H.NYJa7wxpT');
define('DB_NAME', 'project_Kozlov');

// Настройки
define('ROWS_PER_PAGE', 30);
define('MAX_TEXT_LENGTH', 50);

// Цветовая тема
$theme = $_SESSION['theme'] ?? 'light';
?>