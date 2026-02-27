<?php

namespace Cinema;

use PDO;
use Exception;

class Importer
{
    private PDO $pdo;
    private array $errors = [];
    private array $success = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Import SQL from file content.
     *
     * @param string $sqlContent
     * @return bool True if all queries succeeded, false otherwise.
     */
    public function import(string $sqlContent): bool
    {
        $this->errors = [];
        $this->success = [];

        $queries = $this->splitQueries($sqlContent);

        if (empty($queries)) {
            $this->errors[] = "No valid SQL queries found.";
            return false;
        }

        $useTransaction = $this->pdo->beginTransaction();

        foreach ($queries as $index => $query) {
            $query = trim($query);
            if (empty($query)) {
                continue;
            }

            try {
                $result = $this->pdo->exec($query);
                if ($result === false) {
                    $errorInfo = $this->pdo->errorInfo();
                    throw new Exception($errorInfo[2] ?? "Unknown error");
                }
                $this->success[] = "Query " . ($index + 1) . " executed successfully.";
            } catch (Exception $e) {
                $this->errors[] = "Query " . ($index + 1) . " failed: " . $e->getMessage();
                if ($useTransaction) {
                    $this->pdo->rollBack();
                }
                return false;
            }
        }

        if ($useTransaction) {
            $this->pdo->commit();
        }

        return empty($this->errors);
    }

    /**
     * Split SQL content into individual queries (ignores semicolons in quotes).
     */
    private function splitQueries(string $sql): array
    {
        $queries = [];
        $inString = false;
        $quoteChar = '';
        $currentQuery = '';
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            if (($char === "'" || $char === '"') && ($i === 0 || $sql[$i-1] !== '\\')) {
                $inString = !$inString;
                $quoteChar = $char;
            }
            if (!$inString && $char === ';') {
                $queries[] = trim($currentQuery);
                $currentQuery = '';
            } else {
                $currentQuery .= $char;
            }
        }
        if (trim($currentQuery) !== '') {
            $queries[] = trim($currentQuery);
        }
        return array_filter($queries, function($q) {
            $q = trim($q);
            if (empty($q)) return false;
            if (strpos($q, '--') === 0 || strpos($q, '#') === 0) return false;
            return true;
        });
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccess(): array
    {
        return $this->success;
    }
}