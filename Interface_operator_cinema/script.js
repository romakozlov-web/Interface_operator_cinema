/**
 * Cinema Admin Panel JavaScript
 * Follows modern JavaScript practices
 * @version 1.0
 */

(function() {
    'use strict';

    /**
     * Theme Manager Module
     */
    const ThemeManager = {
        init: function() {
            const savedTheme = sessionStorage.getItem('theme');
            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
            }
        },

        toggle: function() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            html.setAttribute('data-theme', newTheme);
            sessionStorage.setItem('theme', newTheme);

            fetch(`?action=set_theme&theme=${newTheme}`)
                .catch(error => console.error('Theme save error:', error));
        }
    };

    /**
     * Table Operations Module
     */
    const TableManager = {
        exportTable: function(tableName, format = 'csv') {
            const urlParams = new URLSearchParams(window.location.search);
            let db = urlParams.get('db');

            if (!db) {
                fetch('?action=get_current_db')
                    .then(response => response.json())
                    .then(data => {
                        if (data.db) {
                            window.open(
                                `?action=export&db=${encodeURIComponent(data.db)}&table=${encodeURIComponent(tableName)}&format=${format}`,
                                '_blank'
                            );
                        } else {
                            window.open(
                                `?action=export&table=${encodeURIComponent(tableName)}&format=${format}`,
                                '_blank'
                            );
                        }
                    })
                    .catch(() => {
                        window.open(
                            `?action=export&table=${encodeURIComponent(tableName)}&format=${format}`,
                            '_blank'
                        );
                    });
            } else {
                window.open(
                    `?action=export&db=${encodeURIComponent(db)}&table=${encodeURIComponent(tableName)}&format=${format}`,
                    '_blank'
                );
            }
        },

        confirmDelete: function() {
            return confirm('Are you sure you want to delete this record?');
        }
    };

    /**
     * Connection Check Module
     */
    const ConnectionManager = {
        check: async function() {
            try {
                const response = await fetch('?action=check_connection');
                const data = await response.json();

                if (!data.success) {
                    NotificationManager.show(
                        'Ошибка подключения: ' + data.message,
                        'error'
                    );
                }
            } catch (error) {
                // Silently ignore connection check errors
                console.log('Connection check skipped:', error.message);
            }
        }
    };

    /**
     * Notification Module
     */
    const NotificationManager = {
        show: function(message, type = 'info') {
            const colors = {
                error: '#f44336',
                success: '#4CAF50',
                warning: '#ff9800',
                info: '#2196F3'
            };

            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                background: ${colors[type] || colors.info};
                color: white;
                border-radius: 8px;
                z-index: 1000;
                animation: slideIn 0.3s ease;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }
    };

    /**
     * Animation Styles
     */
    const AnimationStyles = () => {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    };

    /**
     * Authentication & Role Manager Module
     */
    const AuthManager = {
        isAdmin: false,

        init: function() {
            // Check if admin mode is active from body class
            this.isAdmin = document.body.classList.contains('admin-mode');
            this.updateUI();
        },

        showPasswordModal: function() {
            const modal = document.getElementById('passwordModal');
            if (modal) {
                modal.classList.add('active');
                const passwordInput = document.getElementById('adminPassword');
                if (passwordInput) {
                    passwordInput.value = '';
                    passwordInput.focus();
                }
            }
        },

        hidePasswordModal: function() {
            const modal = document.getElementById('passwordModal');
            if (modal) {
                modal.classList.remove('active');
            }
        },

        login: async function(password) {
            try {
                const formData = new FormData();
                formData.append('action', 'login');
                formData.append('password', password);

                console.log('Sending login request...');

                const response = await fetch('auth.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers.get('content-type'));

                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Expected JSON but got:', text.substring(0, 200));
                    throw new Error('Сервер вернул не JSON ответ');
                }

                const data = await response.json();
                console.log('Response data:', data);

                if (data.success) {
                    this.isAdmin = true;
                    this.hidePasswordModal();
                    NotificationManager.show('Успешная авторизация! Перезагрузка...', 'success');
                    
                    // Reload page to show admin panel
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    NotificationManager.show(data.message || 'Неверный пароль', 'error');
                }
            } catch (error) {
                console.error('Login error:', error);
                NotificationManager.show('Ошибка: ' + error.message, 'error');
            }
        },

        logout: function() {
            window.location.href = 'auth.php?action=logout';
        },

        updateUI: function() {
            const adminElements = document.querySelectorAll('.admin-only');
            const userElements = document.querySelectorAll('.user-only');

            if (this.isAdmin) {
                adminElements.forEach(el => el.style.display = '');
                userElements.forEach(el => el.style.display = 'none');
            } else {
                adminElements.forEach(el => el.style.display = 'none');
                userElements.forEach(el => el.style.display = '');
            }
        }
    };

    /**
     * Initialize on DOM load
     */
    document.addEventListener('DOMContentLoaded', function() {
        ThemeManager.init();
        ConnectionManager.check();
        AnimationStyles();
        AuthManager.init();

        // Setup password modal event listeners
        const loginBtn = document.getElementById('adminLoginBtn');
        if (loginBtn) {
            loginBtn.addEventListener('click', () => AuthManager.showPasswordModal());
        }

        const submitPassword = document.getElementById('submitPassword');
        if (submitPassword) {
            submitPassword.addEventListener('click', () => {
                const password = document.getElementById('adminPassword')?.value;
                if (password) {
                    AuthManager.login(password);
                }
            });
        }

        const cancelPassword = document.getElementById('cancelPassword');
        if (cancelPassword) {
            cancelPassword.addEventListener('click', () => AuthManager.hidePasswordModal());
        }

        // Handle Enter key in password input
        const passwordInput = document.getElementById('adminPassword');
        if (passwordInput) {
            passwordInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    const password = passwordInput.value;
                    if (password) {
                        AuthManager.login(password);
                    }
                }
            });
        }

        // Close modal on overlay click
        const modalOverlay = document.getElementById('passwordModal');
        if (modalOverlay) {
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) {
                    AuthManager.hidePasswordModal();
                }
            });
        }
    });

    // Export functions to global scope for onclick handlers
    window.toggleTheme = () => ThemeManager.toggle();
    window.exportTable = (tableName, format) => TableManager.exportTable(tableName, format);
    window.confirmDelete = () => TableManager.confirmDelete();
    window.showPasswordModal = () => AuthManager.showPasswordModal();
    window.logoutAdmin = () => AuthManager.logout();

})();