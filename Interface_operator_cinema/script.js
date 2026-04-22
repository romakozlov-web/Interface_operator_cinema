(function() {
    'use strict';

    const ThemeManager = {
        init: function() {
            const savedTheme = sessionStorage.getItem('theme');
            if (savedTheme) document.documentElement.setAttribute('data-theme', savedTheme);
        },
        toggle: function() {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme');
            const newTheme = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            sessionStorage.setItem('theme', newTheme);
            fetch(`?action=set_theme&theme=${newTheme}`).catch(console.error);
        }
    };

    window.toggleTheme = () => ThemeManager.toggle();
    window.exportTable = (table, format = 'csv') => {
        window.open(`?action=export&table=${encodeURIComponent(table)}&format=${format}`, '_blank');
    };
    window.confirmDelete = () => confirm('Удалить запись?');

    document.addEventListener('DOMContentLoaded', () => ThemeManager.init());
})();