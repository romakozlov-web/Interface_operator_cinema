<?php
function isLoggedIn(): bool { return isset($_SESSION['user_id']); }
function isAdmin(): bool    { return ($_SESSION['role'] ?? '') === 'admin'; }

function redirect(string $url): void { header("Location: $url"); exit; }

function requireLogin(): void {
    if (!isLoggedIn()) redirect('index.php');
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = 'Доступ только для администратора';
        redirect('dashboard_user.php');
    }
}

function csrfToken(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function checkCsrf(): void {
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf'] ?? '')) {
        die('Ошибка CSRF-токена');
    }
}

function flash(string $key, ?string $msg = null): ?string {
    if ($msg !== null) { $_SESSION[$key] = $msg; return null; }
    $v = $_SESSION[$key] ?? null; unset($_SESSION[$key]); return $v;
}

/**
 * Проверка капчи.
 * Возвращает true, если ответ верный.
 */
function checkCaptcha(string $answer): bool
{
    $answer = mb_strtolower(trim($answer));

    // Защита от пустого ответа
    if ($answer === '') return false;

    // Проверяем время жизни капчи (10 минут)
    $time = $_SESSION['captcha_time'] ?? 0;
    if (time() - $time > 600) return false;

    $allowed = $_SESSION['captcha_answers'] ?? [];
    if (!is_array($allowed)) return false;

    // Убираем "ё" из ввода и из допустимых ответов, чтобы не путать пользователя
    $normalize = fn($s) => str_replace('ё', 'е', mb_strtolower(trim($s)));
    $answer    = $normalize($answer);

    foreach ($allowed as $word) {
        if ($normalize($word) === $answer) {
            // Одноразовая капча — после успешной проверки удаляем ответы
            unset($_SESSION['captcha_answers'], $_SESSION['captcha_time']);
            return true;
        }
    }

    return false;
}