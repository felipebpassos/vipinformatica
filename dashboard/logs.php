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

// --- FILTROS ---
$filterEventType = isset($_GET['event_type']) ? trim($_GET['event_type']) : '';
$filterUserId = isset($_GET['performed_by']) ? intval($_GET['performed_by']) : 0;
// Novos filtros
$filterEntityType = isset($_GET['entity_type']) ? trim($_GET['entity_type']) : '';
$filterClientAction = isset($_GET['is_client_action']) ? $_GET['is_client_action'] : '';
$filterPeriod = isset($_GET['period']) ? $_GET['period'] : 'all';

// Busca tipos de evento distintos
$eventTypes = [];
$res = $conn->query("SELECT DISTINCT event_type FROM event_logs ORDER BY event_type");
while ($row = $res->fetch_assoc()) {
    $eventTypes[] = $row['event_type'];
}

// Busca usuários distintos que realizaram ações
$users = [];
$res = $conn->query("SELECT DISTINCT u.id, u.name FROM event_logs l JOIN users u ON l.performed_by_user_id = u.id ORDER BY u.name");
while ($row = $res->fetch_assoc()) {
    $users[] = $row;
}
// Entidades possíveis
$entityTypes = ['ticket' => 'Chamado', 'user' => 'Usuário', 'equipment' => 'Equipamento'];

// --- PAGINAÇÃO CONFIGURAÇÃO ---
$perPage = 10;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page'])
    ? (int) $_GET['page']
    : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}
$offset = ($currentPage - 1) * $perPage;

// --- WHERE DINÂMICO ---
$where = [];
$params = [];
$types = '';
if ($filterEventType !== '') {
    $where[] = 'l.event_type = ?';
    $params[] = $filterEventType;
    $types .= 's';
}
if ($filterUserId > 0) {
    $where[] = 'l.performed_by_user_id = ?';
    $params[] = $filterUserId;
    $types .= 'i';
}
if ($filterEntityType !== '') {
    $where[] = 'l.entity_type = ?';
    $params[] = $filterEntityType;
    $types .= 's';
}
if ($filterClientAction !== '' && ($filterClientAction === '1' || $filterClientAction === '0')) {
    $where[] = 'l.is_client_action = ?';
    $params[] = (int) $filterClientAction;
    $types .= 'i';
}
// Filtro de período/data
if ($filterPeriod === 'today') {
    $where[] = 'DATE(l.created_at) = CURDATE()';
} elseif ($filterPeriod === '7days') {
    $where[] = 'l.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
} elseif ($filterPeriod === '30days') {
    $where[] = 'l.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Conta total de logs (com filtro)
$countSql = "SELECT COUNT(*) AS total FROM event_logs l $whereSql";
$countStmt = $conn->prepare($countSql);
if ($types) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

// Calcula total de páginas
$totalPages = (int) ceil($totalRecords / $perPage);

// Carrega logs paginados (com filtro)
$listSql = "SELECT l.*, u.name AS performed_by FROM event_logs l JOIN users u ON l.performed_by_user_id = u.id $whereSql ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($types ? ($listSql) : $listSql);
if ($types) {
    $allParams = array_merge($params, [$perPage, $offset]);
    $stmt->bind_param($types . 'ii', ...$allParams);
} else {
    $stmt->bind_param('ii', $perPage, $offset);
}
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
                <div class="mb-4">
                    <h1 class="text-base font-bold mb-2">Logs de Eventos</h1>
                    <form method="get" class="flex flex-wrap gap-2 items-end">
                        <div>
                            <label for="event_type" class="block text-xs text-gray-600">Tipo de Evento</label>
                            <select name="event_type" id="event_type" class="border rounded px-2 py-1">
                                <option value="">Todos</option>
                                <?php foreach ($eventTypes as $et): ?>
                                    <option value="<?= htmlspecialchars($et) ?>" <?= $filterEventType === $et ? 'selected' : '' ?>><?= htmlspecialchars($et) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="entity_type" class="block text-xs text-gray-600">Entidade</label>
                            <select name="entity_type" id="entity_type" class="border rounded px-2 py-1">
                                <option value="">Todas</option>
                                <?php foreach ($entityTypes as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $filterEntityType === $val ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="performed_by" class="block text-xs text-gray-600">Realizado por</label>
                            <select name="performed_by" id="performed_by" class="border rounded px-2 py-1">
                                <option value="0">Todos</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= $filterUserId == $u['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="is_client_action" class="block text-xs text-gray-600">Ação do Cliente?</label>
                            <select name="is_client_action" id="is_client_action" class="border rounded px-2 py-1">
                                <option value="">Todas</option>
                                <option value="1" <?= $filterClientAction === '1' ? 'selected' : '' ?>>Sim</option>
                                <option value="0" <?= $filterClientAction === '0' ? 'selected' : '' ?>>Não</option>
                            </select>
                        </div>
                        <div>
                            <label for="period" class="block text-xs text-gray-600">Período</label>
                            <select name="period" id="period" class="border rounded px-2 py-1">
                                <option value="today" <?= $filterPeriod === 'today' ? 'selected' : '' ?>>Hoje</option>
                                <option value="7days" <?= $filterPeriod === '7days' ? 'selected' : '' ?>>Últimos 7 dias
                                </option>
                                <option value="30days" <?= $filterPeriod === '30days' ? 'selected' : '' ?>>Últimos 30 dias
                                </option>
                                <option value="all" <?= $filterPeriod === 'all' ? 'selected' : '' ?>>Sempre</option>
                            </select>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                document.querySelectorAll('form select').forEach(function (select) {
                                    select.addEventListener('change', function () {
                                        this.form.submit();
                                    });
                                });
                            });
                        </script>
                    </form>
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