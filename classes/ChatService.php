<?php

class ChatService
{
    public function send(int $roomId, int $userId, string $message): array
    {
        $pdo = Database::connection();
        $message = sanitizeText($message, 300);
        if ($message === '') {
            return ['ok' => false, 'message' => 'پیام خالی است.'];
        }

        $antiSpam = $pdo->prepare('SELECT created_at FROM chat_messages WHERE room_id = :room_id AND user_id = :user_id ORDER BY id DESC LIMIT 1');
        $antiSpam->execute(['room_id' => $roomId, 'user_id' => $userId]);
        $last = $antiSpam->fetchColumn();
        if ($last && (time() - strtotime((string)$last)) < 1) {
            return ['ok' => false, 'message' => 'ارسال پیام خیلی سریع است.'];
        }

        $stmt = $pdo->prepare('INSERT INTO chat_messages (room_id, user_id, message) VALUES (:room_id, :user_id, :message)');
        $stmt->execute(['room_id' => $roomId, 'user_id' => $userId, 'message' => $message]);

        return ['ok' => true];
    }

    public function fetch(int $roomId, int $sinceId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT c.id, c.message, c.created_at, u.username FROM chat_messages c JOIN users u ON u.id = c.user_id WHERE c.room_id = :room_id AND c.id > :since ORDER BY c.id ASC LIMIT 100');
        $stmt->execute(['room_id' => $roomId, 'since' => $sinceId]);
        return $stmt->fetchAll();
    }
}
