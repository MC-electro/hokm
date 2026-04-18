<?php
require_once __DIR__ . '/../../config/bootstrap.php';
$userId = requireLogin();
(new AuthService())->touchOnline($userId);

$roomId = (int)($_POST['room_id'] ?? 0);
$inviteCode = sanitizeText($_POST['invite_code'] ?? '', 20);
$service = new RoomService();
$result = $service->joinRoom($userId, $roomId, $inviteCode ?: null);
jsonResponse($result, $result['ok'] ? 200 : 422);
