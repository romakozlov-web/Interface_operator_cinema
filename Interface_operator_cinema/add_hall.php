<?php

use Exception;
$pdo = connectToDB(DEFAULT_DB);
if (!$pdo) {
    echo '<div class="alert">Ошибка подключения к БД</div>';
    exit;
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $seats = intval($_POST['seats'] ?? 0);
    $description = $_POST['description'] ?? '';

    if (empty($name) || $seats <= 0) {
        $error = 'Название и количество мест обязательны';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO halls (name, seats, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $seats, $description]);
            $success = true;
        } catch (Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
}
?>
<div class="card">
    <h3><i class="fas fa-plus-circle"></i> Добавить зал</h3>

    <?php if ($success): ?>
        <div class="alert alert-success">Зал добавлен! <a href="?table=halls">Вернуться к списку</a></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" class="form">
        <div class="form-group">
            <label for="name">Название зала *</label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="seats">Количество мест *</label>
            <input type="number" name="seats" id="seats" class="form-control" required min="1">
        </div>
        <div class="form-group">
            <label for="description">Описание</label>
            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
        </div>
        <button type="submit" class="btn"><i class="fas fa-save"></i> Сохранить</button>
        <a href="?table=halls" class="btn btn-secondary">Отмена</a>
    </form>
</div>