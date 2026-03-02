<?php

use PDO;
$pdo = connectToDB(DEFAULT_DB);
if (!$pdo) {
    echo '<div class="alert alert-danger">Ошибка подключения к БД</div>';
    exit;
}

// Получаем все фильмы
$films = $pdo->query("
    SELECT f.*, 
           COUNT(DISTINCT s.id) as sessions_count,
           COUNT(DISTINCT b.id) as bookings_count
    FROM films f
    LEFT JOIN sessions s ON f.id = s.film_id
    LEFT JOIN bookings b ON s.id = b.session_id
    GROUP BY f.id
    ORDER BY f.release_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="fade-in">
    <div class="d-flex justify-between" style="margin-bottom: 20px;">
        <h2><i class="fas fa-film"></i> Фильмы в прокате</h2>
        <a href="?action=add_film" class="btn">
            <i class="fas fa-plus"></i> Добавить фильм
        </a>
    </div>

    <?php if (empty($films)): ?>
    <div class="card text-center">
        <i class="fas fa-film" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 20px;"></i>
        <p>Нет добавленных фильмов</p>
        <a href="?action=add_film" class="btn btn-success">Добавить первый фильм</a>
    </div>
    <?php else: ?>
    <div class="films-grid">
        <?php foreach ($films as $film): ?>
        <div class="film-card">
            <div class="film-poster">
                <?php if (!empty($film['poster'])): ?>
                    <img src="<?php echo htmlspecialchars($film['poster']); ?>" alt="<?php echo htmlspecialchars($film['title']); ?>">
                <?php else: ?>
                    <img src="https://via.placeholder.com/300x450?text=No+Poster" alt="No poster">
                <?php endif; ?>
            </div>
            <div class="film-info">
                <div class="film-title"><?php echo htmlspecialchars($film['title']); ?></div>
                <div class="film-meta">
                    <span><i class="fas fa-clock"></i> <?php echo $film['duration']; ?> мин</span>
                    <?php if (!empty($film['release_date'])): ?>
                    <span><i class="fas fa-calendar"></i> <?php echo date('d.m.Y', strtotime($film['release_date'])); ?></span>
                    <?php endif; ?>
                </div>
                <div class="film-meta">
                    <span><i class="fas fa-ticket-alt"></i> <?php echo $film['sessions_count']; ?> сеансов</span>
                    <span><i class="fas fa-users"></i> <?php echo $film['bookings_count']; ?> броней</span>
                </div>
                <div class="film-description">
                    <?php echo htmlspecialchars(truncateText($film['description'] ?? 'Нет описания', 100)); ?>
                </div>
                <div class="film-actions">
                    <a href="edit.php?table=films&id=<?php echo $film['id']; ?>" class="btn btn-sm">
                        <i class="fas fa-edit"></i> Редактировать
                    </a>
                    <a href="?table=sessions&film_id=<?php echo $film['id']; ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-clock"></i> Сеансы
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>