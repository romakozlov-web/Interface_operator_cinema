<?php

use Exception;
use PDO;
require_once 'config.php';
require_once 'functions.php';

$db = $_GET['db'] ?? DEFAULT_DB;
$table = $_GET['table'] ?? '';
$format = $_GET['format'] ?? 'csv';

if (!$table) {
    die('Не указана таблица');
}

$pdo = connectToDB($db);
if (!$pdo) {
    die('Ошибка подключения к БД');
}

// Получаем все данные
try {
    $stmt = $pdo->query("SELECT * FROM `$table`");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('Ошибка получения данных: ' . $e->getMessage());
}

if (empty($rows)) {
    die('Нет данных для экспорта');
}

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $table . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    // UTF-8 BOM для Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    // Заголовки
    fputcsv($output, array_keys($rows[0]));
    // Данные
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
} else {
    die('Неподдерживаемый формат');
}
?>