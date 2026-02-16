<?php

use Exception;
use PDO;
$pdo = connectToDB(DEFAULT_DB);
if (!$pdo) {
    echo '<div class="alert alert-danger">Ошибка подключения к БД</div>';
    exit;
}

// Получаем все бронирования с деталями
try {
    $bookings = $pdo->query("
        SELECT b.*, 
               f.title as film_title,
               h.name as hall_name,
               s.date as session_date,
               s.price as session_price
        FROM bookings b
        LEFT JOIN sessions s ON b.session_id = s.id
        LEFT JOIN films f ON s.film_id = f.id
        LEFT JOIN halls h ON s.hall_id = h.id
        ORDER BY b.booking_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    try {
        $bookings = $pdo->query("
            SELECT b.*, 
                   f.title as film_title,
                   h.name as hall_name,
                   s.start_time as session_date,
                   s.price as session_price
            FROM bookings b
            LEFT JOIN sessions s ON b.session_id = s.id
            LEFT JOIN films f ON s.film_id = f.id
            LEFT JOIN halls h ON s.hall_id = h.id
            ORDER BY b.booking_date DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $bookings = [];
    }
}
?>

<div class="fade-in">
    <div class="d-flex justify-between" style="margin-bottom: 20px;">
        <h2><i class="fas fa-ticket-alt"></i> Бронирования</h2>
        <a href="?action=add_booking" class="btn btn-success">
            <i class="fas fa-plus"></i> Новое бронирование
        </a>
    </div>

    <?php if (empty($bookings)): ?>
    <div class="card text-center">
        <i class="fas fa-ticket-alt" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 20px;"></i>
        <p>Нет бронирований</p>
        <a href="?action=add_booking" class="btn btn-success">Создать первое бронирование</a>
    </div>
    <?php else: ?>
    <div class="card">
        <table class="bookings-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Фильм</th>
                    <th>Зал</th>
                    <th>Дата и время</th>
                    <th>Клиент</th>
                    <th>Места</th>
                    <th>Сумма</th>