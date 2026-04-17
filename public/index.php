<?php
require_once __DIR__ . '/../config/bootstrap.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: /public/lobby.php');
    exit;
}
header('Location: /public/login.php');
exit;
