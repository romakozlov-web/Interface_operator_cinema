<?php

use Exception;
use PDO;
require_once 'config.php';
require_once 'functions.php';

$db = $_GET['db'] ?? DEFAULT_DB;
$table = $_GET['table'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!$table || !$id) {
    die('Не указана таблица или ID');
}

$pdo = connectToDB($db);
if (!$pdo) {
    die('Ошибка подключения к БД');
}

// Получаем структуру таблицы для построения формы
$stmt = $pdo->query("DESCRIBE `$table`");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем данные записи
$stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    die('Запись не найдена');
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sets = [];
    $params = [];
    foreach ($columns as $col) {
        $field = $col['Field'];
        if ($field == 'id') continue; // не обновляем первичный ключ
        if (isset($_POST[$field])) {
            $sets[] = "`$field` = ?";
            $params[] = $_POST[$field];
        }
    }
    $params[] = $id;
    
    try {
        $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $success = true;
        // Обновляем данные для отображения
        $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = 'Ошибка обновления: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="UTF-8">
    <title>Редактирование записи</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <i class="fas fa-edit"></i>
                <h1>Редактирование <?php echo htmlspecialchars($table); ?></h1>
            </div>
            <div class="user-info">
                <button class="btn btn-sm" onclick="toggleTheme()"><i class="fas fa-moon"></i></button>
            </div>
        </header>
        <main class="content-area" style="padding: 20px;">
            <div class="card">
                <h3>Редактирование записи ID <?php echo $id; ?></h3>
                <?php if ($success): ?>
                    <div class="alert alert-success">Запись обновлена!</div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <?php foreach ($columns as $col): ?>
                        <?php $field = $col['Field']; ?>
                        <?php if ($field == 'id') continue; ?>
                        <div class="form-group">
                            <label for="<?php echo $field; ?>"><?php echo htmlspecialchars($field); ?></label>
                            <?php if (strpos($col['Type'], 'text') !== false || strpos($col['Type'], 'blob') !== false): ?>
                                <textarea name="<?php echo $field; ?>" id="<?php echo $field; ?>" class="form-control" rows="5"><?php echo htmlspecialchars($row[$field]); ?></textarea>
                            <?php elseif (strpos($col['Type'], 'int') !== false): ?>
                                <input type="number" name="<?php echo $field; ?>" id="<?php echo $field; ?>" class="form-control" value="<?php echo htmlspecialchars($row[$field]); ?>">
                            <?php elseif (strpos($col['Type'], 'datetime') !== false): ?>
                                <input type="datetime-local" name="<?php echo $field; ?>" id="<?php echo $field; ?>" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($row[$field])); ?>">
                            <?php elseif (strpos($col['Type'], 'date') !== false): ?>
                                <input type="date" name="<?php echo $field; ?>" id="<?php echo $field; ?>" class="form-control" value="<?php echo $row[$field]; ?>">
                            <?php else: ?>
                                <input type="text" name="<?php echo $field; ?>" id="<?php echo $field; ?>" class="form-control" value="<?php echo htmlspecialchars($row[$field]); ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn"><i class="fas fa-save"></i> Сохранить</button>
                    <a href="?table=<?php echo urlencode($table); ?>" class="btn btn-secondary">Назад</a>
                </form>
            </div>
        </main>
    </div>
    <script src="script.js"></script>
</body>
</html>