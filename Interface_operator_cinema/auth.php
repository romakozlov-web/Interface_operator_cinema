<?php

/**
 * Authentication handler for admin panel
 * Handles login, logout, and password verification
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config for constants and DB connection
require_once __DIR__ . '/config.php';

// Admin password hash (default: admin123)
// In production, change this to a strong password
define('ADMIN_PASSWORD_HASH', password_hash('admin123', PASSWORD_DEFAULT));

/**
 * Check if user is admin
 */
function isAdmin(): bool
{
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Verify admin password
 */
function verifyPassword(string $password): bool
{
    return password_verify($password, ADMIN_PASSWORD_HASH);
}

/**
 * Handle AJAX login request
 */
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    header('Content-Type: application/json');
    
    $password = $_POST['password'] ?? '';
    
    if (verifyPassword($password)) {
        $_SESSION['is_admin'] = true;
        echo json_encode(['success' => true, 'message' => 'Успешная авторизация']);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Неверный пароль']);
    }
    exit;
}

/**
 * Handle logout request
 */
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['is_admin']);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

/**
 * Handle password change request (optional)
 */
if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
    header('Content-Type: application/json');
    
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
        exit;
    }
    
    $newPassword = $_POST['new_password'] ?? '';
    
    if (strlen($newPassword) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Пароль должен быть минимум 6 символов']);
        exit;
    }
    
    // Note: This doesn't persist across requests since ADMIN_PASSWORD_HASH is a constant
    // For production, store password hash in database or config file
    echo json_encode(['success' => false, 'message' => 'Смена пароля не поддерживается в текущей конфигурации']);
    exit;
}
