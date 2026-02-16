<?php

use Exception;
require_once 'config.php';
require_once 'functions.php';

$db = $_GET['db'] ?? DEFAULT_DB;
$table = $_GET['table'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!$table || !$id) {
    die('Не указана таблица или ID');
}

$pdo = connectToDB($db);
if (!$pdo) {
    die('Ошибка подключения к БД');
}

try {
    $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
    $stmt->execute([$id]);
} catch (Exception $e) {
    // можно залогировать ошибку
}

header("Location: ?table=" . urlencode($table));
exit;