<?php
session_start();

// Используем SQLite (база создается сама, файл app.sqlite)
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $file = __DIR__ . '/app.sqlite';
    $pdo = new PDO('sqlite:' . $file, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');

    // Таблица юзеров
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            username   TEXT NOT NULL UNIQUE,
            email      TEXT NOT NULL UNIQUE,
            password   TEXT NOT NULL,
            firstname  TEXT,
            lastname   TEXT,
            role       TEXT DEFAULT 'user',
            is_active  INTEGER DEFAULT 1,
            is_blocked INTEGER DEFAULT 0,
            block_reason TEXT,
            last_login TEXT
        );
    ");

    // Тестовый админ
    $exists = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
    if ($exists === 0) {
        $pdo->prepare("INSERT INTO users (username, email, password, firstname, lastname, role)
                       VALUES (?, ?, ?, ?, ?, 'admin')")
            ->execute([
                'admin', 'admin@test.ru', password_hash('admin123', PASSWORD_DEFAULT), 'Админ', 'Админов'
            ]);
    }
    return $pdo;
}

// Глобальная переменная для удобства (если где-то в старом коде осталось $pdo)
$pdo = db();