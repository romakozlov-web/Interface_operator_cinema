<?php

use Exception;
use PDO;
use PDOException;

/**
 * Database connection and utility functions
 * Follows PSR-1 and PSR-12 standards
 */

/**
 * Establishes connection to specific database
 * 
 * @param string $database Database name
 * @return PDO|null Returns PDO object on success, null on failure
 */
function connectToDB(string $database): ?PDO
{
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . $database . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

/**
 * Retrieves table information
 * 
 * @param PDO $pdo Database connection
 * @param string $table Table name
 * @return array Table information
 */
function getTableInfo(PDO $pdo, string $table): array
{
    $info = [
        'rows' => 0,
        'size' => '0 B',
        'structure' => []
    ];
    
    try {
        // Get row count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $result = $stmt->fetch();
        $info['rows'] = $result ? (int)$result['count'] : 0;
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE `$table`");
        $info['structure'] = $stmt->fetchAll();
        
        // Get table size
        $stmt = $pdo->query("
            SELECT DATA_LENGTH + INDEX_LENGTH as size
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'
        ");
        $sizeInfo = $stmt->fetch();
        $info['size'] = formatSize($sizeInfo['size'] ?? 0);
    } catch (Exception $e) {
        error_log("Error getting table info: " . $e->getMessage());
    }
    
    return $info;
}

/**
 * Formats bytes to human readable format
 * 
 * @param int $bytes Size in bytes
 * @return string Formatted size
 */
function formatSize(int $bytes): string
{
    if ($bytes === 0) {
        return '0 B';
    }
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = (int)floor(log($bytes, 1024));
    
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

/**
 * Truncates text to specified length
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @return string Truncated text
 */
function truncateText(string $text, int $length = 50): string
{
    if (mb_strlen($text) > $length) {
        return mb_substr($text, 0, $length) . '...';
    }
    
    return $text;
}

/**
 * Sanitizes output data
 * 
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function escapeOutput(string $data): string
{
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Formats date for display
 * 
 * @param string|null $date Date string
 * @param string $format Date format
 * @return string Formatted date
 */
function formatDate(?string $date, string $format = 'd.m.Y H:i'): string
{
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '-';
}