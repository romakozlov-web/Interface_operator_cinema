<?php

use Cinema\App;

/**
 * Unified views renderer for both admin and user.
 * Called from index.php with $viewType and $isAdminMode set.
 */

$pdo = App::getConnection();
if (!$pdo) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Не удалось подключиться к базе данных</div>';
    return;
}

/* ==================== DASHBOARD ==================== */
if ($viewType === 'dashboard') {
    $filmsCount = $pdo->query("SELECT COUNT(*) FROM films")->fetchColumn();
    $hallsCount = $pdo->query("SELECT COUNT(*) FROM halls")->fetchColumn();

    $popularFilms = $pdo->query("
        SELECT f.id, f.title, f.poster, COUNT(b.id) as bookings_count
        FROM films f
        LEFT JOIN sessions s ON f.id = s.film_id
        LEFT JOIN bookings b ON s.id = b.session_id
        GROUP BY f.id
        ORDER BY bookings_count DESC LIMIT 5
    ")->fetchAll();

    $sessionsToday = 0;
    try {
        $sessionsToday = $pdo->query("SELECT COUNT(*) FROM sessions WHERE DATE(date) = CURDATE()")->fetchColumn();
    } catch (Exception $e) {
        try { $sessionsToday = $pdo->query("SELECT COUNT(*) FROM sessions WHERE DATE(start_time) = CURDATE()")->fetchColumn(); } catch (Exception $e) {}
    }
    $bookingsCount = $isAdminMode ? $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn() : 0;

    $upcoming = [];
    try {
        $stmt = $pdo->query("
            SELECT s.id, f.title, f.poster, h.name as hall, s.date as start_time, s.price, COUNT(b.id) as booked_seats
            FROM sessions s JOIN films f ON s.film_id = f.id JOIN halls h ON s.hall_id = h.id
            LEFT JOIN bookings b ON s.id = b.session_id
            WHERE s.date >= NOW() GROUP BY s.id ORDER BY s.date LIMIT 10
        ");
        $upcoming = $stmt->fetchAll();
    } catch (Exception $e) {
        try {
            $stmt = $pdo->query("
                SELECT s.id, f.title, f.poster, h.name as hall, s.start_time, s.price, COUNT(b.id) as booked_seats
                FROM sessions s JOIN films f ON s.film_id = f.id JOIN halls h ON s.hall_id = h.id
                LEFT JOIN bookings b ON s.id = b.session_id
                WHERE s.start_time >= NOW() GROUP BY s.id ORDER BY s.start_time LIMIT 10
            ");
            $upcoming = $stmt->fetchAll();
        } catch (Exception $e) {}
    }
    ?>
    <div class="fade-in">
        <div class="card">
            <h3><i class="fas fa-video"></i> Кинотеатр «Алмаз»</h3>
            <p><?php echo $isAdminMode
                ? 'Добро пожаловать в панель управления! Здесь вы можете отслеживать статистику и управлять контентом кинотеатра.'
                : 'Добро пожаловать! Здесь вы можете ознакомиться с расписанием сеансов и информацией о фильмах.'; ?></p>
        </div>
        <div class="stats-grid">
            <div class="stat-card"><i class="fas fa-film"></i><span class="stat-number"><?php echo $filmsCount; ?></span><span class="stat-label">Фильмов в прокате</span></div>
            <div class="stat-card"><i class="fas fa-door-open"></i><span class="stat-number"><?php echo $hallsCount; ?></span><span class="stat-label">Кинозалов</span></div>
            <div class="stat-card"><i class="fas fa-clock"></i><span class="stat-number"><?php echo $sessionsToday; ?></span><span class="stat-label">Сеансов сегодня</span></div>
            <?php if ($isAdminMode): ?>
            <div class="stat-card"><i class="fas fa-ticket-alt"></i><span class="stat-number"><?php echo $bookingsCount; ?></span><span class="stat-label">Всего броней</span></div>
            <?php endif; ?>
        </div>
        <div class="card" style="margin:20px 0;">
            <h4><i class="fas fa-calendar-alt"></i> <?php echo $isAdminMode ? 'Ближайшие сеансы' : 'Расписание сеансов'; ?></h4>
            <?php if (!empty($upcoming)): ?>
            <div class="films-grid">
                <?php foreach ($upcoming as $sess): ?>
                <div class="film-card">
                    <div class="film-poster">
                        <?php if (!empty($sess['poster'])): ?>
                            <img src="<?php echo App::escape($sess['poster']); ?>" alt="">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/300x200?text=No+Poster" alt="">
                        <?php endif; ?>
                    </div>
                    <div class="film-info">
                        <div class="film-title"><?php echo App::escape($sess['title']); ?></div>
                        <div class="film-meta">
                            <span><i class="fas fa-door-open"></i> <?php echo App::escape($sess['hall']); ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo date('d.m.Y H:i', strtotime($sess['start_time'])); ?></span>
                        </div>
                        <div class="film-meta">
                            <?php if ($isAdminMode): ?>
                            <span><i class="fas fa-ticket-alt"></i> <?php echo $sess['booked_seats'] ?? 0; ?> мест</span>
                            <?php endif; ?>
                            <span><i class="fas fa-ruble-sign"></i> <?php echo number_format($sess['price'], 2); ?> ₽</span>
                        </div>
                        <div class="film-actions">
                            <?php if ($isAdminMode): ?>
                            <a href="?action=edit&table=sessions&id=<?php echo $sess['id']; ?>" class="btn btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="?action=add_booking&session_id=<?php echo $sess['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> Бронь</a>
                            <?php else: ?>
                            <a href="?action=add_booking&session_id=<?php echo $sess['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-ticket-alt"></i> Забронировать</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-center">Нет ближайших сеансов</p>
            <?php endif; ?>
        </div>
        <?php if ($isAdminMode && !empty($popularFilms)): ?>
        <div class="card mt-4">
            <h4><i class="fas fa-star"></i> Популярные фильмы</h4>
            <div class="stats-grid">
                <?php foreach ($popularFilms as $film): ?>
                <div class="stat-card">
                    <i class="fas fa-film"></i>
                    <span class="stat-number"><?php echo $film['bookings_count'] ?? 0; ?></span>
                    <span class="stat-label"><?php echo App::escape($film['title']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/* ==================== FILMS ==================== */
elseif ($viewType === 'films') {
    $films = $pdo->query("
        SELECT f.*,
               (SELECT COUNT(DISTINCT s.id) FROM sessions s WHERE s.film_id = f.id) as sessions_count,
               (SELECT COUNT(DISTINCT b.id)
                FROM sessions s2
                LEFT JOIN bookings b ON s2.id = b.session_id
                WHERE s2.film_id = f.id) as bookings_count
        FROM films f
        ORDER BY f.release_date DESC
    ")->fetchAll();
    ?>
    <div class="fade-in">
        <div class="d-flex justify-between" style="margin-bottom:20px;">
            <h2><i class="fas fa-film"></i> Фильмы в прокате</h2>
            <?php if ($isAdminMode): ?>
            <a href="?action=add_film" class="btn"><i class="fas fa-plus"></i> Добавить фильм</a>
            <?php endif; ?>
        </div>
        <?php if (empty($films)): ?>
        <div class="card text-center">
            <i class="fas fa-film" style="font-size:4rem;color:var(--text-muted);margin-bottom:20px;"></i>
            <p>Нет добавленных фильмов</p>
            <?php if ($isAdminMode): ?>
            <a href="?action=add_film" class="btn btn-success">Добавить первый фильм</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="films-grid">
            <?php foreach ($films as $film): ?>
            <div class="film-card">
                <div class="film-poster">
                    <?php if (!empty($film['poster'])): ?>
                        <img src="<?php echo App::escape($film['poster']); ?>" alt="<?php echo App::escape($film['title']); ?>">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/300x450?text=No+Poster" alt="">
                    <?php endif; ?>
                </div>
                <div class="film-info">
                    <div class="film-title"><?php echo App::escape($film['title']); ?></div>
                    <div class="film-meta">
                        <span><i class="fas fa-clock"></i> <?php echo $film['duration']; ?> мин</span>
                        <?php if (!empty($film['release_date'])): ?><span><i class="fas fa-calendar"></i> <?php echo date('d.m.Y', strtotime($film['release_date'])); ?></span><?php endif; ?>
                    </div>
                    <div class="film-meta">
                        <span><i class="fas fa-ticket-alt"></i> <?php echo $film['sessions_count']; ?> сеансов</span>
                        <?php if ($isAdminMode): ?>
                        <span><i class="fas fa-users"></i> <?php echo $film['bookings_count']; ?> броней</span>
                        <?php endif; ?>
                    </div>
                    <div class="film-description"><?php echo App::truncateText(App::escape($film['description'] ?? 'Нет описания'), 100); ?></div>
                    <?php if ($isAdminMode): ?>
                    <div class="film-actions">
                        <a href="?action=edit&table=films&id=<?php echo $film['id']; ?>" class="btn btn-sm"><i class="fas fa-edit"></i> Редактировать</a>
                        <a href="?action=delete&table=films&id=<?php echo $film['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить фильм &laquo;<?php echo addslashes($film['title']); ?>&raquo;?');"><i class="fas fa-trash"></i> Удалить</a>
                        <a href="?table=sessions&film_id=<?php echo $film['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-clock"></i> Сеансы</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/* ==================== HALLS ==================== */
elseif ($viewType === 'halls') {
    $halls = $pdo->query("
        SELECT h.*, COUNT(DISTINCT s.id) as sessions_count, COUNT(DISTINCT b.id) as bookings_count
        FROM halls h
        LEFT JOIN sessions s ON h.id = s.hall_id
        LEFT JOIN bookings b ON s.id = b.session_id
        GROUP BY h.id ORDER BY h.name
    ")->fetchAll();
    ?>
    <div class="fade-in">
        <div class="d-flex justify-between" style="margin-bottom:20px;">
            <h2><i class="fas fa-door-open"></i> Кинозалы</h2>
            <?php if ($isAdminMode): ?>
            <a href="?action=add_hall" class="btn"><i class="fas fa-plus"></i> Добавить зал</a>
            <?php endif; ?>
        </div>
        <?php if (empty($halls)): ?>
        <div class="card text-center">
            <i class="fas fa-door-open" style="font-size:4rem;color:var(--text-muted);margin-bottom:20px;"></i>
            <p>Нет добавленных залов</p>
            <?php if ($isAdminMode): ?>
            <a href="?action=add_hall" class="btn btn-success">Добавить первый зал</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="halls-grid">
            <?php foreach ($halls as $hall): ?>
            <div class="hall-card">
                <div class="hall-header">
                    <span class="hall-name"><?php echo App::escape($hall['name']); ?></span>
                    <span class="hall-seats"><i class="fas fa-chair"></i> <?php echo $hall['seats']; ?> мест</span>
                </div>
                <?php if (!empty($hall['description'])): ?>
                <div class="hall-description"><?php echo App::escape($hall['description']); ?></div>
                <?php endif; ?>
                <div class="hall-stats">
                    <span><i class="fas fa-clock"></i> <?php echo $hall['sessions_count']; ?> сеансов</span>
                    <?php if ($isAdminMode): ?>
                    <span><i class="fas fa-ticket-alt"></i> <?php echo $hall['bookings_count']; ?> броней</span>
                    <?php endif; ?>
                </div>
                <?php if ($isAdminMode): ?>
                <div class="film-actions">
                    <a href="?action=edit&table=halls&id=<?php echo $hall['id']; ?>" class="btn btn-sm"><i class="fas fa-edit"></i> Редактировать</a>
                    <a href="?action=delete&table=halls&id=<?php echo $hall['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить зал &laquo;<?php echo addslashes($hall['name']); ?>&raquo;?');"><i class="fas fa-trash"></i> Удалить</a>
                    <a href="?table=sessions&hall_id=<?php echo $hall['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-clock"></i> Сеансы</a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/* ==================== SESSIONS ==================== */
elseif ($viewType === 'table_content' && $currentTable === 'sessions') {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * ROWS_PER_PAGE;

    try {
        $totalRows = App::getTotalRows($pdo, 'sessions');
        $totalPages = ceil($totalRows / ROWS_PER_PAGE);

        $stmt = $pdo->prepare("
            SELECT s.*, f.title as film_title, h.name as hall_name
            FROM sessions s
            LEFT JOIN films f ON s.film_id = f.id
            LEFT JOIN halls h ON s.hall_id = h.id
            ORDER BY s.date DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', ROWS_PER_PAGE, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Ошибка: ' . App::escape($e->getMessage()) . '</div>';
        return;
    }
    ?>
    <div class="fade-in">
        <?php if ($isAdminMode): ?>
        <div class="d-flex justify-between" style="margin-bottom:20px;">
            <h2><i class="fas fa-clock"></i> Сеансы</h2>
            <a href="?action=add_session" class="btn"><i class="fas fa-plus"></i> Добавить сеанс</a>
        </div>
        <?php endif; ?>
        <div class="card">
            <h3><i class="fas fa-clock"></i> <?php echo $isAdminMode ? 'Управление сеансами' : 'Расписание сеансов'; ?></h3>
            <?php if (empty($rows)): ?>
                <p class="text-center">Нет сеансов</p>
            <?php else: ?>
            <div class="films-grid">
                <?php foreach ($rows as $sess): ?>
                <div class="film-card">
                    <div class="film-poster">
                        <?php if (!empty($sess['poster'])): ?>
                            <img src="<?php echo App::escape($sess['poster']); ?>" alt="">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/300x200?text=No+Poster" alt="">
                        <?php endif; ?>
                    </div>
                    <div class="film-info">
                        <div class="film-title"><?php echo App::escape($sess['film_title'] ?? 'Неизвестный фильм'); ?></div>
                        <div class="film-meta">
                            <span><i class="fas fa-door-open"></i> <?php echo App::escape($sess['hall_name'] ?? 'N/A'); ?></span>
                            <span><i class="fas fa-calendar"></i> <?php echo App::formatDate($sess['date'] ?? $sess['start_time'] ?? ''); ?></span>
                        </div>
                        <div class="film-meta">
                            <span><i class="fas fa-ruble-sign"></i> <?php echo number_format($sess['price'] ?? 0, 2); ?> ₽</span>
                        </div>
                        <div class="film-actions">
                            <?php if ($isAdminMode): ?>
                            <a href="?action=edit&table=sessions&id=<?php echo $sess['id']; ?>" class="btn btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="?action=delete&table=sessions&id=<?php echo $sess['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить сеанс?');"><i class="fas fa-trash"></i></a>
                            <a href="?action=add_booking&session_id=<?php echo $sess['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> Бронь</a>
                            <?php else: ?>
                            <a href="?action=add_booking&session_id=<?php echo $sess['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-ticket-alt"></i> Забронировать</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?table=sessions&page=<?php echo $page-1; ?>">&laquo; Назад</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?table=sessions&page=<?php echo $i; ?>" class="<?php echo $i==$page?'active':''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?table=sessions&page=<?php echo $page+1; ?>">Вперед &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/* ==================== BOOKINGS ==================== */
elseif ($viewType === 'bookings') {
    if (!$isAdminMode) { $viewType = 'dashboard'; return; }

    $dateColumn = 'date';
    try {
        $stmt = $pdo->query("DESCRIBE sessions");
        foreach ($stmt->fetchAll() as $col) {
            if (in_array($col['Field'], ['date','start_time','session_date'])) { $dateColumn = $col['Field']; break; }
        }
    } catch (Exception $e) {}

    try {
        $bookings = $pdo->query("
            SELECT b.*, f.title as film_title, h.name as hall_name, s.{$dateColumn} as session_date, s.price as session_price
            FROM bookings b
            LEFT JOIN sessions s ON b.session_id = s.id
            LEFT JOIN films f ON s.film_id = f.id
            LEFT JOIN halls h ON s.hall_id = h.id
            ORDER BY b.booking_date DESC
        ")->fetchAll();
    } catch (Exception $e) { $bookings = []; }
    ?>
    <div class="fade-in">
        <div class="d-flex justify-between" style="margin-bottom:20px;">
            <h2><i class="fas fa-ticket-alt"></i> Управление бронированиями</h2>
            <a href="?action=add_booking" class="btn btn-success"><i class="fas fa-plus"></i> Новая бронь</a>
        </div>
        <?php if (empty($bookings)): ?>
        <div class="card text-center">
            <i class="fas fa-ticket-alt" style="font-size:4rem;color:var(--text-muted);margin-bottom:20px;"></i>
            <p>Нет бронирований</p>
            <a href="?action=add_booking" class="btn btn-success">Создать первое бронирование</a>
        </div>
        <?php else: ?>
        <div class="card">
            <table class="bookings-table">
                <thead><tr>
                    <th>ID</th><th>Фильм</th><th>Зал</th><th>Дата сеанса</th><th>Клиент</th><th>Мест</th><th>Сумма</th><th>Статус</th><th>Дата брони</th><th>Действия</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td>#<?php echo $b['id']; ?></td>
                        <td><strong><?php echo App::escape($b['film_title'] ?? 'N/A'); ?></strong></td>
                        <td><?php echo App::escape($b['hall_name'] ?? 'N/A'); ?></td>
                        <td><?php echo App::formatDate($b['session_date'] ?? ''); ?></td>
                        <td><?php $cn = trim(($b['customer_name'] ?? '') . ' ' . ($b['customer_phone'] ?? '')); echo App::escape($cn ?: 'Аноним'); ?></td>
                        <td class="text-center"><?php echo (int)($b['seats'] ?? 1); ?></td>
                        <td class="text-right"><?php echo number_format(((float)($b['session_price'] ?? 0)) * ((int)($b['seats'] ?? 1)), 2); ?> ₽</td>
                        <td>
                            <?php
                            $status = $b['status'] ?? 'pending';
                            $statusClass = $status === 'confirmed' ? 'status-confirmed' : ($status === 'cancelled' ? 'status-cancelled' : 'status-pending');
                            ?>
                            <span class="booking-status <?php echo $statusClass; ?>"><?php echo ucfirst(App::escape($status)); ?></span>
                        </td>
                        <td><?php echo App::formatDate($b['booking_date'] ?? ''); ?></td>
                        <td>
                            <div class="d-flex" style="gap:5px;">
                                <a href="?action=edit&table=bookings&id=<?php echo $b['id']; ?>" class="btn btn-sm" title="Редактировать"><i class="fas fa-edit"></i></a>
                                <a href="?action=delete&table=bookings&id=<?php echo $b['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить бронирование?');" title="Удалить"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/* ==================== TABLE CONTENT (generic) ==================== */
elseif ($viewType === 'table_content') {
    $current_db = $_GET['db'] ?? DEFAULT_DB;
    $current_table = $_GET['table'] ?? '';
    if (empty($current_table)) { echo '<div class="alert alert-warning">Table not specified.</div>'; return; }

    $pdo = App::getConnectionToDb($current_db) ?? $pdo;
    $date_columns = App::getDateColumns($pdo, $current_table);
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * ROWS_PER_PAGE;

    try {
        $totalRows = App::getTotalRows($pdo, $current_table);
        $totalPages = ceil($totalRows / ROWS_PER_PAGE);
        $rows = App::getTableData($pdo, $current_table, $page, ROWS_PER_PAGE);
        $columns = !empty($rows) ? array_keys($rows[0]) : [];
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Ошибка: ' . App::escape($e->getMessage()) . '</div>';
        return;
    }
    $tableInfo = App::getTableInfo($pdo, $current_table);
    ?>
    <div class="card">
        <h3>Таблица: <?php echo App::escape($current_table); ?></h3>
        <p><strong>Записей:</strong> <?php echo $tableInfo['rows']; ?></p>
        <p><strong>Размер:</strong> <?php echo $tableInfo['size']; ?></p>
        <?php if ($isAdminMode): ?>
        <button class="btn btn-sm" onclick="exportTable('<?php echo App::escape($current_table); ?>','csv')"><i class="fas fa-download"></i> Экспорт CSV</button>
        <?php endif; ?>
    </div>
    <div class="card">
        <h3>Данные таблицы</h3>
        <?php if (empty($rows)): ?><p>Таблица пуста</p><?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead><tr>
                    <?php foreach ($columns as $col): ?><th><?php echo App::escape($col); ?></th><?php endforeach; ?>
                    <?php if ($isAdminMode): ?><th>Действия</th><?php endif; ?>
                </tr></thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($row as $key => $value): ?>
                        <td title="<?php echo App::escape($value ?? ''); ?>">
                            <?php
                            if ($key == 'poster' && !empty($value)) {
                                echo '<img src="' . App::escape($value) . '" style="max-height:50px;max-width:50px;">';
                            } elseif (in_array($key, $date_columns) && $value && $value != '0000-00-00 00:00:00') {
                                $ts = strtotime($value);
                                echo $ts !== false ? date('d.m.Y H:i', $ts) : App::escape($value);
                            } else {
                                echo App::truncateText(App::escape($value ?? ''));
                            }
                            ?>
                        </td>
                        <?php endforeach; ?>
                        <?php if ($isAdminMode): ?>
                        <td>
                            <a href="?action=edit&table=<?php echo urlencode($current_table); ?>&id=<?php echo $row['id'] ?? ''; ?>" class="btn btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="?action=delete&table=<?php echo urlencode($current_table); ?>&id=<?php echo $row['id'] ?? ''; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить запись?');"><i class="fas fa-trash"></i></a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($current_table); ?>&page=<?php echo $page-1; ?>">&laquo; Назад</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($current_table); ?>&page=<?php echo $i; ?>" class="<?php echo $i==$page?'active':''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($current_table); ?>&page=<?php echo $page+1; ?>">Вперед &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

/* ==================== ADD BOOKING ==================== */
elseif ($viewType === 'add_booking') {
    $sessionId = intval($_GET['session_id'] ?? $_POST['session_id'] ?? 0);
    $dateColumn = 'date';
    try {
        $stmt = $pdo->query("DESCRIBE sessions");
        foreach ($stmt->fetchAll() as $col) {
            if (in_array($col['Field'], ['date','start_time','session_date'])) { $dateColumn = $col['Field']; break; }
        }
    } catch (Exception $e) {}

    $session = null;
    if ($sessionId > 0) {
        try {
            $stmt = $pdo->prepare("
                SELECT s.*, f.title as film_title, h.name as hall_name, h.seats as hall_seats
                FROM sessions s
                JOIN films f ON s.film_id = f.id
                JOIN halls h ON s.hall_id = h.id
                WHERE s.id = ?
            ");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();
        } catch (Exception $e) {}
    }

    $success = false; $error = '';
    $form = ['customer_name'=>'','customer_email'=>'','seats'=>1];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sessionId > 0) {
        $form['customer_name'] = trim($_POST['customer_name'] ?? '');
        $form['customer_email'] = trim($_POST['customer_email'] ?? '');
        $form['seats'] = intval($_POST['seats'] ?? 1);

        if (empty($form['customer_name'])) $error = 'Укажите имя';
        elseif ($form['seats'] <= 0) $error = 'Количество мест должно быть > 0';
        elseif (!$session) $error = 'Сеанс не найден';
        else {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO bookings (session_id, customer_name, customer_email, seats, booking_date, status)
                     VALUES (?, ?, ?, ?, NOW(), 'pending')"
                );
                $stmt->execute([$sessionId, $form['customer_name'], $form['customer_email'], $form['seats']]);
                $success = true;
                $form = ['customer_name'=>'','customer_email'=>'','seats'=>1];
            } catch (Exception $e) {
                $error = 'Ошибка: ' . $e->getMessage();
            }
        }
    }

    if (!$session && !$success) {
        // Show session selection if no session_id provided
        $allSessions = $pdo->query("
            SELECT s.id, f.title, h.name as hall, s.{$dateColumn} as session_date, s.price
            FROM sessions s
            JOIN films f ON s.film_id = f.id
            JOIN halls h ON s.hall_id = h.id
            ORDER BY s.{$dateColumn} DESC LIMIT 20
        ")->fetchAll();
        ?>
        <div class="fade-in">
            <div class="d-flex justify-between" style="margin-bottom:20px;">
                <h2><i class="fas fa-ticket-alt"></i> Бронирование</h2>
            </div>
            <?php if (empty($allSessions)): ?>
            <div class="card text-center"><p>Нет доступных сеансов</p></div>
            <?php else: ?>
            <div class="card">
                <h4>Выберите сеанс:</h4>
                <div class="films-grid">
                    <?php foreach ($allSessions as $s): ?>
                    <div class="film-card">
                        <div class="film-info">
                            <div class="film-title"><?php echo App::escape($s['title']); ?></div>
                            <div class="film-meta">
                                <span><i class="fas fa-door-open"></i> <?php echo App::escape($s['hall']); ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo App::formatDate($s['session_date']); ?></span>
                            </div>
                            <div class="film-meta">
                                <span><i class="fas fa-ruble-sign"></i> <?php echo number_format($s['price'], 2); ?> ₽</span>
                            </div>
                            <div class="film-actions">
                                <a href="?action=add_booking&session_id=<?php echo $s['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> Забронировать</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return;
    }
    ?>
    <div class="fade-in">
        <div class="d-flex justify-between" style="margin-bottom:20px;">
            <h2><i class="fas fa-ticket-alt"></i> Бронирование</h2>
        </div>
        <?php if ($success): ?>
        <div class="card">
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> Бронирование оформлено!</div>
            <a href="?table=sessions" class="btn"><i class="fas fa-arrow-left"></i> К сеансам</a>
            <?php if ($isAdminMode): ?>
            <a href="?table=bookings" class="btn btn-sm"><i class="fas fa-list"></i> Все брони</a>
            <?php endif; ?>
        </div>
        <?php elseif ($session): ?>
        <div class="card">
            <h4>Сеанс: <?php echo App::escape($session['film_title']); ?></h4>
            <p><i class="fas fa-door-open"></i> <?php echo App::escape($session['hall_name']); ?>
               &nbsp;|&nbsp; <i class="fas fa-calendar"></i> <?php echo App::formatDate($session[$dateColumn] ?? $session['date'] ?? ''); ?>
               &nbsp;|&nbsp; <i class="fas fa-ruble-sign"></i> <?php echo number_format($session['price'], 2); ?> ₽</p>
        </div>
        <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo App::escape($error); ?></div><?php endif; ?>
        <div class="card">
            <form method="post" class="form">
                <input type="hidden" name="session_id" value="<?php echo $sessionId; ?>">
                <div class="form-group">
                    <label for="customer_name"><i class="fas fa-user"></i> Ваше имя *</label>
                    <input type="text" name="customer_name" id="customer_name" class="form-control" required value="<?php echo App::escape($form['customer_name']); ?>" placeholder="Иван Петров">
                </div>
                <div class="form-group">
                    <label for="customer_email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="customer_email" id="customer_email" class="form-control" value="<?php echo App::escape($form['customer_email']); ?>" placeholder="ivan@example.com">
                </div>
                <div class="form-group">
                    <label for="seats"><i class="fas fa-chair"></i> Количество мест *</label>
                    <input type="number" name="seats" id="seats" class="form-control" required min="1" max="<?php echo (int)($session['hall_seats'] ?? 250); ?>" value="<?php echo $form['seats']; ?>">
                </div>
                <div class="d-flex">
                    <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Забронировать</button>
                    <a href="?table=sessions" class="btn btn-secondary"><i class="fas fa-times"></i> Отмена</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/* ==================== IMPORT (Admin Only) ==================== */
elseif ($viewType === 'import') {
    if (!$isAdminMode) { $viewType = 'dashboard'; return; }
    $message = ''; $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sqlfile'])) {
        $file = $_FILES['sqlfile'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Ошибка загрузки: ' . $file['error'];
        } elseif ($file['size'] > 10*1024*1024) {
            $errors[] = 'Файл больше 10 МБ';
        } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'sql') {
            $errors[] = 'Только .sql файлы';
        } else {
            $content = file_get_contents($file['tmp_name']);
            if ($content === false) {
                $errors[] = 'Невозможно прочитать файл';
            } else {
                $errs = App::importSql($pdo, $content);
                if (empty($errs)) { $message = 'SQL файл импортирован успешно!'; }
                else { $errors = $errs; }
            }
        }
    }
    ?>
    <div class="fade-in">
        <div class="card">
            <h3><i class="fas fa-database"></i> Импорт SQL файла</h3>
            <p>Загрузите <code>.sql</code> файл для выполнения в текущей базе данных.</p>
            <?php if ($message): ?><div class="alert alert-success"><?php echo App::escape($message); ?></div><?php endif; ?>
            <?php if (!empty($errors)): ?><div class="alert alert-danger"><ul><?php foreach ($errors as $e): ?><li><?php echo App::escape($e); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" class="form">
                <div class="form-group">
                    <label for="sqlfile"><i class="fas fa-file-upload"></i> Выберите SQL файл:</label>
                    <input type="file" name="sqlfile" id="sqlfile" accept=".sql" required class="form-control">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Вы уверены? Это может изменить базу данных.')"><i class="fas fa-play"></i> Выполнить SQL</button>
                    <a href="?action=dashboard" class="btn btn-secondary">Отмена</a>
                </div>
            </form>
        </div>
        <div class="card">
            <h4>Инструкция</h4>
            <ul>
                <li>Загрузите текстовый файл с расширением .sql.</li>
                <li>Файл должен содержать SQL-операторы, разделённые точкой с запятой.</li>
                <li>Комментарии (строки с -- или #) игнорируются.</li>
                <li>Максимальный размер: 10 МБ.</li>
            </ul>
        </div>
    </div>
    <?php
}
?>
