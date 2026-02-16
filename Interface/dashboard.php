<div class="card">
    <h3><i class="fas fa-tachometer-alt"></i> Панель управления</h3>
    
    <div class="server-status">
        <h4>Статус сервера</h4>
        <?php
        $connection = checkServerConnection();
        if ($connection['success']):
        ?>
            <div class="status-indicator status-success">
                <i class="fas fa-check-circle"></i>
                <span>Сервер доступен</span>
            </div>
            <p>Подключение к <?php echo DB_HOST . ':' . DB_PORT; ?> установлено успешно.</p>
        <?php else: ?>
            <div class="status-indicator status-error">
                <i class="fas fa-times-circle"></i>
                <span>Сервер недоступен</span>
            </div>
            <p>Ошибка: <?php echo htmlspecialchars($connection['message']); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="quick-stats">
        <h4>Быстрая статистика</h4>
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-database"></i>
                <span class="stat-number"><?php echo count($databases); ?></span>
                <span class="stat-label">Баз данных</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-user"></i>
                <span class="stat-number"><?php echo DB_USER; ?></span>
                <span class="stat-label">Пользователь</span>
            </div>
        </div>
    </div>
    
    <div class="quick-actions">
        <h4>Быстрые действия</h4>
        <div class="actions-grid">
            <a href="?db=<?php echo urlencode(DB_NAME); ?>" class="btn">
                <i class="fas fa-folder-open"></i>
                Открыть project_Kozlov
            </a>
            <a href="?action=create_table&db=<?php echo urlencode(DB_NAME); ?>" class="btn">
                <i class="fas fa-plus-circle"></i>
                Создать таблицу
            </a>
        </div>
    </div>
</div>

<style>
.server-status, .quick-stats, .quick-actions {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}

.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    border-radius: 4px;
    margin: 10px 0;
}

.status-success {
    background-color: #d4edda;
    color: #155724;
}

.status-error {
    background-color: #f8d7da;
    color: #721c24;
}

.stats-grid, .actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.stat-card {
    background: var(--secondary-color);
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid var(--border-color);
}

.stat-card i {
    font-size: 2em;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.stat-number {
    display: block;
    font-size: 1.5em;
    font-weight: bold;
    margin: 5px 0;
}

.stat-label {
    color: #666;
    font-size: 0.9em;
}
</style>