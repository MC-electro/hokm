<?php
$config = require __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name($config['app']['session_name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

date_default_timezone_set('Asia/Tehran');

spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireLogin(): int
{
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['ok' => false, 'message' => 'ابتدا وارد حساب کاربری شوید.'], 401);
    }
    return (int)$_SESSION['user_id'];
}

function sanitizeText(?string $text, int $max = 255): string
{
    $text = trim((string)$text);
    $text = preg_replace('/\s+/', ' ', $text);
    return mb_substr($text, 0, $max);
}

function appUrl(string $path = ''): string
{
    $config = require __DIR__ . '/config.php';
    $base = rtrim((string)($config['app']['base_url'] ?? ''), '/');
    if ($base === '') {
        $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        if (strpos($scriptName, '/public/') !== false) {
            $base = substr($scriptName, 0, strpos($scriptName, '/public/') + 7);
        } elseif (substr($scriptName, -7) === '/public') {
            $base = $scriptName;
        }
        $base = preg_replace('#/api$#', '', $base) ?: '';
    }
    $path = trim($path);
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    if ($path[0] !== '/') {
        $path = '/' . $path;
    }
    return $base . $path;
}
