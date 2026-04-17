<?php
require_once __DIR__ . '/../../config/bootstrap.php';
$userId = requireLogin();
$gameId = (int)($_POST['game_id'] ?? 0);
$team = sanitizeText($_POST['team'] ?? '', 1);
$name = sanitizeText($_POST['name'] ?? '', 50);
$result = (new GameService())->setTeamName($gameId, $userId, $team, $name);
jsonResponse($result, $result['ok'] ? 200 : 422);
