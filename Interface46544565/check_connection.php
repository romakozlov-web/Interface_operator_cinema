<?php

use PDO;
use PDOException;
require_once 'config.php';

header('Content-Type: application/json');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4",
        DB_USER,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );
    
    // Простая проверка
    $version = $pdo->query("SELECT VERSION()")->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'message' => 'Подключение успешно',
        'version' => $version,
        'server' => DB_HOST . ':' . DB_PORT
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}
?>