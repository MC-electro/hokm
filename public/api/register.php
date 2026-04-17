<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'message' => 'درخواست نامعتبر است.'], 405);
}

$auth = new AuthService();
$result = $auth->register(
    sanitizeText($_POST['username'] ?? '', 30),
    sanitizeText($_POST['email'] ?? '', 120),
    (string)($_POST['password'] ?? '')
);
jsonResponse($result, $result['ok'] ? 200 : 422);
