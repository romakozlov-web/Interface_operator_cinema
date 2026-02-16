<?php
if (!$current_db || !$current_table) {
    echo '<div class="alert">Выберите таблицу для просмотра</div>';
    exit;
}

$pdo = connectToDB($current_db);
if (!$pdo) {
    echo '<div class="alert alert-danger">Ошибка подключения к базе данных</div>';
    exit;
}

// Получаем информацию о таблице
$tableInfo = getTableInfo($pdo, $current_table);

// Пагинация
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * ROWS_PER_PAGE;

// Получаем данные
$stmt = $pdo->prepare("SELECT * FROM `$current_table` LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', ROWS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Общее количество записей для пагинации
$totalRows = $tableInfo['rows'];
$totalPages = ceil($totalRows / ROWS_PER_PAGE);
?>

<div class="card">
    <h3>Таблица: <?php echo htmlspecialchars($current_table); ?></h3>
    <div class="table-info">
        <p><strong>Записей:</strong> <?php echo $tableInfo['rows']; ?></p>
        <p><strong>Размер:</strong> <?php echo $tableInfo['size']; ?></p>
    </div>
    
    <div class="table-actions">
        <a href="?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($current_table); ?>&action=browse" 
           class="btn btn-sm">
            <i class="fas fa-eye"></i> Просмотр
        </a>
        <a href="?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($current_table); ?>&action=structure" 
           class="btn btn-sm">
            <i class="fas fa-sitemap"></i> Структура
        </a>
        <a href="?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($current_table); ?>&action=sql" 
           class="btn btn-sm">
            <i class="fas fa-code"></i> SQL
        </a>
        <button onclick="exportTable('<?php echo $current_table; ?>', 'csv')" class="btn btn-sm">
            <i class="fas fa-download"></i> Экспорт CSV
        </button>
    </div>
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
                        <?php foreach (array_keys($rows[0]) as $column): ?>
                            <th><?php echo htmlspecialchars($column); ?></th>
                        <?php endforeach; ?>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($row as $value): ?>
                                <td title="<?php echo htmlspecialchars($value); ?>">
                                    <?php echo truncateText(htmlspecialchars($value)); ?>
                                </td>
                            <?php endforeach; ?>
                            <td>
                                <a href="?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($current_table); ?>&action=edit&id=<?php echo $row['id'] ?? ''; ?>" 
                                   class="btn btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($current_table); ?>&id=<?php echo $row['id'] ?? ''; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirmDelete()">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($current_table); ?>&page=<?php echo $page - 1; ?>">
                        &laquo; Назад
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
                    <?php echo paginationLink($i, $page, $current_db, $current_table); ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($current_table); ?>&page=<?php echo $page + 1; ?>">
                        Вперед &raquo;
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <div style="margin-top: 20px;">
        <a href="?db=<?php echo urlencode($current_db); ?>&table=<?php echo urlencode($current_table); ?>&action=insert" 
           class="btn">
            <i class="fas fa-plus"></i> Добавить запись
        </a>
    </div>
</div>