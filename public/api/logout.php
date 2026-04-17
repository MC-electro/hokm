<?php
require_once __DIR__ . '/../../config/bootstrap.php';
if (!empty($_SESSION['user_id'])) {
    (new AuthService())->logout((int)$_SESSION['user_id']);
}
jsonResponse(['ok' => true]);
