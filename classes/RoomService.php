<?php

class RoomService
{
    public function createRoom(int $ownerId, string $name, bool $isPrivate): array
    {
        $pdo = Database::connection();
        $inviteCode = bin2hex(random_bytes(6));

        $stmt = $pdo->prepare('INSERT INTO rooms (name, owner_id, is_private, invite_code, status) VALUES (:name, :owner_id, :is_private, :invite_code, "waiting")');
        $stmt->execute([
            'name' => $name,
            'owner_id' => $ownerId,
            'is_private' => $isPrivate ? 1 : 0,
            'invite_code' => $inviteCode,
        ]);

        $roomId = (int)$pdo->lastInsertId();
        $seat = $pdo->prepare('INSERT INTO room_players (room_id, user_id, seat_position, role) VALUES (:room_id, :user_id, 0, "leader")');
        $seat->execute(['room_id' => $roomId, 'user_id' => $ownerId]);

        return ['ok' => true, 'room_id' => $roomId, 'invite_code' => $inviteCode];
    }

    public function joinRoom(int $userId, int $roomId, ?string $inviteCode = null): array
    {
        $pdo = Database::connection();
        $roomStmt = $pdo->prepare('SELECT * FROM rooms WHERE id = :id LIMIT 1');
        $roomStmt->execute(['id' => $roomId]);
        $room = $roomStmt->fetch();

        if (!$room) {
            return ['ok' => false, 'message' => 'اتاق پیدا نشد.'];
        }

        if ((int)$room['is_private'] === 1 && $room['invite_code'] !== $inviteCode) {
            return ['ok' => false, 'message' => 'کد دعوت نامعتبر است.'];
        }

        $exists = $pdo->prepare('SELECT id FROM room_players WHERE room_id = :room_id AND user_id = :user_id');
        $exists->execute(['room_id' => $roomId, 'user_id' => $userId]);
        if ($exists->fetch()) {
            return ['ok' => true, 'room_id' => $roomId];
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) c FROM room_players WHERE room_id = :room_id');
        $countStmt->execute(['room_id' => $roomId]);
        $count = (int)$countStmt->fetch()['c'];
        if ($count >= 4) {
            return ['ok' => false, 'message' => 'اتاق پر است.'];
        }

        $seatStmt = $pdo->prepare('SELECT seat_position FROM room_players WHERE room_id = :room_id');
        $seatStmt->execute(['room_id' => $roomId]);
        $taken = array_map(fn($r) => (int)$r['seat_position'], $seatStmt->fetchAll());
        $seat = 0;
        while (in_array($seat, $taken, true)) {
            $seat++;
        }

        $join = $pdo->prepare('INSERT INTO room_players (room_id, user_id, seat_position, role) VALUES (:room_id, :user_id, :seat_position, "player")');
        $join->execute(['room_id' => $roomId, 'user_id' => $userId, 'seat_position' => $seat]);

        return ['ok' => true, 'room_id' => $roomId];
    }

    public function roomSnapshot(int $roomId, int $viewerId): array
    {
        $pdo = Database::connection();
        if ($roomId === 0) {
            $publicRooms = $pdo->query('SELECT r.id, r.name, r.status, COUNT(rp.id) players_count FROM rooms r LEFT JOIN room_players rp ON rp.room_id = r.id WHERE r.is_private = 0 AND r.status IN ("waiting", "playing") GROUP BY r.id ORDER BY r.created_at DESC')->fetchAll();
            $online = $pdo->query('SELECT username FROM users WHERE is_online = 1 ORDER BY last_seen_at DESC LIMIT 25')->fetchAll();
            return ['ok' => true, 'public_rooms' => $publicRooms, 'online_users' => $online];
        }

        $stmt = $pdo->prepare('SELECT r.*, u.username owner_name FROM rooms r JOIN users u ON u.id = r.owner_id WHERE r.id = :id');
        $stmt->execute(['id' => $roomId]);
        $room = $stmt->fetch();

        if (!$room) {
            return ['ok' => false, 'message' => 'اتاق موجود نیست.'];
        }

        $playersStmt = $pdo->prepare('SELECT rp.user_id, rp.seat_position, rp.role, u.username FROM room_players rp JOIN users u ON u.id = rp.user_id WHERE rp.room_id = :room_id ORDER BY rp.seat_position');
        $playersStmt->execute(['room_id' => $roomId]);
        $players = $playersStmt->fetchAll();

        $publicRooms = $pdo->query('SELECT r.id, r.name, r.status, COUNT(rp.id) players_count FROM rooms r LEFT JOIN room_players rp ON rp.room_id = r.id WHERE r.is_private = 0 AND r.status IN ("waiting", "playing") GROUP BY r.id ORDER BY r.created_at DESC')->fetchAll();

        $online = $pdo->query('SELECT username FROM users WHERE is_online = 1 ORDER BY last_seen_at DESC LIMIT 25')->fetchAll();

        $viewerInRoom = array_values(array_filter($players, fn($p) => (int)$p['user_id'] === $viewerId));
        if (!$viewerInRoom) {
            return ['ok' => false, 'message' => 'شما عضو این اتاق نیستید.'];
        }

        return [
            'ok' => true,
            'room' => $room,
            'players' => $players,
            'public_rooms' => $publicRooms,
            'online_users' => $online,
            'invite_link' => '/room.php?id=' . $room['id'] . '&code=' . $room['invite_code'],
        ];
    }
}
