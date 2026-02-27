<?php

namespace Cinema;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    /**
     * Get PDO connection to the default database.
     *
     * @return PDO|null
     */
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

    /**
     * Get connection to a specific database.
     *
     * @param string $database
     * @return PDO|null
     */
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
}