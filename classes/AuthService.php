<?php

class AuthService
{
    public function register(string $username, string $email, string $password): array
    {
        $pdo = Database::connection();

        if (!preg_match('/^[\p{Arabic}a-zA-Z0-9_]{3,30}$/u', $username)) {
            return ['ok' => false, 'message' => 'نام کاربری معتبر نیست.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'ایمیل معتبر نیست.'];
        }
        if (mb_strlen($password) < 6) {
            return ['ok' => false, 'message' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.'];
        }

        $exists = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
        $exists->execute(['username' => $username, 'email' => $email]);
        if ($exists->fetch()) {
            return ['ok' => false, 'message' => 'نام کاربری یا ایمیل تکراری است.'];
        }

        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)');
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        return ['ok' => true, 'message' => 'ثبت‌نام با موفقیت انجام شد.'];
    }

    public function login(string $identity, string $password): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = :identity OR email = :identity LIMIT 1');
        $stmt->execute(['identity' => $identity]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'message' => 'اطلاعات ورود نادرست است.'];
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];

        $update = $pdo->prepare('UPDATE users SET last_seen_at = NOW(), is_online = 1 WHERE id = :id');
        $update->execute(['id' => $user['id']]);

        return ['ok' => true, 'message' => 'خوش آمدید.'];
    }

    public function logout(int $userId): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE users SET is_online = 0 WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $_SESSION = [];
        session_destroy();
    }

    public function touchOnline(int $userId): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE users SET last_seen_at = NOW(), is_online = 1 WHERE id = :id');
        $stmt->execute(['id' => $userId]);
    }
}
