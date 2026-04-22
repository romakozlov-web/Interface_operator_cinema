<?php

use Cinema\App;

/**
 * Main entry point — Cinema App
 * Supports both user and admin modes with database authentication
 */

require_once 'config.php';
require_once 'auth.php';

$isAdminMode = isAdmin();
$isLoggedIn = isUserLoggedIn();

$currentTable = $_GET['table'] ?? '';
$action = $_GET['action'] ?? '';

// Если пользователь не авторизован и пытается зайти на страницу, требующую прав (админка, добавление, импорт), перенаправляем на login
$restrictedActions = ['add_film', 'add_hall', 'add_session', 'import', 'edit', 'delete'];
$isRestricted = ($action && in_array($action, $restrictedActions)) || ($currentTable === 'bookings');
if (!$isLoggedIn && $isRestricted) {
    header('Location: login.php');
    exit;
}
// Если пользователь авторизован, но не админ, и пытается зайти в админ-разделы – тоже редирект
if ($isLoggedIn && !$isAdminMode && $isRestricted) {
    header('Location: index.php?error=access_denied');
    exit;
}

/* ---------- AJAX / Non-HTML actions ---------- */
if ($action === 'check_connection') {
    header('Content-Type: application/json');
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4",
            DB_USER, DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
        echo json_encode(['success' => true, 'message' => 'OK', 'version' => $pdo->query("SELECT VERSION()")->fetchColumn(), 'server' => DB_HOST . ':' . DB_PORT]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_current_db') {
    header('Content-Type: application/json');
    echo json_encode(['db' => DEFAULT_DB]);
    exit;
}

if ($action === 'set_theme') {
    if (isset($_GET['theme'])) $_SESSION['theme'] = $_GET['theme'];
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '?action=dashboard'));
    exit;
}

/* ---------- Admin-only actions ---------- */
if ($action === 'delete' && $isAdminMode) {
    $table = $_GET['table'] ?? '';
    $id = intval($_GET['id'] ?? 0);
    if ($table && $id && App::isTableAllowed($table)) {
        $pdo = App::getConnection();
        if ($pdo) {
            $deleters = [
                'films'    => [App::class, 'deleteFilm'],
                'halls'    => [App::class, 'deleteHall'],
                'sessions' => [App::class, 'deleteSession'],
                'bookings' => [App::class, 'deleteBooking'],
            ];
            if (isset($deleters[$table])) {
                $deleters[$table]($pdo, $id);
            } else {
                $pdo->prepare("DELETE FROM `$table` WHERE id = ?")->execute([$id]);
            }
        }
    }
    header("Location: ?table=" . urlencode($table));
    exit;
}

