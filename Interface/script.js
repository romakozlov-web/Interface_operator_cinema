/**
 * Переключение темы
 */
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-theme', newTheme);
    
    // Сохраняем в sessionStorage
    sessionStorage.setItem('theme', newTheme);
    
    // Отправляем на сервер для сохранения в сессии
    fetch(`set_theme.php?theme=${newTheme}`)
        .catch(err => console.error('Ошибка сохранения темы:', err));
}

/**
 * Экспорт таблицы
 */
function exportTable(tableName, format) {
    const params = new URLSearchParams(window.location.search);
    const db = params.get('db');
    
    let url = `export.php?db=${encodeURIComponent(db)}&table=${encodeURIComponent(tableName)}&format=${format}`;
    
    window.open(url, '_blank');
}

/**
 * Подтверждение удаления
 */
function confirmDelete() {
    return confirm('Вы уверены, что хотите удалить эту запись?');
}

/**
 * Инициализация при загрузке
 */
document.addEventListener('DOMContentLoaded', function() {
    // Восстанавливаем тему из sessionStorage
    const savedTheme = sessionStorage.getItem('theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
    }
    
    // Проверяем соединение с сервером
    checkConnection();
});

/**
 * Проверка соединения
 */
async function checkConnection() {
    try {
        const response = await fetch('check_connection.php');
        const data = await response.json();
        
        if (!data.success) {
            showNotification('Ошибка подключения к серверу: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Ошибка проверки соединения:', error);
    }
}

/**
 * Показать уведомление
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px;
        background: ${type === 'error' ? '#f44336' : '#4CAF50'};
        color: white;
        border-radius: 4px;
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Добавляем стили для анимации
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);