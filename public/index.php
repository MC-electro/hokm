<?php
require_once __DIR__ . '/../config/bootstrap.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: /lobby.php');
    exit;
}
header('Location: /login.php');
exit;