if ($action === 'export' && $isAdminMode) {
    $table = $_GET['table'] ?? '';
    if (!App::isTableAllowed($table)) { die('Invalid table'); }
    $pdo = App::getConnection();
    if (!$pdo) { die('DB error'); }
    try {
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { die('Error: ' . $e->getMessage()); }
    if (empty($rows)) { die('No data'); }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $table . '_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, array_keys($rows[0]));
    foreach ($rows as $row) fputcsv($output, $row);
    fclose($output);
    exit;
}

/* ---------- Edit (Admin Only) ---------- */
$editData = null;
$columns = [];
if ($action === 'edit' && $isAdminMode) {
    $table = $_GET['table'] ?? '';
    $id = intval($_GET['id'] ?? 0);
    if ($table && $id && App::isTableAllowed($table)) {
        $pdo = App::getConnection();
        if ($pdo) {
            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
            $stmt->execute([$id]);
            $editData = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    if (!$editData) { header('Location: index.php'); exit; }

    $success = false; $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sets = []; $params = [];
        foreach ($columns as $col) {
            $field = $col['Field'];
            if ($field === 'id') continue;
            if (isset($_POST[$field])) {
                $sets[] = "`$field` = ?";
                $params[] = $_POST[$field];
            }
        }
        $params[] = $id;
        try {
            $pdo->prepare("UPDATE `$table` SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            $success = true;
            $editData = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
            $editData->execute([$id]);
            $editData = $editData->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
}

/* ---------- Determine view type ---------- */
$viewType = match (true) {
    $action === 'dashboard' => 'dashboard',
    $action === 'add_film' || $action === 'add_hall' || $action === 'add_session' => 'add_record',
    $action === 'import' => 'import',
    $action === 'add_booking' => 'add_booking',
    $currentTable === 'films' => 'films',
    $currentTable === 'halls' => 'halls',
    $currentTable === 'bookings' => 'bookings',
    $currentTable !== '' => 'table_content',
    default => 'dashboard',
};

/* ---------- Block admin-only views for regular users ---------- */
if (!$isAdminMode && in_array($viewType, ['add_record', 'import', 'edit'])) {
    $viewType = 'dashboard';
    $action = 'dashboard';
}

/* ---------- DB connection for views ---------- */
$pdo = App::getConnection();
if (!$pdo) {
    $viewType = 'error';
}

/* ===================== HTML OUTPUT ===================== */
$theme = $_SESSION['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?php echo App::escape($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кинотеатр "Алмаз" - <?php echo $isAdminMode ? 'Админ Панель' : 'Расписание'; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="https://img.icons8.com/color/48/cinema-.png">
</head>
<body class="<?php echo $isAdminMode ? 'admin-mode' : 'user-mode'; ?>">
    <div class="container">
        <header class="header <?php echo $isAdminMode ? '' : 'user-header'; ?>">
            <div class="logo">
                <i class="fas fa-film"></i>
                <h1>Кинотеатр "Алмаз" - <?php echo $isAdminMode ? 'Админ Панель' : 'Расписание сеансов'; ?></h1>
            </div>
            <div class="user-info">
                <?php if ($isLoggedIn): ?>
                    <span><i class="fas fa-user"></i> <?= App::escape($_SESSION['username'] ?? '') ?>
                        <?php if ($isAdminMode): ?>
                            <span class="user-mode-badge" style="background:var(--danger-color);">Админ</span>
                        <?php endif; ?>
                    </span>
                    <a href="auth.php?action=logout" class="btn btn-sm btn-logout"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-sm"><i class="fas fa-sign-in-alt"></i> Вход</a>
                    <a href="register.php" class="btn btn-sm btn-success"><i class="fas fa-user-plus"></i> Регистрация</a>
                <?php endif; ?>
                <button class="btn btn-sm" onclick="toggleTheme()" aria-label="Toggle theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </header>

        <div class="main-content">
            <aside class="sidebar <?php echo $isAdminMode ? '' : 'user-sidebar'; ?>">
                <div class="sidebar-section">
                    <h3><i class="fas fa-video"></i> Кинотеатр "Алмаз"</h3>
                    <ul class="table-list">
                        <li class="<?php echo $action === 'dashboard' && !$currentTable ? 'active' : ''; ?>">
                            <a href="?action=dashboard"><i class="fas fa-tachometer-alt"></i> <?php echo $isAdminMode ? 'Панель управления' : 'Главная'; ?></a>
                        </li>
                        <li class="<?php echo $currentTable === 'films' ? 'active' : ''; ?>">
                            <a href="?table=films"><i class="fas fa-film"></i> Фильмы</a>
                        </li>
                        <li class="<?php echo $currentTable === 'halls' ? 'active' : ''; ?>">
                            <a href="?table=halls"><i class="fas fa-door-open"></i> Залы</a>
                        </li>
                        <li class="<?php echo $currentTable === 'sessions' ? 'active' : ''; ?>">
                            <a href="?table=sessions"><i class="fas fa-clock"></i> Сеанс</a>
                        </li>
                        <?php if ($isAdminMode): ?>
                        <li class="<?php echo $currentTable === 'bookings' ? 'active' : ''; ?>">
                            <a href="?table=bookings"><i class="fas fa-ticket-alt"></i> Бронирования</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <?php if ($isAdminMode): ?>
                <div class="sidebar-section admin-only">
                    <h3><i class="fas fa-cog"></i> Управление</h3>
                    <a href="?action=add_film" class="btn btn-sm btn-block"><i class="fas fa-plus"></i> Добавить фильм</a>
                    <a href="?action=add_session" class="btn btn-sm btn-block"><i class="fas fa-plus"></i> Добавить сеанс</a>
                    <a href="?action=add_hall" class="btn btn-sm btn-block"><i class="fas fa-plus"></i> Добавить зал</a>
                    <a href="?action=import" class="btn btn-sm btn-block"><i class="fas fa-database"></i> Импорт SQL</a>
                </div>
                <?php endif; ?>

                <!-- Блок с доступом больше не нужен, так как кнопки входа/выхода теперь в шапке -->
            </aside>

            <main class="content-area">

                <?php if ($action === 'edit' && $isAdminMode && $editData): ?>
                <div class="card">
                    <h3>Редактирование <?php echo App::escape($currentTable); ?> — ID <?php echo intval($_GET['id'] ?? 0); ?></h3>
                    <?php if ($success): ?><div class="alert alert-success">Запись обновлена!</div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?php echo App::escape($error); ?></div><?php endif; ?>
                    <form method="post">
                        <?php foreach ($columns as $col): ?>
                            <?php $field = $col['Field']; if ($field === 'id') continue; ?>
                            <div class="form-group">
                                <label for="<?php echo $field; ?>"><?php echo App::escape($field); ?></label>
                                <?php if (strpos($col['Type'], 'text') !== false || strpos($col['Type'], 'blob') !== false): ?>
                                    <textarea name="<?php echo $field; ?>" id="<?php echo $field; ?>" class="form-control" rows="5"><?php echo App::escape($editData[$field]); ?></textarea>
                                <?php elseif (strpos($col['Type'], 'int') !== false): ?>
                                    <input type="number" name="<?php echo $field; ?>" id="<?php echo $field; ?>" class="form-control" value="<?php echo App::escape($editData[$field]); ?>">
                                <?php elseif (strpos($col['Type'], 'datetime') !== false): ?>
                                    <input type="datetime-local" name="<?php echo $field; ?>" id="<?php echo $field; ?>" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($editData[$field])); ?>">
                                <?php elseif (strpos($col['Type'], 'date') !== false): ?>
                                    <input type="date" name="<?php echo $field; ?>" id="<?php echo $field; ?>" class="form-control" value="<?php echo App::escape($editData[$field]); ?>">
                                <?php else: ?>
                                    <input type="text" name="<?php echo $field; ?>" id="<?php echo $field; ?>" class="form-control" value="<?php echo App::escape($editData[$field] ?? ''); ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn"><i class="fas fa-save"></i> Сохранить</button>
                        <a href="?table=<?php echo urlencode($currentTable); ?>" class="btn btn-secondary">Назад</a>
                    </form>
                </div>
                <?php elseif ($isAdminMode && $viewType === 'add_record'): ?>
                    <?php include 'add_record.php'; ?>
                <?php elseif ($viewType === 'error'): ?>
                    <div class="alert alert-danger">Ошибка подключения к БД</div>
                <?php else: ?>
                    <?php include 'views.php'; ?>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>