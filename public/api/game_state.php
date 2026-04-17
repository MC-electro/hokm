<?php
require_once __DIR__ . '/../../config/bootstrap.php';
$userId = requireLogin();
$roomId = (int)($_GET['room_id'] ?? 0);
$rev = (int)($_GET['revision'] ?? 0);
$result = (new GameService())->getState($roomId, $userId, $rev);
jsonResponse($result, $result['ok'] ? 200 : 422);
