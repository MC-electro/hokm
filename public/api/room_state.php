<?php
require_once __DIR__ . '/../../config/bootstrap.php';
$userId = requireLogin();
(new AuthService())->touchOnline($userId);

$roomId = (int)($_GET['room_id'] ?? 0);
$result = (new RoomService())->roomSnapshot($roomId, $userId);
jsonResponse($result, $result['ok'] ? 200 : 422);
