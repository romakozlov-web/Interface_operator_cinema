<?php
use Cinema\App;
if (!isset($pdo) || !$pdo) { echo '<div class="alert alert-danger">Ошибка БД</div>'; return; }
$customerEmail = $_GET['email'] ?? '';
$bookings = [];
if (!empty($customerEmail)) {
    $stmt = $pdo->prepare("
        SELECT b.*, f.title as film_title, h.name as hall_name, s.date as session_date
        FROM bookings b
        JOIN sessions s ON b.session_id = s.id
        JOIN films f ON s.film_id = f.id
        JOIN halls h ON s.hall_id = h.id
        WHERE b.customer_email = ?
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute([$customerEmail]);
    $bookings = $stmt->fetchAll();
}
?>
<div class="fade-in">
    <div class="card">
        <h3><i class="fas fa-ticket-alt"></i> Мои бронирования</h3>
        <form method="get" class="form" style="margin-bottom:20px;">
            <input type="hidden" name="action" value="my_bookings">
            <div class="form-group">
                <label for="email">Ваш Email</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="ivan@example.com" value="<?= App::escape($customerEmail) ?>" required>
            </div>
            <button type="submit" class="btn"><i class="fas fa-search"></i> Показать</button>
        </form>
        <?php if (!empty($customerEmail)): ?>
            <?php if (empty($bookings)): ?>
                <div class="alert alert-warning">Бронирований не найдено</div>
            <?php else: ?>
                <div class="bookings-list">
                    <?php foreach ($bookings as $b): ?>
                        <div class="booking-card">
                            <div><strong><?= App::escape($b['film_title']) ?></strong> — <?= App::formatDate($b['session_date']) ?>, зал <?= App::escape($b['hall_name']) ?></div>
                            <div>Мест: <?= (int)$b['seats'] ?>, статус: <span class="booking-status status-<?= $b['status'] ?>"><?= App::escape($b['status']) ?></span></div>
                            <div><small>Забронировано: <?= App::formatDate($b['booking_date']) ?></small></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<style>.booking-card { border:1px solid var(--border-color); border-radius:8px; padding:15px; margin-bottom:15px; }</style>