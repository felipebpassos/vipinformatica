<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db_connect.php';

// Define a página atual para navegação
$_SESSION['current_page'] = 'logs';

// Verificar permissão (apenas admin e technician)
if (!in_array($_SESSION['user_role'], ['admin', 'technician'])) {
    header("Location: " . $_ENV['APP_URL']);
    exit();
}

// --- PAGINAÇÃO CONFIGURAÇÃO ---
$perPage = 10;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page'])
    ? (int) $_GET['page']
    : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}
$offset = ($currentPage - 1) * $perPage;

// Conta total de logs
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM event_logs");
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

// Calcula total de páginas
$totalPages = (int) ceil($totalRecords / $perPage);

// Carrega logs paginados
$stmt = $conn->prepare(
    "SELECT l.*, u.name AS performed_by
     FROM event_logs l
     JOIN users u ON l.performed_by_user_id = u.id
     ORDER BY l.created_at DESC
     LIMIT ? OFFSET ?"
);
$stmt->bind_param("ii", $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
$logs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico | Vip Informática</title>
    <link rel="icon" type="image/png" href="./assets/img/icon.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-gray-100">
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        <div class="container mx-32 flex-1 px-8 py-6">
            <nav class="flex items-center text-gray-600 mb-4">
                <i class="fas fa-home mr-2"></i><span>Home</span>
                <span class="mx-2 text-gray-400">/</span>
                <span class="font-semibold text-gray-800">Logs de Eventos</span>
            </nav>

            <div class="container p-4 bg-white rounded shadow mb-4">
                <div class="flex justify-between items-center mb-4">
                    <h1 class="text-base font-bold">Logs de Eventos</h1>
                </div>

                <table class="min-w-full bg-white shadow-md rounded mb-0">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">Tipo de Evento</th>
                            <th class="py-2 px-4 border-b">Entidade</th>
                            <th class="py-2 px-4 border-b">ID da Entidade</th>
                            <th class="py-2 px-4 border-b">Realizado por</th>
                            <th class="py-2 px-4 border-b">Ação do Cliente?</th>
                            <th class="py-2 px-4 border-b">Data/Hora</th>
                            <th class="py-2 px-4 border-b">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr class="text-center border-t align-top">
                                <td class="py-2 px-4"><?= htmlspecialchars($log['event_type']) ?></td>
                                <td class="py-2 px-4"><?= htmlspecialchars($log['entity_type']) ?></td>
                                <td class="py-2 px-4"><?= $log['entity_id'] ?></td>
                                <td class="py-2 px-4"><?= htmlspecialchars($log['performed_by']) ?></td>
                                <td class="py-2 px-4"><?= $log['is_client_action'] ? 'Sim' : 'Não' ?></td>
                                <td class="py-2 px-4"><?= $log['created_at'] ?></td>
                                <td class="py-2 px-4 text-left max-w-xs">
                                    <pre class="whitespace-pre-wrap text-xs"><?= htmlspecialchars($log['details']) ?></pre>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="8" class="py-4 text-center text-gray-500">Nenhum registro encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- PAGINAÇÃO -->
                <div class="px-4 py-2 bg-white flex flex-col items-center space-y-2 mt-4">
                    <div class="text-sm text-gray-700">
                        <?php
                        $start = $totalRecords > 0 ? $offset + 1 : 0;
                        $end = $offset + count($logs);
                        echo "{$start} a {$end} de {$totalRecords} registros";
                        ?>
                    </div>
                    <?php if ($totalRecords > 0): ?>
                        <nav aria-label="Paginação">
                            <ul class="inline-flex items-center -space-x-px mt-2">
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <li>
                                        <a href="?page=<?= $p ?>" class="px-3 py-2 border text-sm font-medium
                          <?= $p === $currentPage
                              ? 'bg-red-500 text-white border-red-500'
                              : 'bg-white text-gray-500 border-gray-300 hover:bg-gray-100' ?>">
                                            <?= $p ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>