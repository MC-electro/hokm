<?php
require_once __DIR__ . '/../../config/bootstrap.php';
$userId = requireLogin();
$gameId = (int)($_POST['game_id'] ?? 0);
$suit = sanitizeText($_POST['suit'] ?? '', 20);
$result = (new GameService())->chooseTrump($gameId, $userId, $suit);
jsonResponse($result, $result['ok'] ? 200 : 422);
