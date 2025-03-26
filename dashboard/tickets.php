<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db_connect.php';

// Lógica para listar chamados
if ($_SESSION['user_role'] === 'client') {
    $stmt = $conn->prepare("SELECT * FROM tickets WHERE client_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
} else {
    $stmt = $conn->prepare("SELECT * FROM tickets");
}
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!-- Exibir tabela de chamados com opções de ação -->