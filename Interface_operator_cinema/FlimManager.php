<?php

namespace Cinema;

use PDO;
use Exception;

class FilmManager
{
    private PDO $pdo;
    private string $table = 'films';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get all films.
     *
     * @return array
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT f.*, 
                   COUNT(DISTINCT s.id) as sessions_count,
                   COUNT(DISTINCT b.id) as bookings_count
            FROM films f
            LEFT JOIN sessions s ON f.id = s.film_id
            LEFT JOIN bookings b ON s.id = b.session_id
            GROUP BY f.id
            ORDER BY f.release_date DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Add a new film.
     *
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function add(array $data): bool
    {
        $stmt = $this->pdo->prepare(
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

    /**
     * Get film by ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM films WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Update film.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $set = [];
        $params = [':id' => $id];
        foreach ($data as $field => $value) {
            if ($field !== 'id') {
                $set[] = "`$field` = :$field";
                $params[":$field"] = $value;
            }
        }
        if (empty($set)) {
            return false;
        }
        $sql = "UPDATE films SET " . implode(', ', $set) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete film.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM films WHERE id = ?");
        return $stmt->execute([$id]);
    }
}