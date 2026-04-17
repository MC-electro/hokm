<?php
require_once __DIR__ . '/../../config/bootstrap.php';
$userId = requireLogin();
$gameId = (int)($_POST['game_id'] ?? 0);
$card = sanitizeText($_POST['card'] ?? '', 20);
$result = (new GameService())->playCard($gameId, $userId, $card);
jsonResponse($result, $result['ok'] ? 200 : 422);
