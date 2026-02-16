<?php
require_once 'config.php';
require_once 'functions.php';

// Проверка подключения к MySQL
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4",
        DB_USER,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("<div style='padding:20px; color:red;'>Ошибка подключения к серверу: " . $e->getMessage() . "</div>");
}

// Получаем список баз данных
try {
    $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $databases = [];
    echo "<div class='alert'>Ошибка получения списка БД: " . $e->getMessage() . "</div>";
}

// Текущая база данных
$current_db = isset($_GET['db']) ? $_GET['db'] : DEFAULT_DB;

// Текущая таблица
$current_table = isset($_GET['table']) ? $_GET['table'] : '';

// Действия
$action = isset($_GET['action']) ? $_GET['action'] : '';
?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySQL Admin - <?php echo htmlspecialchars($current_db); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <i class="fas fa-database"></i>
                <h1>MySQL Admin</h1>
            </div>
            <div class="user-info">
                <span><?php echo htmlspecialchars(DB_USER . '@' . DB_HOST); ?></span>
                <button class="btn btn-sm" onclick="toggleTheme()">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </header>

        <div class="main-content">
            <aside class="sidebar">
                <div class="sidebar-section">
                    <h3><i class="fas fa-database"></i> Базы данных</h3>
                    <ul class="database-list">
                        <?php if (empty($databases)): ?>
                            <li><i class="fas fa-exclamation-triangle"></i> Нет доступных БД</li>
                        <?php else: ?>
                            <?php foreach ($databases as $db): ?>
                                <li class="<?php echo ($db == $current_db) ? 'active' : ''; ?>">
                                    <a href="?db=<?php echo urlencode($db); ?>">
                                        <i class="fas fa-database"></i>
                                        <?php echo htmlspecialchars($db); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <?php if ($current_db): ?>
                    <div class="sidebar-section">
                        <h3><i class="fas fa-table"></i> Таблицы</h3>
                        <?php
                        $tables = [];
                        try {
                            $pdo2 = new PDO(
                                "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . $current_db . ";charset=utf8mb4",
                                DB_USER,
                                DB_PASSWORD,
                                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                            );
                            $tables = $pdo2->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                        } catch (Exception $e) {
                            echo "<p style='color:red;'>Ошибка: " . $e->getMessage() . "</p>";
                        }
                        ?>
                        <ul class="table-list">
                            <?php if (empty($tables)): ?>
                                <li><i>Таблиц нет</i></li>
                            <?php else: ?>
                                <?php foreach ($tables as $table): ?>
                                    <li class="<?php echo ($table == $current_table) ? 'active' : ''; ?>">
                                        <a href="?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($table); ?>">
                                            <i class="fas fa-table"></i>
                                            <?php echo htmlspecialchars($table); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </aside>

            <main class="content-area">
                <?php
                if (!$current_db) {
                    include 'views/dashboard.php';
                } elseif ($current_table) {
                    include 'views/table_content.php';
                } else {
                    include 'views/database_info.php';
                }
                ?>
            </main>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>