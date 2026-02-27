<?php

use PDO;
$pdo = connectToDB(DEFAULT_DB);
if (!$pdo) {
    echo '<div class="alert alert-danger">Ошибка подключения к БД</div>';
    exit;
}

// Получаем все залы со статистикой
$halls = $pdo->query("
    SELECT h.*, 
           COUNT(DISTINCT s.id) as sessions_count,
           COUNT(DISTINCT b.id) as bookings_count
    FROM halls h
    LEFT JOIN sessions s ON h.id = s.hall_id
    LEFT JOIN bookings b ON s.id = b.session_id
    GROUP BY h.id
    ORDER BY h.name
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="fade-in">
    <div class="d-flex justify-between" style="margin-bottom: 20px;">
        <h2><i class="fas fa-door-open"></i> Кинозалы</h2>
        <a href="?action=add_hall" class="btn">
            <i class="fas fa-plus"></i> Добавить зал
        </a>
    </div>

    <?php if (empty($halls)): ?>
    <div class="card text-center">
        <i class="fas fa-door-open" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 20px;"></i>
        <p>Нет добавленных залов</p>
        <a href="?action=add_hall" class="btn btn-success">Добавить первый зал</a>
    </div>
    <?php else: ?>
    <div class="halls-grid">
        <?php foreach ($halls as $hall): ?>
        <div class="hall-card">
            <div class="hall-header">
                <span class="hall-name"><?php echo htmlspecialchars($hall['name']); ?></span>
                <span class="hall-seats">
                    <i class="fas fa-chair"></i> <?php echo $hall['seats']; ?> мест
                </span>
            </div>
            
            <?php if (!empty($hall['description'])): ?>
            <div class="hall-description">
                <?php echo htmlspecialchars($hall['description']); ?>
            </div>
            <?php endif; ?>
            
            <div class="hall-stats">
                <span><i class="fas fa-clock"></i> <?php echo $hall['sessions_count']; ?> сеансов</span>
                <span><i class="fas fa-ticket-alt"></i> <?php echo $hall['bookings_count']; ?> броней</span>
            </div>
            
            <div class="film-actions">
                <a href="edit.php?table=halls&id=<?php echo $hall['id']; ?>" class="btn btn-sm">
                    <i class="fas fa-edit"></i> Редактировать
                </a>
                <a href="?table=sessions&hall_id=<?php echo $hall['id']; ?>" class="btn btn-sm btn-success">
                    <i class="fas fa-clock"></i> Сеансы
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>