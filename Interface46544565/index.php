<?php

use PDO;
use PDOException;

/**
 * Main entry point for cinema admin panel
 * Follows PSR-1 and PSR-12 standards
 */

require_once 'config.php';
require_once 'functions.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4",
        DB_USER,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("<div style='padding:20px; color:red;'>Connection error: " . $e->getMessage() . "</div>");
}

$currentDb = DEFAULT_DB;
$currentTable = $_GET['table'] ?? '';
$action = $_GET['action'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?php echo escapeOutput($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cinema "Almaz" - Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="https://img.icons8.com/color/48/cinema-.png">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <i class="fas fa-film"></i>
                <h1>Cinema "Almaz" - Admin Panel</h1>
            </div>
            <div class="user-info">
                <span><i class="fas fa-user"></i> <?php echo escapeOutput(DB_USER); ?></span>
                <button class="btn btn-sm" onclick="toggleTheme()" aria-label="Toggle theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </header>

        <div class="main-content">
            <aside class="sidebar">
                <div class="sidebar-section">
                    <h3><i class="fas fa-video"></i> Cinema "Almaz"</h3>
                    <ul class="table-list">
                        <li class="<?php echo $action === 'dashboard' ? 'active' : ''; ?>">
                            <a href="?action=dashboard">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="<?php echo $currentTable === 'films' ? 'active' : ''; ?>">
                            <a href="?table=films">
                                <i class="fas fa-film"></i> Films
                            </a>
                        </li>
                        <li class="<?php echo $currentTable === 'halls' ? 'active' : ''; ?>">
                            <a href="?table=halls">
                                <i class="fas fa-door-open"></i> Halls
                            </a>
                        </li>
                        <li class="<?php echo $currentTable === 'sessions' ? 'active' : ''; ?>">
                            <a href="?table=sessions">
                                <i class="fas fa-clock"></i> Sessions
                            </a>
                        </li>
                        <li class="<?php echo $currentTable === 'bookings' ? 'active' : ''; ?>">
                            <a href="?table=bookings">
                                <i class="fas fa-ticket-alt"></i> Bookings
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="sidebar-section">
                    <h3><i class="fas fa-cog"></i> Management</h3>
                    <a href="?action=add_film" class="btn btn-sm btn-block">
                        <i class="fas fa-plus"></i> Add Film
                    </a>
                    <a href="?action=add_session" class="btn btn-sm btn-block">
                        <i class="fas fa-plus"></i> Add Session
                    </a>
                    <a href="?action=add_hall" class="btn btn-sm btn-block">
                        <i class="fas fa-plus"></i> Add Hall
                    </a>
                </div>
            </aside>

            <main class="content-area">
                <?php
                $viewFile = match($action) {
                    'dashboard' => 'dashboard.php',
                    'add_film' => 'add_film.php',
                    'add_session' => 'add_session.php',
                    'add_hall' => 'add_hall.php',
                    default => $currentTable ? 'table_content.php' : 'dashboard.php'
                };
                
                if (file_exists($viewFile)) {
                    include $viewFile;
                } else {
                    echo '<div class="alert alert-danger">View file not found</div>';
                }
                ?>
            </main>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>