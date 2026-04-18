<?php
require_once __DIR__ . '/../../config/bootstrap.php';
$userId = requireLogin();
(new AuthService())->touchOnline($userId);

$service = new RoomService();
$result = $service->createRoom($userId, sanitizeText($_POST['name'] ?? 'اتاق جدید', 80), !empty($_POST['is_private']));
jsonResponse($result, $result['ok'] ? 200 : 422);
