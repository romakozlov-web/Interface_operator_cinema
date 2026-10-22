<?php
require 'config.php';
require 'functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет</title>
    <style>
        body { font-family: system-ui; background: #f0f2f5; 
               display:flex; justify-content:center; padding-top:60px; }
        .card { background:#fff; padding:30px; border-radius:12px;
                box-shadow:0 4px 20px rgba(0,0,0,.1); width:400px; text-align:center; }
        a.btn { display:inline-block; margin-top:15px; padding:10px 20px;
                background:#dc2626; color:#fff; border-radius:6px;
                text-decoration:none; }
    </style>
</head>
<body>
<div class="card">
    <h1>Привет, <?= htmlspecialchars($_SESSION['username']) ?>! 👋</h1>
    <p>Вы успешно авторизованы.</p>
    <a class="btn" href="logout.php">Выйти</a>
</div>
</body>
</html>