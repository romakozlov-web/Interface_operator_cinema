<?php

namespace Cinema;

use PDO;
use Exception;

class TableManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get table info (rows count, size, structure).
     *
     * @param string $table
     * @return array
     */
    public function getTableInfo(string $table): array
    {
        $info = [
            'rows' => 0,
            'size' => '0 B',
            'structure' => []
        ];

        try {
            // Row count
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $row = $stmt->fetch();
            $info['rows'] = $row ? (int)$row['count'] : 0;

            // Structure
            $stmt = $this->pdo->query("DESCRIBE `$table`");
            $info['structure'] = $stmt->fetchAll();

            // Size
            $stmt = $this->pdo->query("
                SELECT DATA_LENGTH + INDEX_LENGTH as size
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'
            ");
            $sizeInfo = $stmt->fetch();
            $info['size'] = Formatter::formatSize((int)($sizeInfo['size'] ?? 0));
        } catch (Exception $e) {
            error_log("Error getting table info: " . $e->getMessage());
        }

        return $info;
    }

    /**
     * Get paginated table data with optional joins.
     *
     * @param string $table
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getTableData(string $table, int $page = 1, int $perPage = 30): array
    {
        $offset = ($page - 1) * $perPage;

        // Build query with optional joins for known tables
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

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get total rows count.
     *
     * @param string $table
     * @return int
     */
    public function getTotalRows(string $table): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $row = $stmt->fetch();
        return $row ? (int)$row['count'] : 0;
    }

    /**
     * Get date columns for a table.
     *
     * @param string $table
     * @return array
     */
    public function getDateColumns(string $table): array
    {
        $dateColumns = [];
        try {
            $stmt = $this->pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll();
            foreach ($columns as $col) {
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
}