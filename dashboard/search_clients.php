<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db_connect.php';

// Verifica se o usuário tem permissão
if ($_SESSION['user_role'] === 'client') {
    http_response_code(403);
    exit('Acesso negado');
}

header('Content-Type: application/json');

$term = isset($_GET['term']) ? trim($_GET['term']) : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Busca por ID específico
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'client'");
    $stmt->bind_param("i", $id);
} elseif (strlen($term) >= 2) {
    // Busca por termo
    $searchTerm = "%{$term}%";
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE name LIKE ? AND role = 'client' ORDER BY name ASC LIMIT 10");
    $stmt->bind_param("s", $searchTerm);
} else {
    echo json_encode([]);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();
$clients = [];

while ($row = $result->fetch_assoc()) {
    $clients[] = [
        'id' => $row['id'],
        'name' => $row['name']
    ];
}

$stmt->close();
echo json_encode($clients);
?>