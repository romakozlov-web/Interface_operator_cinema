<?php
require_once 'config.php';
require_once 'auth.php';

if (isUserLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Заполните все поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif ($password !== $confirm) {
        $error = 'Пароли не совпадают';
    } else {
        $result = registerUser($username, $email, $password);
        if ($result === true) {
            $success = 'Регистрация успешна! Теперь вы можете <a href="login.php">войти</a>.';
        } else {
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация – Кинотеатр "Алмаз"</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container" style="justify-content: center; align-items: center;">
        <div class="card" style="max-width: 400px; margin: 0 auto;">
            <h3><i class="fas fa-user-plus"></i> Регистрация</h3>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php else: ?>
                <form method="post">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Логин</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Пароль (мин. 6 символов)</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Повторите пароль</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-block"><i class="fas fa-check"></i> Зарегистрироваться</button>
                    <p class="text-center" style="margin-top: 15px;">
                        Уже есть аккаунт? <a href="login.php">Войти</a>
                    </p>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>