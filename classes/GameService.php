<?php

class GameService
{
    private array $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
    private array $ranks = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];

    public function startGame(int $roomId): array
    {
        $pdo = Database::connection();
        $players = $this->fetchPlayers($roomId);
        if (count($players) !== 4) {
            return ['ok' => false, 'message' => 'برای شروع بازی باید ۴ بازیکن حضور داشته باشند.'];
        }

        $pdo->beginTransaction();
        try {
            $last = $pdo->prepare('SELECT dealer_position FROM games WHERE room_id = :room_id ORDER BY id DESC LIMIT 1');
            $last->execute(['room_id' => $roomId]);
            $lastDealer = $last->fetchColumn();
            $dealer = $lastDealer === false ? 0 : (((int)$lastDealer + 1) % 4);

            $insert = $pdo->prepare('INSERT INTO games (room_id, status, dealer_position, current_turn, phase, team_a_points, team_b_points, team_a_name, team_b_name, revision) VALUES (:room_id, "active", :dealer, :turn, "team_naming", 0, 0, "تیم آبی", "تیم قرمز", 1)');
            $insert->execute(['room_id' => $roomId, 'dealer' => $dealer, 'turn' => ($dealer + 1) % 4]);
            $gameId = (int)$pdo->lastInsertId();

            $pdo->prepare('UPDATE rooms SET status = "playing" WHERE id = :id')->execute(['id' => $roomId]);
            $this->logMove($gameId, null, 'game_started', ['dealer_position' => $dealer]);

            $pdo->commit();
            return ['ok' => true, 'game_id' => $gameId];
        } catch (Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'شروع بازی ناموفق بود.'];
        }
    }

    public function setTeamName(int $gameId, int $userId, string $team, string $name): array
    {
        $pdo = Database::connection();
        $game = $this->getGame($gameId);
        if (!$game || $game['phase'] !== 'team_naming') {
            return ['ok' => false, 'message' => 'امکان انتخاب نام تیم وجود ندارد.'];
        }

        $roomPlayers = $this->fetchPlayers((int)$game['room_id']);
        $user = null;
        foreach ($roomPlayers as $p) {
            if ((int)$p['user_id'] === $userId) {
                $user = $p;
                break;
            }
        }
        if (!$user) {
            return ['ok' => false, 'message' => 'شما عضو بازی نیستید.'];
        }

        $seat = (int)$user['seat_position'];
        if (($team === 'a' && $seat !== 0) || ($team === 'b' && $seat !== 1)) {
            return ['ok' => false, 'message' => 'شما لیدر این تیم نیستید.'];
        }

        $field = $team === 'a' ? 'team_a_name' : 'team_b_name';
        $stmt = $pdo->prepare("UPDATE games SET $field = :name, revision = revision + 1 WHERE id = :id");
        $stmt->execute(['name' => $name, 'id' => $gameId]);

        $fresh = $this->getGame($gameId);
        if (!empty($fresh['team_a_name']) && !empty($fresh['team_b_name'])) {
            $this->dealNewHand($gameId);
        }

        return ['ok' => true];
    }

    public function dealNewHand(int $gameId): void
    {
        $pdo = Database::connection();
        $game = $this->getGame($gameId);
        if (!$game) {
            return;
        }

        $deck = [];
        foreach ($this->suits as $suit) {
            foreach ($this->ranks as $rank) {
                $deck[] = $suit . '-' . $rank;
            }
        }
        shuffle($deck);

        $hands = [0 => [], 1 => [], 2 => [], 3 => []];
        $i = 0;
        while ($card = array_shift($deck)) {
            $hands[$i % 4][] = $card;
            $i++;
        }

        $handData = [];
        foreach ($hands as $seat => $cards) {
            sort($cards);
            $handData[(string)$seat] = $cards;
        }

        $dealer = (int)$game['dealer_position'];
        $turn = ($dealer + 1) % 4;

        $stmt = $pdo->prepare('UPDATE games SET hands_json = :hands, trump_suit = NULL, current_trick_json = :trick, trick_leader_position = :leader, current_turn = :turn, team_a_tricks = 0, team_b_tricks = 0, phase = "trump_selection", revision = revision + 1 WHERE id = :id');
        $stmt->execute([
            'hands' => json_encode($handData, JSON_UNESCAPED_UNICODE),
            'trick' => json_encode([], JSON_UNESCAPED_UNICODE),
            'leader' => $turn,
            'turn' => $dealer,
            'id' => $gameId,
        ]);
        $this->logMove($gameId, null, 'hand_dealt', ['dealer_position' => $dealer]);
    }

    public function chooseTrump(int $gameId, int $userId, string $suit): array
    {
        if (!in_array($suit, $this->suits, true)) {
            return ['ok' => false, 'message' => 'خال انتخابی نامعتبر است.'];
        }

        $pdo = Database::connection();
        $game = $this->getGame($gameId);
        if (!$game || $game['phase'] !== 'trump_selection') {
            return ['ok' => false, 'message' => 'اکنون نوبت انتخاب حکم نیست.'];
        }

        $players = $this->fetchPlayers((int)$game['room_id']);
        $dealerSeat = (int)$game['dealer_position'];
        $dealerPlayer = array_values(array_filter($players, fn($p) => (int)$p['seat_position'] === $dealerSeat));

        if (!$dealerPlayer || (int)$dealerPlayer[0]['user_id'] !== $userId) {
            return ['ok' => false, 'message' => 'فقط دیلر می‌تواند حکم را انتخاب کند.'];
        }

        $turn = ((int)$game['dealer_position'] + 1) % 4;
        $stmt = $pdo->prepare('UPDATE games SET trump_suit = :suit, phase = "playing", current_turn = :turn_current, trick_leader_position = :turn_leader, revision = revision + 1 WHERE id = :id');
        $stmt->execute([
            'suit' => $suit,
            'turn_current' => $turn,
            'turn_leader' => $turn,
            'id' => $gameId
        ]);
        $this->logMove($gameId, $userId, 'trump_selected', ['trump_suit' => $suit]);

        return ['ok' => true];
    }

    public function playCard(int $gameId, int $userId, string $card): array
    {
        $pdo = Database::connection();
        $game = $this->getGame($gameId);
        if (!$game || $game['phase'] !== 'playing') {
            return ['ok' => false, 'message' => 'اکنون امکان بازی کارت وجود ندارد.'];
        }

        $players = $this->fetchPlayers((int)$game['room_id']);
        $player = array_values(array_filter($players, fn($p) => (int)$p['user_id'] === $userId));
        if (!$player) {
            return ['ok' => false, 'message' => 'بازیکن در این بازی نیست.'];
        }

        $seat = (int)$player[0]['seat_position'];
        if ($seat !== (int)$game['current_turn']) {
            return ['ok' => false, 'message' => 'نوبت شما نیست.'];
        }

        $hands = json_decode($game['hands_json'] ?: '{}', true);
        $currentTrick = json_decode($game['current_trick_json'] ?: '[]', true);

        if (!in_array($card, $hands[(string)$seat] ?? [], true)) {
            return ['ok' => false, 'message' => 'کارت در دست شما نیست.'];
        }

        [$suit] = explode('-', $card);
        if (!empty($currentTrick)) {
            $leadSuit = $currentTrick[0]['suit'];
            if ($suit !== $leadSuit) {
                $hasLeadSuit = false;
                foreach ($hands[(string)$seat] as $handCard) {
                    if (strpos($handCard, $leadSuit . '-') === 0) {
                        $hasLeadSuit = true;
                        break;
                    }
                }
                if ($hasLeadSuit) {
                    return ['ok' => false, 'message' => 'باید از خال دست پیروی کنید.'];
                }
            }
        }

        $hands[(string)$seat] = array_values(array_filter($hands[(string)$seat], fn($c) => $c !== $card));
        [, $rank] = explode('-', $card);
        $currentTrick[] = ['seat' => $seat, 'card' => $card, 'suit' => $suit, 'rank' => $rank];

        $nextTurn = ($seat + 1) % 4;
        $teamATricks = (int)$game['team_a_tricks'];
        $teamBTricks = (int)$game['team_b_tricks'];

        if (count($currentTrick) === 4) {
            $winner = $this->decideTrickWinner($currentTrick, (string)$game['trump_suit']);
            $nextTurn = $winner;
            if (in_array($winner, [0, 2], true)) {
                $teamATricks++;
            } else {
                $teamBTricks++;
            }
            $this->logMove($gameId, $userId, 'trick_finished', ['winner_seat' => $winner, 'trick' => $currentTrick]);
            $currentTrick = [];
        }

        $phase = 'playing';
        $teamAPoints = (int)$game['team_a_points'];
        $teamBPoints = (int)$game['team_b_points'];

        if (empty($hands['0']) && empty($hands['1']) && empty($hands['2']) && empty($hands['3'])) {
            if ($teamATricks > $teamBTricks) {
                $teamAPoints++;
            } else {
                $teamBPoints++;
            }

            $newDealer = (((int)$game['dealer_position']) + 1) % 4;
            $phase = ($teamAPoints >= 7 || $teamBPoints >= 7) ? 'finished' : 'team_naming';
            $stmt = $pdo->prepare('UPDATE games SET hands_json = :hands, current_trick_json = :trick, current_turn = :turn, trick_leader_position = :leader, team_a_tricks = :a_tricks, team_b_tricks = :b_tricks, team_a_points = :a_points, team_b_points = :b_points, dealer_position = :dealer, phase = :phase, status = :status, revision = revision + 1 WHERE id = :id');
            $stmt->execute([
                'hands' => json_encode($hands, JSON_UNESCAPED_UNICODE),
                'trick' => json_encode($currentTrick, JSON_UNESCAPED_UNICODE),
                'turn' => $nextTurn,
                'leader' => $nextTurn,
                'a_tricks' => $teamATricks,
                'b_tricks' => $teamBTricks,
                'a_points' => $teamAPoints,
                'b_points' => $teamBPoints,
                'dealer' => $newDealer,
                'phase' => $phase,
                'status' => $phase === 'finished' ? 'finished' : 'active',
                'id' => $gameId,
            ]);
            $this->logMove($gameId, $userId, 'hand_finished', ['team_a_tricks' => $teamATricks, 'team_b_tricks' => $teamBTricks]);
            if ($phase === 'finished') {
                $this->finalizeLeaderboard($gameId, $teamAPoints, $teamBPoints, $players);
            } else {
                $this->dealNewHand($gameId);
            }
        } else {
            $stmt = $pdo->prepare('UPDATE games SET hands_json = :hands, current_trick_json = :trick, current_turn = :turn, trick_leader_position = :leader, team_a_tricks = :a_tricks, team_b_tricks = :b_tricks, revision = revision + 1 WHERE id = :id');
            $stmt->execute([
                'hands' => json_encode($hands, JSON_UNESCAPED_UNICODE),
                'trick' => json_encode($currentTrick, JSON_UNESCAPED_UNICODE),
                'turn' => $nextTurn,
                'leader' => $nextTurn,
                'a_tricks' => $teamATricks,
                'b_tricks' => $teamBTricks,
                'id' => $gameId,
            ]);
        }

        $this->logMove($gameId, $userId, 'card_played', ['card' => $card, 'seat' => $seat]);
        return ['ok' => true];
    }

    public function getState(int $roomId, int $userId, int $sinceRevision): array
    {
        $pdo = Database::connection();
        $gameStmt = $pdo->prepare('SELECT * FROM games WHERE room_id = :room_id ORDER BY id DESC LIMIT 1');
        $gameStmt->execute(['room_id' => $roomId]);
        $game = $gameStmt->fetch();

        if (!$game) {
            return ['ok' => true, 'has_update' => false, 'state' => null];
        }

        if ((int)$game['revision'] <= $sinceRevision) {
            return ['ok' => true, 'has_update' => false, 'revision' => (int)$game['revision']];
        }

        $players = $this->fetchPlayers((int)$game['room_id']);
        $viewer = array_values(array_filter($players, fn($p) => (int)$p['user_id'] === $userId));
        if (!$viewer) {
            return ['ok' => false, 'message' => 'شما در اتاق نیستید.'];
        }
        $viewerSeat = (int)$viewer[0]['seat_position'];

        $hands = json_decode($game['hands_json'] ?: '{}', true);
        $maskedHands = [];
        $visibleCount = $game['phase'] === 'trump_selection' ? 5 : 13;
        foreach ($players as $p) {
            $seat = (int)$p['seat_position'];
            $cards = $hands[(string)$seat] ?? [];
            $cards = array_slice($cards, 0, $visibleCount);
            $maskedHands[(string)$seat] = $seat === $viewerSeat ? $cards : array_fill(0, count($cards), 'hidden');
        }

        $moves = $pdo->prepare('SELECT id, action, payload_json, created_at FROM game_moves WHERE game_id = :game_id AND id > :since ORDER BY id ASC LIMIT 100');
        $moves->execute(['game_id' => $game['id'], 'since' => 0]);

        return [
            'ok' => true,
            'has_update' => true,
            'revision' => (int)$game['revision'],
            'state' => [
                'game' => $game,
                'players' => $players,
                'hands' => $maskedHands,
                'current_trick' => json_decode($game['current_trick_json'] ?: '[]', true),
                'moves' => $moves->fetchAll(),
            ],
        ];
    }

    private function decideTrickWinner(array $trick, string $trump): int
    {
        $values = array_flip($this->ranks);
        $leadSuit = $trick[0]['suit'];
        $winner = $trick[0];

        foreach ($trick as $play) {
            $winnerTrump = $winner['suit'] === $trump;
            $playTrump = $play['suit'] === $trump;

            if ($playTrump && !$winnerTrump) {
                $winner = $play;
                continue;
            }

            if (($play['suit'] === $winner['suit']) && ($values[$play['rank']] > $values[$winner['rank']])) {
                $winner = $play;
                continue;
            }

            if (!$winnerTrump && !$playTrump && $winner['suit'] !== $leadSuit && $play['suit'] === $leadSuit) {
                $winner = $play;
            }
        }

        return (int)$winner['seat'];
    }

    private function fetchPlayers(int $roomId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT rp.user_id, rp.seat_position, u.username FROM room_players rp JOIN users u ON u.id = rp.user_id WHERE rp.room_id = :room_id ORDER BY rp.seat_position ASC');
        $stmt->execute(['room_id' => $roomId]);
        return $stmt->fetchAll();
    }

    private function getGame(int $gameId): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM games WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $gameId]);
        $game = $stmt->fetch();
        return $game ?: null;
    }

    private function logMove(int $gameId, ?int $userId, string $action, array $payload): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO game_moves (game_id, user_id, action, payload_json) VALUES (:game_id, :user_id, :action, :payload_json)');
        $stmt->execute([
            'game_id' => $gameId,
            'user_id' => $userId,
            'action' => $action,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function finalizeLeaderboard(int $gameId, int $aPoints, int $bPoints, array $players): void
    {
        $pdo = Database::connection();
        $aWin = $aPoints > $bPoints;
        foreach ($players as $player) {
            $seat = (int)$player['seat_position'];
            $teamA = in_array($seat, [0, 2], true);
            $win = ($teamA && $aWin) || (!$teamA && !$aWin);
            $stmt = $pdo->prepare('INSERT INTO leaderboard (user_id, games_played, wins, losses, points) VALUES (:user_id, 1, :wins_insert, :losses_insert, :points_insert)
                ON DUPLICATE KEY UPDATE games_played = games_played + 1, wins = wins + :wins_update, losses = losses + :losses_update, points = points + :points_update');
            $wins = $win ? 1 : 0;
            $losses = $win ? 0 : 1;
            $points = $win ? 30 : 10;
            $stmt->execute([
                'user_id' => $player['user_id'],
                'wins_insert' => $wins,
                'losses_insert' => $losses,
                'points_insert' => $points,
                'wins_update' => $wins,
                'losses_update' => $losses,
                'points_update' => $points,
            ]);
        }
    }
}
