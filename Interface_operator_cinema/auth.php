<?php
/**
 * Authentication handler – поддержка пользователей из БД
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
use Cinema\App;

/**
 * Проверка, авторизован ли пользователь
 */
function isUserLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Проверка, является ли текущий пользователь администратором
 */
function isAdmin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Аутентификация пользователя по логину/email и паролю
 * @return array|false – данные пользователя или false
 */
function authenticateUser(string $login, string $password) {
    $pdo = App::getConnection();
    if (!$pdo) return false;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        unset($user['password_hash']);
        return $user;
    }
    return false;
}

/**
 * Регистрация нового пользователя
 * @return true|string – true при успехе, иначе текст ошибки
 */
function registerUser(string $username, string $email, string $password) {
    $pdo = App::getConnection();
    if (!$pdo) return 'Ошибка подключения к БД';

    // Проверка уникальности
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return 'Пользователь с таким логином или email уже существует';
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'user')");
    if ($stmt->execute([$username, $email, $hash])) {
        return true;
    }
    return 'Ошибка при создании пользователя';
}

/**
 * Выход
 */
function logoutUser(): void {
    unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['user_role']);
    session_destroy();
}

// ---------- Обработка AJAX-запросов (для совместимости, если нужно) ----------
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'login') {
        $login = $_POST['login'] ?? '';
        $password = $_POST['password'] ?? '';
        $user = authenticateUser($login, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            echo json_encode(['success' => true, 'message' => 'Успешный вход', 'role' => $user['role']]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Неверные учётные данные']);
        }
        exit;
    }

    if ($_POST['action'] === 'register') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $result = registerUser($username, $email, $password);
        if ($result === true) {
            echo json_encode(['success' => true, 'message' => 'Регистрация успешна']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $result]);
        }
        exit;
    }
}

// Обработка выхода через GET
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutUser();
    header('Location: index.php');
    exit;
}