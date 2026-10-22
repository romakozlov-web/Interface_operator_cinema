<?php
require 'config.php';

// Если уже вошли - на дашборд
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard_user.php');
    exit;
}

$page    = $_GET['page'] ?? 'login';
$error   = $_SESSION['error']   ?? null;
$success = $_SESSION['success'] ?? null;
$old     = $_SESSION['old']     ?? [];
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title><?= $page === 'register' ? 'Регистрация' : 'Вход' ?></title>
<style>
    * { box-sizing: border-box; font-family: system-ui, sans-serif; }
    body { background:#f0f2f5; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; padding:20px; }
    .card { background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,.1); width:400px; }
    h1 { margin:0 0 20px; font-size:22px; text-align:center; }
    input { width:100%; padding:10px 12px; margin-bottom:12px; border:1px solid #ddd; border-radius:6px; font-size:14px; }
    button { width:100%; padding:11px; background:#2563eb; color:#fff; border:0; border-radius:6px; font-size:15px; cursor:pointer; }
    button:hover { background:#1d4ed8; }
    .msg { padding:10px; border-radius:6px; margin-bottom:12px; font-size:14px; }
    .err { background:#fee2e2; color:#991b1b; }
    .ok  { background:#dcfce7; color:#166534; }
    .links { text-align:center; margin-top:15px; font-size:14px; }
    .links a { color:#2563eb; text-decoration:none; }
    
    .captcha-wrapper { margin-bottom: 15px; }
    .captcha-title { font-size: 13px; color: #6b7280; margin-bottom: 8px; }
    .captcha-grid { 
        display: grid; grid-template-columns: 1fr 1fr; gap: 0; 
        width: 240px; height: 240px; margin: 0 auto; 
        border: 2px solid #e5e7eb; border-radius: 8px; overflow: hidden; 
        background: #f9fafb;
    }
    .captcha-piece { 
        width: 100%; height: 100%; object-fit: cover; cursor: pointer; 
        transition: transform 0.1s, box-shadow 0.1s; user-select: none;
    }
    .captcha-piece.selected { box-shadow: inset 0 0 0 4px #2563eb; transform: scale(0.95); }
</style>
</head>
<body>
<div class="card">

<?php if ($error):   ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="msg ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if ($page === 'register'): ?>
    <h1>Регистрация</h1>
    <form method="post" action="auth.php?action=register" autocomplete="off">
        <input type="text"     name="firstname" placeholder="Имя" required>
        <input type="text"     name="lastname"  placeholder="Фамилия" required>
        <input type="text"     name="username"  placeholder="Логин" required minlength="3">
        <input type="email"    name="email"     placeholder="Email" required>
        <input type="password" name="password"  placeholder="Пароль" required minlength="6">

        <div class="captcha-wrapper">
            <div class="captcha-title">Соберите картинку:</div>
            <div class="captcha-grid" id="captchaGrid">
                <img src="cap_1.png" data-id="1" class="captcha-piece" onclick="selectPiece(this)">
                <img src="cap_2.png" data-id="2" class="captcha-piece" onclick="selectPiece(this)">
                <img src="cap_3.png" data-id="3" class="captcha-piece" onclick="selectPiece(this)">
                <img src="cap_4.png" data-id="4" class="captcha-piece" onclick="selectPiece(this)">
            </div>
            <input type="hidden" name="captcha_order" id="captchaOrder" value="1,2,3,4">
        </div>
        <button type="submit" onclick="prepareSubmit()">Зарегистрироваться</button>
    </form>
    <div class="links">Уже есть аккаунт? <a href="?page=login">Войти</a></div>

<?php else: ?>
    <h1>Вход</h1>
    <form method="post" action="auth.php?action=login" autocomplete="off" id="loginForm">
        <input type="email"    name="email"    placeholder="Email" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
        <input type="password" name="password" placeholder="Пароль" required>

        <div class="captcha-wrapper">
            <div class="captcha-title">Соберите картинку:</div>
            <div class="captcha-grid" id="captchaGrid">
                <!-- data-id - это "правильное" место картинки (1=верх-лево, 2=верх-право, 3=низ-лево, 4=низ-право) -->
                <img src="cap_1.png" data-id="1" class="captcha-piece" onclick="selectPiece(this)">
                <img src="cap_2.png" data-id="2" class="captcha-piece" onclick="selectPiece(this)">
                <img src="cap_3.png" data-id="3" class="captcha-piece" onclick="selectPiece(this)">
                <img src="cap_4.png" data-id="4" class="captcha-piece" onclick="selectPiece(this)">
            </div>
            <input type="hidden" name="captcha_order" id="captchaOrder" value="1,2,3,4">
        </div>
        <button type="submit" onclick="prepareSubmit()">Войти</button>
    </form>
    <div class="links">Нет аккаунта? <a href="?page=register">Регистрация</a></div>
<?php endif; ?>

</div>

<script>
    let firstPiece = null;

    // 1. Перемешиваем картинки при загрузке
    window.onload = function() {
        let grid = document.getElementById('captchaGrid');
        let items = Array.from(grid.children);
        items.sort(() => Math.random() - 0.5);
        grid.innerHTML = '';
        items.forEach(item => grid.appendChild(item));
    };

    // 2. Меняем местами картинки и их ID
    function selectPiece(el) {
        if (!firstPiece) {
            firstPiece = el; 
            el.classList.add('selected');
        } else {
            if (firstPiece === el) { 
                firstPiece.classList.remove('selected'); 
                firstPiece = null; 
                return; 
            }
            
            // Меняем src (картинку) и data-id (правильное место)
            let tempSrc = firstPiece.getAttribute('src');
            let tempId = firstPiece.getAttribute('data-id');
            
            firstPiece.setAttribute('src', el.getAttribute('src'));
            firstPiece.setAttribute('data-id', el.getAttribute('data-id'));
            
            el.setAttribute('src', tempSrc);
            el.setAttribute('data-id', tempId);
            
            firstPiece.classList.remove('selected'); 
            firstPiece = null;
        }
    }

    // 3. Собираем текущий порядок
    function prepareSubmit() {
        let imgs = document.querySelectorAll('#captchaGrid .captcha-piece');
        let order = Array.from(imgs).map(img => img.getAttribute('data-id')).join(',');
        document.getElementById('captchaOrder').value = order;
    }
</script>
</body>
</html>