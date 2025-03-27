<?php
require_once __DIR__ . '/includes/auth_check.php';

// Verificar permissão
if (!in_array($_SESSION['user_role'], ['admin', 'technician'])) {
    header("Location: dashboard.php");
    exit();
}

// Lógica para CRUD de clientes
?>

<!-- Formulários e tabela de clientes -->