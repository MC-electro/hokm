<?php
require_once __DIR__ . '/../../config/bootstrap.php';
$userId = requireLogin();
$roomId = (int)($_POST['room_id'] ?? 0);
$targetPoints = (int)($_POST['target_points'] ?? 7);

$room = (new RoomService())->roomSnapshot($roomId, $userId);
if (!$room['ok']) {
    jsonResponse($room, 422);
}
if ((int)$room['room']['owner_id'] !== $userId) {
    jsonResponse(['ok' => false, 'message' => 'فقط لیدر اتاق می‌تواند بازی را شروع کند.'], 403);
}

$result = (new GameService())->startGame($roomId, $targetPoints);
jsonResponse($result, $result['ok'] ? 200 : 422);
