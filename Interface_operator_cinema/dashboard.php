<?php

use Exception;
use PDO;
$pdo = connectToDB(DEFAULT_DB);
if (!$pdo) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Не удалось подключиться к базе данных</div>';
    exit;
}

// Статистика
$filmsCount = $pdo->query("SELECT COUNT(*) FROM films")->fetchColumn();
$hallsCount = $pdo->query("SELECT COUNT(*) FROM halls")->fetchColumn();

// Получаем популярные фильмы
$popularFilms = $pdo->query("
    SELECT f.id, f.title, f.poster, COUNT(b.id) as bookings_count
    FROM films f
    LEFT JOIN sessions s ON f.id = s.film_id
    LEFT JOIN bookings b ON s.id = b.session_id
    GROUP BY f.id
    ORDER BY bookings_count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Сеансы сегодня
try {
    $sessionsToday = $pdo->query("
        SELECT COUNT(*) FROM sessions 
        WHERE DATE(date) = CURDATE()
    ")->fetchColumn();
} catch (Exception $e) {
    try {
        $sessionsToday = $pdo->query("
            SELECT COUNT(*) FROM sessions 
            WHERE DATE(start_time) = CURDATE()
        ")->fetchColumn();
    } catch (Exception $e) {
        $sessionsToday = 0;
    }
}

$bookingsCount = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

// Ближайшие сеансы
try {
    $stmt = $pdo->query("
        SELECT s.id, f.title, f.poster, h.name as hall, s.date as start_time, s.price,
               COUNT(b.id) as booked_seats
        FROM sessions s
        JOIN films f ON s.film_id = f.id
        JOIN halls h ON s.hall_id = h.id
        LEFT JOIN bookings b ON s.id = b.session_id
        WHERE s.date >= NOW()
        GROUP BY s.id
        ORDER BY s.date
        LIMIT 5
    ");
    $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    try {
        $stmt = $pdo->query("
            SELECT s.id, f.title, f.poster, h.name as hall, s.start_time, s.price,
                   COUNT(b.id) as booked_seats
            FROM sessions s
            JOIN films f ON s.film_id = f.id
            JOIN halls h ON s.hall_id = h.id
            LEFT JOIN bookings b ON s.id = b.session_id
            WHERE s.start_time >= NOW()
            GROUP BY s.id
            ORDER BY s.start_time
            LIMIT 5
        ");
        $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $upcoming = [];
    }
}
?>

<div class="fade-in">
    <!-- Приветствие -->
    <div class="card">
        <h3><i class="fas fa-tachometer-alt"></i> Дашборд кинотеатра «Алмаз»</h3>
        <p>Добро пожаловать в панель управления! Здесь вы можете отслеживать статистику и управлять контентом кинотеатра.</p>
    </div>

    <!-- Статистика -->
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-film"></i>
            <span class="stat-number"><?php echo $filmsCount; ?></span>
            <span class="stat-label">Фильмов в прокате</span>
        </div>
        <div class="stat-card">
            <i class="fas fa-door-open"></i>
            <span class="stat-number"><?php echo $hallsCount; ?></span>
            <span class="stat-label">Кинозалов</span>
        </div>
        <div class="stat-card">
            <i class="fas fa-clock"></i>
            <span class="stat-number"><?php echo $sessionsToday; ?></span>
            <span class="stat-label">Сеансов сегодня</span>
        </div>
        <div class="stat-card">
            <i class="fas fa-ticket-alt"></i>
            <span class="stat-number"><?php echo $bookingsCount; ?></span>
            <span class="stat-label">Всего броней</span>
        </div>
    </div>

    <div class="d-flex justify-between" style="margin: 20px 0;">
        <h4><i class="fas fa-calendar-alt"></i> Ближайшие сеансы</h4>
        <a href="?table=sessions" class="btn btn-sm">
            <i class="fas fa-arrow-right"></i> Все сеансы
        </a>
    </div>

    <?php if (!empty($upcoming)): ?>
    <div class="films-grid">
        <?php foreach ($upcoming as $session): ?>
        <div class="film-card">
            <div class="film-poster">
                <?php if (!empty($session['poster'])): ?>
                    <img src="<?php echo htmlspecialchars($session['poster']); ?>" alt="<?php echo htmlspecialchars($session['title']); ?>">
                <?php else: ?>
                    <img src="https://via.placeholder.com/300x200?text=No+Poster" alt="No poster">
                <?php endif; ?>
            </div>
            <div class="film-info">
                <div class="film-title"><?php echo htmlspecialchars($session['title']); ?></div>
                <div class="film-meta">
                    <span><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($session['hall']); ?></span>
                    <span><i class="fas fa-clock"></i> <?php echo date('d.m.Y H:i', strtotime($session['start_time'])); ?></span>
                </div>
                <div class="film-meta">
                    <span><i class="fas fa-ticket-alt"></i> <?php echo $session['booked_seats'] ?? 0; ?> мест забронировано</span>
                    <span><i class="fas fa-ruble-sign"></i> <?php echo number_format($session['price'], 2); ?></span>
                </div>
                <div class="film-actions">
                    <a href="edit.php?table=sessions&id=<?php echo $session['id']; ?>" class="btn btn-sm">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="?action=add_booking&session_id=<?php echo $session['id']; ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Бронь
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="card">
        <p class="text-center">Нет ближайших сеансов</p>
    </div>
    <?php endif; ?>

    <!-- Популярные фильмы -->
    <?php if (!empty($popularFilms)): ?>
    <div class="card mt-4">
        <h4><i class="fas fa-star"></i> Популярные фильмы</h4>
        <div class="stats-grid">
            <?php foreach ($popularFilms as $film): ?>
            <div class="stat-card">
                <i class="fas fa-film"></i>
                <span class="stat-number"><?php echo $film['bookings_count'] ?? 0; ?></span>
                <span class="stat-label"><?php echo htmlspecialchars($film['title']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>