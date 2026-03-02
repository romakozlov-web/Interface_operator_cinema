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
            
            // Save to server session
            fetch(`set_theme.php?theme=${newTheme}`)
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
                // Get current DB from server
                fetch('get_current_db.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.db) {
                            window.open(
                                `export.php?db=${encodeURIComponent(data.db)}&table=${encodeURIComponent(tableName)}&format=${format}`,
                                '_blank'
                            );
                        }
                    })
                    .catch(error => {
                        console.error('Error getting current DB:', error);
                        // Try export without DB
                        window.open(
                            `export.php?table=${encodeURIComponent(tableName)}&format=${format}`,
                            '_blank'
                        );
                    });
            } else {
                window.open(
                    `export.php?db=${encodeURIComponent(db)}&table=${encodeURIComponent(tableName)}&format=${format}`,
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
                const response = await fetch('check_connection.php');
                const data = await response.json();
                
                if (!data.success) {
                    NotificationManager.show(
                        'Connection error: ' + data.message,
                        'error'
                    );
                }
            } catch (error) {
                console.error('Connection check error:', error);
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
     * Initialize on DOM load
     */
    document.addEventListener('DOMContentLoaded', function() {
        ThemeManager.init();
        ConnectionManager.check();
        AnimationStyles();
    });

    // Export functions to global scope for onclick handlers
    window.toggleTheme = () => ThemeManager.toggle();
    window.exportTable = (tableName, format) => TableManager.exportTable(tableName, format);
    window.confirmDelete = () => TableManager.confirmDelete();

})();