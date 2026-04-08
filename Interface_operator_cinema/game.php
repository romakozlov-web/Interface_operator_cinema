<?php
/**
 * Страница с игрой The Boolean Game
 */
?>
<div class="fade-in">
    <div class="card">
        <h3><i class="fas fa-gamepad"></i> The Boolean Game</h3>
        <p>Практикуйся в булевых операциях (union, subtract, intersect, difference) – полезно для дизайнеров и разработчиков.</p>
        <div style="position: relative; padding-bottom: 75%; height: 0; overflow: hidden; margin-top: 20px;">
            <iframe 
                src="https://boolean.method.ac/" 
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);"
                title="The Boolean Game"
                allow="fullscreen"
                loading="lazy">
            </iframe>
        </div>
        <p class="text-muted" style="margin-top: 15px;">
            <i class="fas fa-info-circle"></i> Игра не сохраняет данные на вашем сервере. Управление: union (объединение), subtract (вычитание), intersect (пересечение), difference (разность).
        </p>
    </div>
</div>