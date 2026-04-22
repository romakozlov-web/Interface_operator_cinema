<?php

namespace Cinema;

use PDO;
use PDOException;
use Exception;

/**
 * Unified application class combining Database, TableManager, and FilmManager.
 */
class App
{
    private static ?PDO $connection = null;

    /* ===== Database ===== */

    public static function getConnection(): ?PDO
    {
        if (self::$connection === null) {
            try {
                self::$connection = new PDO(
                    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DEFAULT_DB . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASSWORD,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                error_log("Database connection error: " . $e->getMessage());
                return null;
            }
        }
        return self::$connection;
    }

    public static function getConnectionToDb(string $database): ?PDO
    {
        try {
            return new PDO(
                "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . $database . ";charset=utf8mb4",
                DB_USER,
                DB_PASSWORD,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }
    }

    /* ===== TableManager ===== */

    public static function getTableInfo(PDO $pdo, string $table): array
    {
        $info = ['rows' => 0, 'size' => '0 B', 'structure' => []];
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $row = $stmt->fetch();
            $info['rows'] = $row ? (int)$row['count'] : 0;

            $stmt = $pdo->query("DESCRIBE `$table`");
            $info['structure'] = $stmt->fetchAll();

            $stmt = $pdo->query("
                SELECT DATA_LENGTH + INDEX_LENGTH as size
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'
            ");
            $sizeInfo = $stmt->fetch();
            $info['size'] = self::formatSize((int)($sizeInfo['size'] ?? 0));
        } catch (Exception $e) {
            error_log("Error getting table info: " . $e->getMessage());
        }
        return $info;
    }

    public static function getTableData(PDO $pdo, string $table, int $page = 1, int $perPage = 30): array
    {
        $offset = ($page - 1) * $perPage;
        $query = "SELECT * FROM `$table`";
        if ($table === 'sessions') {
            $query = "SELECT s.*, f.title as film_title, h.name as hall_name
                      FROM sessions s
                      LEFT JOIN films f ON s.film_id = f.id
                      LEFT JOIN halls h ON s.hall_id = h.id";
        } elseif ($table === 'bookings') {
            $query = "SELECT b.*, f.title as film_title
                      FROM bookings b
                      LEFT JOIN sessions s ON b.session_id = s.id
                      LEFT JOIN films f ON s.film_id = f.id";
        }
        $query .= " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getTotalRows(PDO $pdo, string $table): int
    {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $row = $stmt->fetch();
        return $row ? (int)$row['count'] : 0;
    }

    public static function getDateColumns(PDO $pdo, string $table): array
    {
        $dateColumns = [];
        try {
            $stmt = $pdo->query("DESCRIBE `$table`");
            foreach ($stmt->fetchAll() as $col) {
                if (strpos($col['Type'], 'datetime') !== false ||
                    strpos($col['Type'], 'date') !== false ||
                    strpos($col['Type'], 'timestamp') !== false) {
                    $dateColumns[] = $col['Field'];
                }
            }
        } catch (Exception $e) {
            error_log("Error getting date columns: " . $e->getMessage());
        }
        return $dateColumns;
    }

    /* ===== FilmManager ===== */

    public static function getAllFilms(PDO $pdo): array
    {
        $stmt = $pdo->query("
            SELECT f.*,
                   (SELECT COUNT(DISTINCT s.id) FROM sessions s WHERE s.film_id = f.id) as sessions_count,
                   (SELECT COUNT(DISTINCT b.id) 
                    FROM sessions s2 
                    LEFT JOIN bookings b ON s2.id = b.session_id 
                    WHERE s2.film_id = f.id) as bookings_count
            FROM films f
            ORDER BY f.release_date DESC
        ");
        return $stmt->fetchAll();
    }

    public static function addFilm(PDO $pdo, array $data): bool
    {
        $stmt = $pdo->prepare(
            "INSERT INTO films (title, description, duration, poster, release_date)
             VALUES (:title, :description, :duration, :poster, :release_date)"
        );
        return $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':duration' => $data['duration'],
            ':poster' => $data['poster'] ?? null,
            ':release_date' => $data['release_date'] ?? null,
        ]);
    }

    public static function getFilmById(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM films WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function updateFilm(PDO $pdo, int $id, array $data): bool
    {
        $set = [];
        $params = [':id' => $id];
        foreach ($data as $field => $value) {
            if ($field !== 'id') {
                $set[] = "`$field` = :$field";
                $params[":$field"] = $value;
            }
        }
        if (empty($set)) return false;
        $sql = "UPDATE films SET " . implode(', ', $set) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function deleteFilm(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM films WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function deleteHall(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM halls WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function deleteSession(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function deleteBooking(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /* ===== Formatter (static helpers) ===== */

    public static function formatSize(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int)floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    public static function truncateText(string $text, int $length = 50): string
    {
        if (mb_strlen($text) > $length) {
            return mb_substr($text, 0, $length) . '...';
        }
        return $text;
    }

    public static function escape(?string $data): string
    {
        return htmlspecialchars($data ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function formatDate(?string $date, string $format = 'd.m.Y H:i'): string
    {
        if (empty($date) || $date === '0000-00-00 00:00:00') return '-';
        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : '-';
    }

    public static function isTableAllowed(string $table): bool
    {
        $allowed = unserialize(ALLOWED_TABLES);
        return in_array($table, $allowed);
    }

    /* ===== Importer ===== */

    public static function importSql(PDO $pdo, string $sqlContent): array
    {
        $errors = [];
        $queries = self::splitSqlQueries($sqlContent);
        if (empty($queries)) return ['No valid SQL queries found.'];

        try {
            $pdo->beginTransaction();
            foreach ($queries as $query) {
                $query = trim($query);
                if (empty($query)) continue;
                $pdo->exec($query);
            }
            $pdo->commit();
            return [];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return [$e->getMessage()];
        }
    }

    private static function splitSqlQueries(string $sql): array
    {
        $queries = [];
        $inString = false;
        $currentQuery = '';
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            if (($char === "'" || $char === '"') && ($i === 0 || $sql[$i-1] !== '\\')) {
                $inString = !$inString;
            }
            if (!$inString && $char === ';') {
                $queries[] = trim($currentQuery);
                $currentQuery = '';
            } else {
                $currentQuery .= $char;
            }
        }
        if (trim($currentQuery) !== '') $queries[] = trim($currentQuery);

        return array_filter($queries, function($q) {
            $q = trim($q);
            if (empty($q)) return false;
            if (strpos($q, '--') === 0 || strpos($q, '#') === 0) return false;
            return true;
        });
    }
}
