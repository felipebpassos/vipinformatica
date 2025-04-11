<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once 'includes/db_connect.php';

$_SESSION['current_page'] = 'logs';

if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ". $_ENV['APP_URL']);
    exit();
}

// Consultar event_logs com JOIN em users
$logs = $conn->query("
    SELECT el.*, u.name as performer 
    FROM event_logs el
    JOIN users u ON el.performed_by_user_id = u.id
    ORDER BY el.created_at DESC
");
?>

<!-- Tabela de logs com detalhes -->