<?php
require_once __DIR__ . '/../config.php';

$conn = new mysqli(
    $_ENV['DB_HOST'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
    $_ENV['DB_NAME']
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}