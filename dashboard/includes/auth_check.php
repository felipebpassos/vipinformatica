<?php
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $_ENV['APP_URL'] . "/login.php");
    exit();
}

$current_role = $_SESSION['user_role'];
$allowed_roles = ['client', 'admin', 'technician'];
if (!in_array($current_role, $allowed_roles)) {
    header("Location: " . $_ENV['APP_URL'] . "/login.php");
    exit();
}