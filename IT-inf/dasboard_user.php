<?php
// dashboard_user.php — единый интерфейс с переключением разделов
require 'config.php';
require 'functions.php';
requireLogin();

$userName = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Пользователь');
$userRole = $_SESSION['role'] === 'admin' ? 'Администратор' : 'Специалист';

// Получаем текущий раздел из URL (по умолчанию 'overview')
$page = $_GET['page'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ИТ-Контур — Управление инфраструктурой</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg: #f3f4f6; --card: #ffffff; --text: #1f2937; --muted: #6b7280;
            --border: #e5e7eb; --primary: #2563eb; --sidebar: #1e293b; --sidebar-hover: #334155;
            --success: #10b981; --danger: #ef4444; --warning: #f59e0b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

        /* Боковое меню (темная тема как на скрине) */
        .sidebar {
            width: 260px; background: var(--sidebar); color: #fff;
            display: flex; flex-direction: column; flex-shrink: 0;
        }
        .logo-area { padding: 20px; border-bottom: 1px solid #334155; display: flex; align-items: center; gap: 10px; }
        .logo-icon { width: 32px; height: 32px; background: var(--primary); border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: bold; }
        .logo-text { font-size: 14px; font-weight: 600; line-height: 1.2; }
        
        .menu-container { padding: 15px 10px; flex: 1; overflow-y: auto; }
        .menu-group { margin-bottom: 20px; }
        .menu-title { font-size: 10px; text-transform: uppercase; color: #94a3b8; margin: 0 10px 10px; letter-spacing: 0.5px; }
        .menu-link {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 12px; color: #cbd5e1; text-decoration: none;
            border-radius: 6px; font-size: 13px; transition: 0.2s; margin-bottom: 2px;
        }
        .menu-link:hover { background: var(--sidebar-hover); color: #fff; }
        .menu-link.active { background: var(--primary); color: #fff; }
        .menu-badge { background: var(--danger); color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; font-weight: bold; }

        .sidebar-footer { padding: 15px; border-top: 1px solid #334155; font-size: 11px; color: #94a3b8; display: flex; align-items: center; gap: 8px; }
        .dot-green { width: 8px; height: 8px; background: var(--success); border-radius: 50%; }

        /* Основная часть */
        .main-area { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        
        /* Шапка */
        .topbar {
            background: var(--card); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border);
        }
        .breadcrumbs { font-size: 13px; color: var(--muted); }
        .breadcrumbs b { color: var(--text); }
        .user-block { display: flex; align-items: center; gap: 15px; }
        .avatar { width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; color: var(--primary); display: flex; justify-content: center; align-items: center; font-weight: bold; }
        .user-info { display: flex; flex-direction: column; text-align: right; }
        .user-name { font-size: 13px; font-weight: 600; }
        .user-role { font-size: 11px; color: var(--muted); }
        .logout-btn { color: var(--muted); text-decoration: none; font-size: 13px; margin-left: 10px; }
        .logout-btn:hover { color: var(--danger); }

        /* Контентная область */
        .content { padding: 30px; overflow-y: auto; flex: 1; }
        
        .page-title { font-size: 20px; font-weight: 600; margin-bottom: 20px; }

        /* Сетка дашборда (Обзор) */
        .grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
        .card { background: var(--card); border-radius: 12px; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid var(--border); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-title { font-size: 16px; font-weight: 600; }
        .card-subtitle { font-size: 12px; color: var(--muted); margin-top: 4px; }

        /* Таблицы */
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; padding: 10px; color: var(--muted); font-weight: 500; border-bottom: 1px solid var(--border); }
        td { padding: 12px 10px; border-bottom: 1px solid var(--border); }
        tr:last-child td { border-bottom: none; }

        /* Бейджи */
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .bg-danger { background: #fee2e2; color: #b91c1c; }
        .bg-warning { background: #fef3c7; color: #b45309; }
        .bg-info { background: #e0f2fe; color: #0369a1; }
        .bg-success { background: #dcfce7; color: #15803d; }

        /* Пользовательский аватар в таблице */
        .u-avatar { display: inline-flex; justify-content: center; align-items: center; width: 24px; height: 24px; border-radius: 50%; background: #f1f5f9; font-size: 10px; color: var(--muted); margin-right: 8px; }

        /* Кнопки */
        .btn { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; text-decoration: none; }
        .btn-primary { background: var(--primary); color: #fff; }
    </style>
</head>
<body>

<!-- БОКОВОЕ МЕНЮ -->
<aside class="sidebar">
    <div class="logo-area">
        <div class="logo-icon">N</div>
        <div class="logo-text">ИТ-КОНТУР<br><span style="font-weight:400;font-size:10px;color:#94a3b8;">УПРАВЛЕНИЕ ИНФРАСТРУКТУРОЙ</span></div>
    </div>

    <div class="menu-container">
        <div class="menu-group">
            <div class="menu-title">Рабочее пространство</div>
            <a href="?page=overview" class="menu-link <?= $page === 'overview' ? 'active' : '' ?>">📊 Обзор</a>
        </div>

        <div class="menu-group">
            <div class="menu-title">Управление услугами</div>
            <a href="?page=incidents" class="menu-link <?= $page === 'incidents' ? 'active' : '' ?>">
                🚨 Инциденты <span class="menu-badge">7</span>
            </a>
            <a href="?page=requests" class="menu-link <?= $page === 'requests' ? 'active' : '' ?>">📬 Запросы</a>
        </div>

        <div class="menu-group">
            <div class="menu-title">Активы и конфигурации</div>
            <a href="?page=hardware" class="menu-link <?= $page === 'hardware' ? 'active' : '' ?>">🖥️ Оборудование</a>
            <a href="?page=software" class="menu-link <?= $page === 'software' ? 'active' : '' ?>">💻 ПО и лицензии</a>
            <a href="?page=cmdb" class="menu-link <?= $page === 'cmdb' ? 'active' : '' ?>">🗄️ CMDB</a>
        </div>
    </div>

    <div class="sidebar-footer">
        <div class="dot-green"></div> Все системы работают
    </div>
</aside>

<!-- ОСНОВНАЯ ОБЛАСТЬ -->
<div class="main-area">
    <!-- Шапка -->
    <header class="topbar">
        <div class="breadcrumbs">
            Рабочее пространство / <b><?= match($page) {
                'overview' => 'Обзор',
                'incidents' => 'Инциденты',
                'requests' => 'Запросы',
                'hardware' => 'Оборудование',
                'software' => 'ПО и лицензии',
                'cmdb' => 'CMDB',
                default => 'Раздел'
            } ?></b>
        </div>
        <div class="user-block">
            <div class="avatar"><?= mb_substr($userName, 0, 1) ?></div>
            <div class="user-info">
                <div class="user-name"><?= $userName ?></div>
                <div class="user-role"><?= $userRole ?></div>
            </div>
            <a href="logout.php" class="logout-btn">Выйти</a>
        </div>
    </header>

    <!-- Контент, который меняется в зависимости от раздела -->
    <div class="content">

        <?php if ($page === 'overview'): ?>
            <!-- РАЗДЕЛ: ОБЗОР -->
            <h1 class="page-title">Обзор</h1>
            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Инциденты и запросы</div>
                            <div class="card-subtitle">Динамика за последние 7 дней</div>
                        </div>
                    </div>
                    <div style="height: 200px;"><canvas id="lineChart"></canvas></div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Состояние активов</div>
                            <div class="card-subtitle">Распределение по статусам</div>
                        </div>
                    </div>
                    <div style="height: 200px; position: relative;"><canvas id="doughnutChart"></canvas></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Требуют внимания</div>
                        <div class="card-subtitle">Активные инциденты и их текущий статус</div>
                    </div>
                    <button class="btn btn-primary">+ Создать заявку</button>
                </div>
                <div class="table-wrapper">
                    <table>
                        <tr><th>НОМЕР</th><th>ИНЦИДЕНТ</th><th>ПРИОРИТЕТ</th><th>ОТВЕТСТВЕННЫЙ</th><th>СТАТУС</th><th>ОБНОВЛЕН</th></tr>
                        <tr>
                            <td>INC-1042</td><td>Недоступен кластер базы данных</td>
                            <td><span class="badge bg-danger">Критический</span></td>
                            <td><span class="u-avatar">ИИ</span> Иван Иванов</td>
                            <td><span class="badge bg-danger">Эскалация</span></td><td>10 минут назад</td>
                        </tr>
                        <tr>
                            <td>INC-1038</td><td>Перебои с VPN на площадке СПБ</td>
                            <td><span class="badge bg-warning">Высокий</span></td>
                            <td><span class="u-avatar">АМ</span> Анна Морозова</td>
                            <td><span class="badge bg-warning">В работе</span></td><td>35 минут назад</td>
                        </tr>
                    </table>
                </div>
            </div>

        <?php elseif ($page === 'incidents'): ?>
            <!-- РАЗДЕЛ: ИНЦИДЕНТЫ -->
            <h1 class="page-title">Инциденты</h1>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Все инциденты (7)</div>
                    <button class="btn btn-primary">+ Зарегистрировать инцидент</button>
                </div>
                <div class="table-wrapper">
                    <table>
                        <tr><th>ID</th><th>Тема</th><th>Приоритет</th><th>Статус</th><th>Исполнитель</th><th>Действия</th></tr>
                        <tr><td>INC-1042</td><td>Недоступен кластер БД</td><td><span class="badge bg-danger">Критический</span></td><td>Эскалация</td><td>И. Иванов</td><td><a href="#">Открыть</a></td></tr>
                        <tr><td>INC-1038</td><td>Перебои с VPN</td><td><span class="badge bg-warning">Высокий</span></td><td>В работе</td><td>А. Морозова</td><td><a href="#">Открыть</a></td></tr>
                        <tr><td>INC-1031</td><td>Обновление сертификата</td><td><span class="badge bg-info">Средний</span></td><td>Назначен</td><td>Д. Ким</td><td><a href="#">Открыть</a></td></tr>
                        <tr><td>INC-1027</td><td>Замена диска</td><td><span class="badge bg-success">Низкий</span></td><td>В работе</td><td>Е. Смирнова</td><td><a href="#">Открыть</a></td></tr>
                    </table>
                </div>
            </div>

        <?php elseif ($page === 'requests'): ?>
            <!-- РАЗДЕЛ: ЗАПРОСЫ -->
            <h1 class="page-title">Запросы на обслуживание</h1>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Очередь запросов</div>
                </div>
                <div class="table-wrapper">
                    <table>
                        <tr><th>ID</th><th>Запрос</th><th>Тип</th><th>Статус</th><th>Создан</th></tr>
                        <tr><td>SR-2086</td><td>Доступ к корпоративному VPN</td><td>Доступ</td><td><span class="badge bg-success">Выполнен</span></td><td>Сегодня, 11:42</td></tr>
                        <tr><td>SR-2085</td><td>Установка Microsoft Office</td><td>ПО</td><td><span class="badge bg-warning">В работе</span></td><td>Сегодня, 10:15</td></tr>
                        <tr><td>SR-2084</td><td>Замена картриджа в принтере</td><td>Оборудование</td><td><span class="badge bg-info">Назначен</span></td><td>Вчера, 16:30</td></tr>
                    </table>
                </div>
            </div>

        <?php elseif ($page === 'hardware'): ?>
            <!-- РАЗДЕЛ: ОБОРУДОВАНИЕ -->
            <h1 class="page-title">Оборудование</h1>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Список активов</div>
                    <button class="btn btn-primary">+ Добавить оборудование</button>
                </div>
                <div class="table-wrapper">
                    <table>
                        <tr><th>Инв. номер</th><th>Название</th><th>Тип</th><th>Расположение</th><th>Статус</th></tr>
                        <tr><td>INV-001</td><td>Dell PowerEdge R740</td><td>Сервер</td><td>ДЦ «Север», Стойка 4</td><td><span class="badge bg-success">В эксплуатации</span></td></tr>
                        <tr><td>INV-002</td><td>Cisco Catalyst 9300</td><td>Коммутатор</td><td>Офис СПБ, Серверная</td><td><span class="badge bg-success">В эксплуатации</span></td></tr>
                        <tr><td>INV-003</td><td>HP LaserJet Pro</td><td>Принтер</td><td>Офис Москва, 3 этаж</td><td><span class="badge bg-warning">На обслуживании</span></td></tr>
                    </table>
                </div>
            </div>

        <?php elseif ($page === 'software'): ?>
            <!-- РАЗДЕЛ: ПО И ЛИЦЕНЗИИ -->
            <h1 class="page-title">ПО и лицензии</h1>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Учет лицензий</div>
                </div>
                <div class="table-wrapper">
                    <table>
                        <tr><th>Продукт</th><th>Тип лицензии</th><th>Кол-во</th><th>Истекает</th><th>Статус</th></tr>
                        <tr><td>Microsoft 365 E3</td><td>Подписка</td><td>150</td><td>14 дней</td><td><span class="badge bg-danger">Истекает</span></td></tr>
                        <tr><td>Kaspersky Endpoint</td><td>Подписка</td><td>200</td><td>В след. году</td><td><span class="badge bg-success">Активна</span></td></tr>
                        <tr><td>Windows Server 2022</td><td>Бессрочная</td><td>5</td><td>-</td><td><span class="badge bg-success">Активна</span></td></tr>
                    </table>
                </div>
            </div>

        <?php elseif ($page === 'cmdb'): ?>
            <!-- РАЗДЕЛ: CMDB -->
            <h1 class="page-title">CMDB (База конфигураций)</h1>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Конфигурационные единицы (CI)</div>
                    <button class="btn btn-primary">+ Добавить CI</button>
                </div>
                <div class="table-wrapper">
                    <table>
                        <tr><th>CI Name</th><th>Класс</th><th>Связанные инциденты</th><th>Окружение</th><th>Владелец</th></tr>
                        <tr><td>db-cluster-01</td><td>Кластер БД</td><td>INC-1042</td><td>Production</td><td>И. Иванов</td></tr>
                        <tr><td>vpn-gw-spb</td><td>Шлюз VPN</td><td>INC-1038</td><td>Production</td><td>А. Морозова</td></tr>
                        <tr><td>web-app-front</td><td>Веб-сервер</td><td>Нет</td><td>Staging</td><td>Д. Ким</td></tr>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Скрипты для графиков -->
<script>
    // Если мы на странице "Обзор", рисуем графики
    if (document.getElementById('lineChart')) {
        Chart.defaults.font.family = "system-ui, -apple-system, sans-serif";
        Chart.defaults.color = '#6b7280';

        // График инцидентов
        new Chart(document.getElementById('lineChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
                datasets: [{
                    label: 'Обращения',
                    data: [18, 22, 25, 21, 28, 32, 30],
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37,99,235,0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#2563eb',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { borderDash: [5, 5] } }, x: { grid: { display: false } } } }
        });

        // Кольцевая диаграмма активов
        new Chart(document.getElementById('doughnutChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['В эксплуатации', 'В резерве', 'На обслуживании', 'Прочее'],
                datasets: [{
                    data: [1451, 468, 281, 140],
                    backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#e5e7eb'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '75%',
                plugins: { legend: { position: 'right', labels: { boxWidth: 10, usePointStyle: true } } }
            }
        });
    }
</script>
</body>
</html>