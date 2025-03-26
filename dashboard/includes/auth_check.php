<?php
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_role = $_SESSION['user_role'];
$allowed_roles = ['client', 'admin', 'owner'];
if (!in_array($current_role, $allowed_roles)) {
    header("Location: login.php");
    exit();
}