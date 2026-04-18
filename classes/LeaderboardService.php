<?php

class LeaderboardService
{
    public function top(int $limit = 100): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT l.*, u.username FROM leaderboard l JOIN users u ON u.id = l.user_id ORDER BY l.points DESC, l.wins DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
