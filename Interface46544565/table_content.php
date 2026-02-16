<?php
use Exception;
use PDO;
if (!$current_db || !$current_table) {
    echo '<div class="alert">Выберите таблицу для просмотра</div>';
    exit;
}

$pdo = connectToDB($current_db);
if (!$pdo) {
    echo '<div class="alert">Ошибка подключения к базе данных</div>';
    exit;
}

// Получаем структуру таблицы для определения полей с датой
$date_columns = [];
try {
    $stmt = $pdo->query("DESCRIBE `$current_table`");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($structure as $col) {
        if (strpos($col['Type'], 'datetime') !== false || strpos($col['Type'], 'date') !== false || strpos($col['Type'], 'timestamp') !== false) {
            $date_columns[] = $col['Field'];
        }
    }
} catch (Exception $e) {
    // Игнорируем ошибки описания
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * ROWS_PER_PAGE;

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$current_table`");
    $totalRows = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $totalPages = ceil($totalRows / ROWS_PER_PAGE);

    $query = "SELECT * FROM `$current_table`";
    
    // Для сеансов добавляем связанные данные
    if ($current_table == 'sessions') {
        $query = "SELECT s.*, f.title as film_title, h.name as hall_name 
                  FROM sessions s 
                  LEFT JOIN films f ON s.film_id = f.id 
                  LEFT JOIN halls h ON s.hall_id = h.id";
    } elseif ($current_table == 'bookings') {
        $query = "SELECT b.*, s.date as session_date, f.title as film_title 
                  FROM bookings b 
                  LEFT JOIN sessions s ON b.session_id = s.id 
                  LEFT JOIN films f ON s.film_id = f.id";
    }
    $query .= " LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', ROWS_PER_PAGE, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $columns = !empty($rows) ? array_keys($rows[0]) : [];
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Ошибка: ' . $e->getMessage() . '</div>';
    exit;
}

$tableInfo = getTableInfo($pdo, $current_table);
?>

<div class="card">
    <h3>Таблица: <?php echo htmlspecialchars($current_table); ?></h3>
    <p><strong>Записей:</strong> <?php echo $tableInfo['rows']; ?></p>
    <p><strong>Размер:</strong> <?php echo $tableInfo['size']; ?></p>
    <button class="btn btn-sm" onclick="exportTable('<?php echo $current_table; ?>', 'csv')">
        <i class="fas fa-download"></i> Экспорт CSV
    </button>
</div>

<div class="card">
    <h3>Данные таблицы</h3>
    
    <?php if (empty($rows)): ?>
        <p>Таблица пуста</p>
    <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <?php foreach ($columns as $col): ?>
                            <th><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; ?>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($row as $key => $value): ?>
                                <td title="<?php echo htmlspecialchars($value); ?>">
                                    <?php 
                                    if ($key == 'poster' && !empty($value)) {
                                        echo '<img src="' . htmlspecialchars($value) . '" style="max-height: 50px; max-width: 50px;">';
                                    } elseif (in_array($key, $date_columns) && $value && $value != '0000-00-00 00:00:00') {
                                        // Пробуем разные форматы даты
                                        $timestamp = strtotime($value);
                                        if ($timestamp !== false) {
                                            echo date('d.m.Y H:i', $timestamp);
                                        } else {
                                            echo htmlspecialchars($value);
                                        }
                                    } else {
                                        echo truncateText(htmlspecialchars($value));
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                            <td>
                                <a href="edit.php?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($current_table); ?>&id=<?php echo $row['id'] ?? ''; ?>" 
                                   class="btn btn-sm"><i class="fas fa-edit"></i></a>
                                <a href="delete.php?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($current_table); ?>&id=<?php echo $row['id'] ?? ''; ?>" 
                                   class="btn btn-sm btn-danger" onclick="return confirmDelete()"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($current_table); ?>&page=<?php echo $page - 1; ?>">&laquo; Назад</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($current_table); ?>&page=<?php echo $i; ?>" 
                       class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($current_table); ?>&page=<?php echo $page + 1; ?>">Вперед &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>