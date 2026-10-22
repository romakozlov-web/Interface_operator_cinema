<?php
require 'config.php';
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php?page=register');
}
checkCsrf();

$username  = trim($_POST['username'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = $_POST['password'] ?? '';
$firstname = trim($_POST['firstname'] ?? '');
$lastname  = trim($_POST['lastname'] ?? '');
$captcha   = $_POST['captcha'] ?? '';

// 1. Валидация полей
if (mb_strlen($username) < 3 || mb_strlen($password) < 6) {
    flash('error', 'Логин минимум 3 символа, пароль минимум 6');
    redirect('index.php?page=register');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Некорректный email');
    redirect('index.php?page=register');
}
if ($firstname === '' || $lastname === '') {
    flash('error', 'Заполните имя и фамилию');
    redirect('index.php?page=register');
}

// 2. Капча
if (!checkCaptcha($captcha)) {
    flash('error', 'Неверный ответ капчи');
    redirect('index.php?page=register');
}

// 3. Сохранение
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, email, password, firstname, lastname, role)
         VALUES (?, ?, ?, ?, ?, "user")'
    );
    $stmt->execute([$username, $email, $hash, $firstname, $lastname]);

    flash('success', 'Регистрация успешна! Теперь войдите.');
    redirect('index.php?page=login');

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        flash('error', 'Такой логин или email уже занят');
    } else {
        flash('error', 'Ошибка сервера');
    }
    redirect('index.php?page=register');
}