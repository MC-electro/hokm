<?php
require_once __DIR__ . '/../../config/bootstrap.php';
$userId = requireLogin();
$service = new ChatService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $result = $service->send($roomId, $userId, (string)($_POST['message'] ?? ''));
    jsonResponse($result, $result['ok'] ? 200 : 422);
}

$roomId = (int)($_GET['room_id'] ?? 0);
$since = (int)($_GET['since_id'] ?? 0);
jsonResponse(['ok' => true, 'messages' => $service->fetch($roomId, $since)]);
