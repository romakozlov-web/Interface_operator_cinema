<?php
/**
 * Подключение к конкретной базе данных
 */
function connectToDB($database) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . $database . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Получение информации о таблице
 */
function getTableInfo($pdo, $table) {
    $info = ['rows' => 0, 'size' => '0 B', 'structure' => []];
    
    try {
        // Количество записей
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $info['rows'] = $result ? $result['count'] : 0;
        
        // Структура таблицы
        $stmt = $pdo->query("DESCRIBE `$table`");
        $info['structure'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Размер таблицы
        $stmt = $pdo->query("
            SELECT 
                DATA_LENGTH + INDEX_LENGTH as size,
                TABLE_ROWS as rows_count
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '$table'
        ");
        $sizeInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        $info['size'] = formatSize($sizeInfo['size'] ?? 0);
        
    } catch (Exception $e) {
        // Ошибка - возвращаем значения по умолчанию
    }
    
    return $info;
}

/**
 * Форматирование размера
 */
function formatSize($bytes) {
    if ($bytes == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

/**
 * Обрезка текста для отображения
 */
function truncateText($text, $length = 50) {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}
?>