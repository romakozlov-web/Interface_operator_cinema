<?php
require 'config.php';
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
checkCsrf();

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$captcha  = $_POST['captcha'] ?? '';
$ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// 1. Обязательные поля
if ($email === '' || $password === '') {
    flash('error', 'Заполните все обязательные поля');
    flash('old_email', $email);
    redirect('index.php');
}

// 2. Email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Введите корректный email');
    flash('old_email', $email);
    redirect('index.php');
}

// 3. Капча
if (!checkCaptcha($captcha)) {
    flash('error', 'Неверный ответ капчи');
    flash('old_email', $email);
    redirect('index.php');
}

// 4. Антибрутфорс
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM login_attempts
     WHERE ip_address = ? AND success = 0
     AND attempted_at > datetime('now', '-15 minutes')"
);
$stmt->execute([$ip]);
if ($stmt->fetchColumn() >= 5) {
    flash('error', 'Слишком много неудачных попыток. Попробуйте позже.');
    redirect('index.php');
}

// 5. Ищем пользователя
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $pdo->prepare('INSERT INTO login_attempts (ip_address, email, success) VALUES (?,?,0)')
        ->execute([$ip, $email]);
    flash('error', 'Неверные учётные данные');
    flash('old_email', $email);
    redirect('index.php');
}

// 6. Пароль
if (!password_verify($password, $user['password'])) {
    $pdo->prepare('INSERT INTO login_attempts (ip_address, email, success) VALUES (?,?,0)')
        ->execute([$ip, $email]);
    flash('error', 'Неверный пароль');
    flash('old_email', $email);
    redirect('index.php');
}

// 7. Блокировка
if ($user['is_blocked']) {
    flash('error', 'Ваша учётная запись заблокирована: '
        . ($user['block_reason'] ?: 'обратитесь к администратору'));
    redirect('index.php');
}

// 8. Активность
if (!$user['is_active']) {
    flash('error', 'Учётная запись не активирована');
    redirect('index.php');
}

// 9. Успех
$pdo->prepare('INSERT INTO login_attempts (ip_address, email, success) VALUES (?,?,1)')
    ->execute([$ip, $email]);
$pdo->prepare("UPDATE users SET last_login = datetime('now') WHERE id = ?")
    ->execute([$user['id']]);

session_regenerate_id(true);
$_SESSION['user_id']   = $user['id'];
$_SESSION['username']  = $user['username'];
$_SESSION['full_name'] = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''))
                         ?: $user['username'];
$_SESSION['email']     = $user['email'];
$_SESSION['role']      = $user['role'];

redirect(isAdmin() ? 'dashboard_admin.php' : 'dashboard_user.php');