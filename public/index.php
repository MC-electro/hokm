<?php
require_once __DIR__ . '/../config/bootstrap.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . appUrl('/lobby.php'));
    exit;
}
header('Location: ' . appUrl('/login.php'));
exit;
