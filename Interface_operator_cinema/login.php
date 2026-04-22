<?php
require_once 'config.php';
require_once 'auth.php';

if (isUserLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($login) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        $user = authenticateUser($login, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход – Кинотеатр "Алмаз"</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container" style="justify-content: center; align-items: center;">
        <div class="card" style="max-width: 400px; margin: 0 auto;">
            <h3><i class="fas fa-sign-in-alt"></i> Вход в систему</h3>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Логин или Email</label>
                    <input type="text" name="login" class="form-control" required autofocus>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Пароль</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-block"><i class="fas fa-arrow-right"></i> Войти</button>
                <p class="text-center" style="margin-top: 15px;">
                    Нет аккаунта? <a href="register.php">Зарегистрироваться</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>