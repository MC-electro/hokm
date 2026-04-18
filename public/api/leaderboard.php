<?php
require_once __DIR__ . '/../../config/bootstrap.php';
jsonResponse(['ok' => true, 'items' => (new LeaderboardService())->top(100)]);
