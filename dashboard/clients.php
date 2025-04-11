<?php
require_once __DIR__ . '/includes/auth_check.php';

$_SESSION['current_page'] = 'clients';

// Verificar permissão
if (!in_array($_SESSION['user_role'], ['admin', 'technician'])) {
    header("Location: ". $_ENV['APP_URL']);
    exit();
}

// Lógica para CRUD de clientes
?>

<!-- Formulários e tabela de clientes -->