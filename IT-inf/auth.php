<?php
require 'config.php';

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Функция проверки капчи
function captchaOk(): bool {
    return ($_POST['captcha_order'] ?? '') === '1,2,3,4';
}

// РЕГИСТРАЦИЯ
if ($action === 'register') {
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');

    $_SESSION['old'] = compact('firstname','lastname','username','email');

    if (mb_strlen($username) < 3 || mb_strlen($password) < 6) {
        $_SESSION['error'] = 'Логин ≥ 3 символа, пароль ≥ 6';
        header('Location: index.php?page=register'); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Некорректный email';
        header('Location: index.php?page=register'); exit;
    }
    if (!captchaOk()) {
        $_SESSION['error'] = 'Капча собрана неверно. Соберите картинку правильно.';
        header('Location: index.php?page=register'); exit;
    }

    try {
        db()->prepare("INSERT INTO users (username, email, password, firstname, lastname, role)
                       VALUES (?, ?, ?, ?, ?, 'user')")
            ->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $firstname, $lastname]);
        unset($_SESSION['old']);
        $_SESSION['success'] = 'Регистрация успешна! Теперь войдите.';
        header('Location: index.php?page=login'); exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = str_contains($e->getMessage(), 'UNIQUE') ? 'Такой логин или email уже занят' : 'Ошибка БД';
        header('Location: index.php?page=register'); exit;
    }
}

// ВХОД
if ($action === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $_SESSION['old'] = ['email' => $email];

    if ($email === '' || $password === '') {
        $_SESSION['error'] = 'Заполните все поля';
        header('Location: index.php'); exit;
    }
    if (!captchaOk()) {
        $_SESSION['error'] = 'Капча собрана неверно. Соберите картинку правильно.';
        header('Location: index.php'); exit;
    }

    $stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['error'] = 'Неверные учётные данные';
        header('Location: index.php'); exit;
    }
    if ($user['is_blocked']) {
        $_SESSION['error'] = 'Учётная запись заблокирована';
        header('Location: index.php'); exit;
    }

    unset($_SESSION['old']);
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')) ?: $user['username'];
    $_SESSION['role']      = $user['role'];

    db()->prepare("UPDATE users SET last_login = datetime('now') WHERE id = ?")->execute([$user['id']]);

    header('Location: dashboard_user.php'); 
    exit;
}

header('Location: index.php');