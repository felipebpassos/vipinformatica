<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db_connect.php';

// Recupera informações do usuário autenticado
$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['user_role']; // 'admin', 'technician' ou 'client'
$_SESSION['current_page'] = 'tickets';

// --- PAGINAÇÃO CONFIGURAÇÃO ---
$perPage     = 10;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page'])
    ? (int) $_GET['page']
    : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}
$offset = ($currentPage - 1) * $perPage;

// --- FILTROS ---
$filter_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : '';
$filter_service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : '';
$filter_priority = isset($_GET['priority']) ? $_GET['priority'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_period = isset($_GET['period']) ? $_GET['period'] : 'all';

// Monta WHERE dinâmico
$where = [];
$params = [];
$types = '';
if ($userRole === 'client') {
    $where[] = 't.client_id = ?';
    $params[] = $userId;
    $types .= 'i';
} else {
    if ($filter_client_id) {
        $where[] = 't.client_id = ?';
        $params[] = $filter_client_id;
        $types .= 'i';
    }
}
if ($filter_service_id) {
    $where[] = 't.service_id = ?';
    $params[] = $filter_service_id;
    $types .= 'i';
}
if ($filter_priority) {
    $where[] = 't.priority = ?';
    $params[] = $filter_priority;
    $types .= 's';
}
if ($filter_status) {
    $where[] = 't.status = ?';
    $params[] = $filter_status;
    $types .= 's';
}
// Filtro de período
if ($filter_period === 'today') {
    $where[] = 'DATE(t.created_at) = CURDATE()';
} elseif ($filter_period === '7days') {
    $where[] = 't.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
} elseif ($filter_period === '30days') {
    $where[] = 't.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Conta o total de chamados com filtro
if ($userRole === 'client' || $filter_client_id || $filter_service_id || $filter_priority || $filter_status || $filter_period !== 'all') {
    $countSql = "SELECT COUNT(*) AS total FROM tickets t $whereSql";
    $countStmt = $conn->prepare($countSql);
    if ($params) $countStmt->bind_param($types, ...$params);
} else {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM tickets");
}
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = (int) ceil($totalRecords / $perPage);

// Inicializa variável de erros
$errors = [];

// Trata formulários de criação, edição e exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // marca quem está executando
    $conn->query("SET @current_user_id = " . intval($userId));

    $action = $_POST['action'];
    if ($action === 'create_ticket') {
        // criação
        if ($userRole === 'client') {
            $clientId = $userId;
            $priority = 'normal';
        } else {
            $clientId = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
            if (empty($clientId)) {
                $errors[] = 'Selecione um cliente.';
            }
            $priority = $_POST['priority'] ?? 'normal';
        }
        $serviceId         = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $description       = trim($_POST['description'] ?? '');
        $selectedEquipment = $_POST['equipment_ids'] ?? [];
        if (empty($serviceId)) {
            $errors[] = 'Selecione um serviço.';
        }
        if (empty($errors)) {
            $stmt = $conn->prepare("
                INSERT INTO tickets (service_id, client_id, description, priority)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iiss", $serviceId, $clientId, $description, $priority);
            if ($stmt->execute()) {
                $ticketId = $stmt->insert_id;
                $stmt->close();
                if (!empty($selectedEquipment)) {
                    $stmt2 = $conn->prepare("
                        INSERT INTO ticket_equipment (ticket_id, equipment_id)
                        VALUES (?, ?)
                    ");
                    foreach ($selectedEquipment as $equipmentId) {
                        $stmt2->bind_param("ii", $ticketId, $equipmentId);
                        $stmt2->execute();
                    }
                    $stmt2->close();
                }
                header("Location: index.php?page={$currentPage}&success=Chamado+criado+com+sucesso");
                exit;
            } else {
                $errors[] = "Erro ao criar o chamado: " . $stmt->error;
            }
        }

    } elseif ($action === 'edit_ticket') {
        // edição
        $ticketId         = intval($_POST['ticket_id']);
        $serviceId        = intval($_POST['service_id']);
        $description      = trim($_POST['description']);
        $priority         = $_POST['priority'] ?? 'normal';
        $status           = $_POST['status'] ?? 'open';
        $selectedEquipment = $_POST['equipment_ids'] ?? [];

        if ($serviceId <= 0) {
            $errors[] = 'Serviço inválido.';
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("
                UPDATE tickets
                SET service_id = ?, description = ?, priority = ?, status = ?
                WHERE id = ?
            ");
            $stmt->bind_param("isssi", $serviceId, $description, $priority, $status, $ticketId);
            if ($stmt->execute()) {
                $stmt->close();
                // atualiza equipamentos
                $conn->query("DELETE FROM ticket_equipment WHERE ticket_id = $ticketId");
                if (!empty($selectedEquipment)) {
                    $stmt2 = $conn->prepare("
                        INSERT INTO ticket_equipment (ticket_id, equipment_id)
                        VALUES (?, ?)
                    ");
                    foreach ($selectedEquipment as $eqId) {
                        $stmt2->bind_param("ii", $ticketId, $eqId);
                        $stmt2->execute();
                    }
                    $stmt2->close();
                }
                header("Location: index.php?page={$currentPage}&success=Chamado+atualizado+com+sucesso");
                exit;
            } else {
                $errors[] = "Erro ao atualizar: " . $stmt->error;
            }
        }

    } elseif ($action === 'delete_ticket') {
        // exclusão
        $ticketId = intval($_POST['ticket_id']);
        $conn->query("DELETE FROM ticket_equipment WHERE ticket_id = $ticketId");
        if ($conn->query("DELETE FROM tickets WHERE id = $ticketId")) {
            header("Location: index.php?page={$currentPage}&success=Chamado+excluído+com+sucesso");
            exit;
        } else {
            $errors[] = "Erro ao excluir: " . $conn->error;
        }
    }
}

// Carrega listas auxiliares
$services    = [];
$res         = $conn->query("SELECT * FROM services ORDER BY service ASC");
while ($row = $res->fetch_assoc()) {
    $services[] = $row;
}
if ($userRole === 'client') {
    $equipmentsAll = [];
    $res = $conn->query("SELECT * FROM equipment WHERE user_id = $userId ORDER BY type ASC");
    while ($row = $res->fetch_assoc()) {
        $equipmentsAll[] = $row;
    }
} else {
    $equipmentsAll = [];
    $res = $conn->query("
        SELECT e.*, u.name AS client_name
        FROM equipment e
        JOIN users u ON e.user_id = u.id
        ORDER BY u.name, e.type ASC
    ");
    while ($row = $res->fetch_assoc()) {
        $equipmentsAll[] = $row;
    }
}

// Busca chamados paginados com filtro
if ($userRole === 'client' || $filter_client_id || $filter_service_id || $filter_priority || $filter_status || $filter_period !== 'all') {
    if ($userRole === 'client') {
        $sql = "SELECT t.*, s.service FROM tickets t JOIN services s ON t.service_id = s.id $whereSql ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
    } else {
        $sql = "SELECT t.*, s.service, u.name AS client_name FROM tickets t JOIN services s ON t.service_id = s.id JOIN users u ON t.client_id = u.id $whereSql ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
    }
    $stmt = $conn->prepare($sql);
    $bindParams = $params;
    $bindTypes = $types . 'ii';
    $bindParams[] = $perPage;
    $bindParams[] = $offset;
    $stmt->bind_param($bindTypes, ...$bindParams);
} else {
    $stmt = $conn->prepare("SELECT t.*, s.service, u.name AS client_name FROM tickets t JOIN services s ON t.service_id = s.id JOIN users u ON t.client_id = u.id ORDER BY t.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $perPage, $offset);
}
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$priorityMap = [
    'normal'        => 'Normal',
    'high' => 'Alta',
];

$statusMap = [
    'open'        => 'Aberto',
    'in_progress' => 'Em progresso',
    'closed'      => 'Finalizado',
];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chamados | Vip Informática</title>
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
                <span class="font-semibold text-gray-800">Chamados</span>
            </nav>

            <div class="container p-4 bg-white rounded shadow mb-4">
                <div class="flex justify-between items-center mb-4">
                    <?php if ($userRole === 'client'): ?>
                        <h1 class="text-base font-bold">Meus chamados</h1>
                    <?php else: ?>
                        <h1 class="text-base font-bold">Ordens de serviço</h1>
                    <?php endif; ?>
                    <button id="btn-open-create"
                        class="bg-red-500 text-white text-sm px-4 py-2 rounded hover:bg-red-600 flex items-center gap-2">
                        <i class="fas fa-plus"></i> Novo chamado
                    </button>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="bg-green-200 text-green-800 p-2 mb-4">
                        <?= htmlspecialchars($_GET['success']) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-200 text-red-800 p-2 mb-4">
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($userRole === 'client'): ?>
    <div class="mb-4">
        <span class="bg-gray-600 text-white rounded-lg px-4 py-2 mx-auto block">
            Abra um chamado de ordem de serviço que nossos especialistas entrarão em contato em breve pra resolver seu problema.
        </span>
    </div>
<?php endif; ?>

                <?php
                // Adicionar formulário de filtro acima da tabela, igual logs.php
                ?>
                <form method="GET" class="mb-4 flex flex-wrap gap-2 items-end">
                    <?php if ($userRole !== 'client'): ?>
                        <div class="relative">
                            <label class="block text-xs text-gray-600" for="client_id">Cliente</label>
                            <div class="flex">
                                <input type="text" name="client_search" id="client_search" placeholder="Digite para buscar..." 
                                       class="border rounded px-2 py-1 w-48" autocomplete="off">
                                <?php if ($filter_client_id): ?>
                                    <button type="button" id="clear_client_filter" class="ml-1 px-2 py-1 bg-gray-300 text-gray-600 rounded hover:bg-gray-400 text-xs">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="client_id" id="client_id" value="<?= $filter_client_id ?>">
                            <div id="client_search_results" class="absolute bg-white border rounded shadow-lg hidden max-h-48 overflow-y-auto z-10 w-48 top-full left-0"></div>
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="block text-xs text-gray-600" for="service_id">Serviço</label>
                        <select name="service_id" id="service_id" class="border rounded px-2 py-1">
                            <option value="">Todos</option>
                            <?php foreach ($services as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $filter_service_id == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['service']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600" for="priority">Prioridade</label>
                        <select name="priority" id="priority" class="border rounded px-2 py-1">
                            <option value="">Todas</option>
                            <?php foreach ($priorityMap as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $filter_priority === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600" for="status">Status</label>
                        <select name="status" id="status" class="border rounded px-2 py-1">
                            <option value="">Todos</option>
                            <?php foreach ($statusMap as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $filter_status === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600" for="period">Período</label>
                        <select name="period" id="period" class="border rounded px-2 py-1">
                            <option value="today" <?= $filter_period === 'today' ? 'selected' : '' ?>>Hoje</option>
                            <option value="7days" <?= $filter_period === '7days' ? 'selected' : '' ?>>Últimos 7 dias</option>
                            <option value="30days" <?= $filter_period === '30days' ? 'selected' : '' ?>>Últimos 30 dias</option>
                            <option value="all" <?= $filter_period === 'all' ? 'selected' : '' ?>>Sempre</option>
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

                <table class="min-w-full bg-white shadow-md rounded mb-0">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">ID</th>
                            <?php if ($userRole !== 'client'): ?>
                                <th class="py-2 px-4 border-b">Cliente</th>
                            <?php endif; ?>
                            <th class="py-2 px-4 border-b">Serviço</th>
                            <?php if ($userRole !== 'client'): ?>
                                <th class="py-2 px-4 border-b">Prioridade</th>
                            <?php endif; ?>
                            <th class="py-2 px-4 border-b">Status</th>
                            <th class="py-2 px-4 border-b">Criado em</th>
                            <th class="py-2 px-4 border-b">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="<?= $userRole !== 'client' ? 8 : 7 ?>" class="py-4 text-center text-gray-500">
                                    Nenhum
                                    registro encontrado.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($tickets as $t): ?>
                            <tr class="text-center border-t">
                                <td class="py-2 px-4"><?= $t['id'] ?></td>
                                <?php if ($userRole !== 'client'): ?>
                                    <td class="py-2 px-4"><?= htmlspecialchars($t['client_name']) ?></td>
                                <?php endif; ?>
                                <td class="py-2 px-4"><?= htmlspecialchars($t['service']) ?></td>
                                <?php if ($userRole !== 'client'): ?>
                                    <td class="py-2 px-4"><?= $priorityMap[$t['priority']] ?></td>
                                <?php endif; ?>
                                <td class="py-2 px-4"><?= $statusMap[$t['status']] ?></td>
                                <td class="py-2 px-4"><?= date("d/m/Y H:i", strtotime($t['created_at'])) ?></td>
                                <td class="py-2 px-4 space-x-2">
    <?php if ($userRole === 'client'): ?>
        <button class="js-open-info" data-id="<?= $t['id'] ?>">
            <i class="fas fa-info-circle text-gray-400 hover:text-gray-500"></i>
        </button>
    <?php elseif ($userRole === 'technician'): ?>
        <button class="js-open-info" data-id="<?= $t['id'] ?>">
            <i class="fas fa-info-circle text-gray-400 hover:text-gray-500"></i>
        </button>
        <button class="js-open-edit" data-id="<?= $t['id'] ?>">
            <i class="fas fa-edit text-gray-400 hover:text-gray-500"></i>
        </button>
    <?php elseif ($userRole === 'admin'): ?>
        <button class="js-open-info" data-id="<?= $t['id'] ?>">
            <i class="fas fa-info-circle text-gray-400 hover:text-gray-500"></i>
        </button>
        <button class="js-open-edit" data-id="<?= $t['id'] ?>">
            <i class="fas fa-edit text-gray-400 hover:text-gray-500"></i>
        </button>
        <button class="js-open-delete" data-id="<?= $t['id'] ?>">
            <i class="fas fa-trash text-red-500 hover:text-red-600"></i>
        </button>
    <?php endif; ?>
</td>
                            </tr>

                            <!-- Info Modal -->
                            <div id="modal-info-<?= $t['id'] ?>"
                                class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
                                <div class="bg-white p-6 rounded-lg w-full max-w-lg relative">
                                    <button
                                        class="js-close-info absolute top-2 right-2 text-gray-500 text-2xl">&times;</button>
                                    <h2 class="text-xl font-bold mb-4">Detalhes do Chamado #<?= $t['id'] ?></h2>
                                    <ul class="space-y-2 text-gray-700">
                                        <?php if ($userRole !== 'client'): ?>
                                            <li><strong>Cliente:</strong>
                                                <?= htmlspecialchars($t['client_name']) ?>
                                            </li>
                                        <?php endif; ?>
                                        <li><strong>Serviço:</strong> <?= htmlspecialchars($t['service']) ?></li>
                                        <li><strong>Descrição:</strong> <?= nl2br(htmlspecialchars($t['description'])) ?>
                                        </li>
                                        <li><strong>Prioridade:</strong> <?= $priorityMap[$t['priority']] ?>
                                        </li>
                                        <li><strong>Status:</strong> <?= $statusMap[$t['status']] ?></li>
                                        <li><strong>Criado em:</strong>
                                            <?= date("d/m/Y H:i", strtotime($t['created_at'])) ?>
                                        </li>
                                        <?php if ($t['closed_at']): ?>
                                            <li><strong>Fechado em:</strong>
                                                <?= date("d/m/Y H:i", strtotime($t['closed_at'])) ?>
                                            </li>
                                        <?php endif; ?>
                                        <li><strong>Equipamentos:</strong>
                                            <?php
                                            $eqs = $conn->query("SELECT e.* FROM ticket_equipment te JOIN equipment e ON te.equipment_id=e.id WHERE te.ticket_id={$t['id']}");
                                            if ($eqs->num_rows) {
                                                echo '<ul class="list-disc list-inside">';
                                                while ($e = $eqs->fetch_assoc()) {
                                                    $label = ($userRole === 'client')
                                                        ? "{$e['type']} - {$e['equipment_code']}"
                                                        : "{$t['client_name']} - {$e['type']} - {$e['equipment_code']}";
                                                    echo "<li>" . htmlspecialchars($label) . "</li>";
                                                }
                                                echo '</ul>';
                                            } else {
                                                echo 'Nenhum.';
                                            }
                                            ?>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Edit Modal -->
                            <div id="modal-edit-<?= $t['id'] ?>"
                                class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
                                <div class="bg-white p-6 rounded-lg w-full max-w-md relative overflow-auto max-h-screen">
                                    <button
                                        class="js-close-edit absolute top-2 right-2 text-gray-500 text-2xl">&times;</button>
                                    <h2 class="text-xl font-bold mb-4">Editar Chamado #<?= $t['id'] ?></h2>
                                    <form action="index.php?page=<?= $currentPage ?>" method="POST">
                                        <input type="hidden" name="action" value="edit_ticket">
                                        <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">

                                        <div class="mb-4">
                                            <label for="service_id_<?= $t['id'] ?>"
                                                class="block text-gray-700">Serviço</label>
                                            <select name="service_id" id="service_id_<?= $t['id'] ?>"
                                                class="w-full p-2 border rounded">
                                                <?php foreach ($services as $s): ?>
                                                    <option value="<?= $s['id'] ?>" <?= $s['id'] == $t['service_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($s['service']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-4">
                                            <label for="description_<?= $t['id'] ?>"
                                                class="block text-gray-700">Descrição</label>
                                            <textarea name="description" id="description_<?= $t['id'] ?>" rows="4"
                                                class="w-full p-2 border rounded"><?= htmlspecialchars($t['description']) ?></textarea>
                                        </div>

                                        <?php if ($userRole !== 'client'): ?>
                                            <div class="mb-4">
                                                <label for="priority_<?= $t['id'] ?>"
                                                    class="block text-gray-700">Prioridade</label>
                                                <select name="priority" id="priority_<?= $t['id'] ?>"
                                                    class="w-full p-2 border rounded">
                                                    <option value="normal" <?= $t['priority'] === 'normal' ? 'selected' : '' ?>>
                                                        Normal
                                                    </option>
                                                    <option value="high" <?= $t['priority'] === 'high' ? 'selected' : '' ?>>Alta
                                                    </option>
                                                </select>
                                            </div>
                                        <?php endif; ?>

                                        <div class="mb-4">
                                        <label for="status_<?= $t['id'] ?>" class="block text-gray-700">Status</label>
<select name="status" id="status_<?= $t['id'] ?>" class="w-full p-2 border rounded">
    <?php foreach ($statusMap as $value => $label): ?>
        <option value="<?= $value ?>" <?= $t['status'] === $value ? 'selected' : '' ?>>
            <?= $label ?>
        </option>
    <?php endforeach; ?>
</select>
                                        </div>

                                        <div class="mb-4">
                                            <label for="equipment_ids_<?= $t['id'] ?>"
                                                class="block text-gray-700">Equipamentos</label>
                                            <select name="equipment_ids[]" id="equipment_ids_<?= $t['id'] ?>"
                                                class="w-full p-2 border rounded" multiple>
                                                <?php
                                                // equipamentos já associados
                                                $assoc = [];
                                                $eqs2 = $conn->query("SELECT equipment_id FROM ticket_equipment WHERE ticket_id={$t['id']}");
                                                while ($e2 = $eqs2->fetch_assoc()) {
                                                    $assoc[] = $e2['equipment_id'];
                                                }
                                                ?>
                                                <?php foreach ($equipmentsAll as $e): ?>
                                                    <?php
                                                    $label = ($userRole === 'client')
                                                        ? "{$e['type']} - {$e['equipment_code']}"
                                                        : "{$e['client_name']} - {$e['type']} - {$e['equipment_code']}";
                                                    ?>
                                                    <option value="<?= $e['id'] ?>" <?= in_array($e['id'], $assoc) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($label) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="text-gray-600 text-sm">Ctrl/Cmd para múltipla seleção.</p>
                                        </div>

                                        <button type="submit"
                                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                                            Salvar alterações
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Delete Modal -->
                            <div id="modal-delete-<?= $t['id'] ?>"
                                class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
                                <div class="bg-white p-6 rounded-lg w-full max-w-sm relative">
                                    <button
                                        class="js-close-delete absolute top-2 right-2 text-gray-500 text-2xl">&times;</button>
                                    <h2 class="text-xl font-bold mb-4">Excluir Chamado #<?= $t['id'] ?></h2>
                                    <p class="mb-4">Você tem certeza que deseja excluir este chamado?</p>
                                    <form action="index.php?page=<?= $currentPage ?>" method="POST"
                                        class="flex justify-end space-x-2">
                                        <input type="hidden" name="action" value="delete_ticket">
                                        <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                        <button type="button"
                                            class="px-4 py-2 rounded border hover:bg-gray-100 js-close-delete">Cancelar</button>
                                        <button type="submit"
                                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Excluir</button>
                                    </form>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Paginação -->
                <div class="px-4 py-2 bg-white flex flex-col items-center space-y-2 mt-4">
                    <div class="text-sm text-gray-700">
                        <?php
                        $start = $totalRecords > 0 ? $offset + 1 : 0;
                        $end = $offset + count($tickets);
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

            <!-- Create Modal -->
<div id="modal-create" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded-lg w-full max-w-md relative overflow-auto max-h-screen">
        <button id="close-create" class="absolute top-2 right-2 text-gray-500 text-2xl">&times;</button>
        <h2 class="text-xl font-bold mb-4">Novo Chamado</h2>
        <form action="index.php?page=<?= $currentPage?>" method="POST">
            <input type="hidden" name="action" value="create_ticket">
            <?php if($userRole!=='client'): ?>
            <div class="mb-4 relative">
                <label for="client_id" class="block text-gray-700">Cliente</label>
                <input type="text" name="client_search_modal" id="client_search_modal" placeholder="Digite para buscar..." 
                       class="w-full p-2 border rounded" autocomplete="off">
                <input type="hidden" name="client_id" id="client_id_modal" value="">
                <div id="client_search_results_modal" class="absolute bg-white border rounded shadow-lg hidden max-h-48 overflow-y-auto z-10 w-full top-full left-0"></div>
            </div>
            <?php endif; ?>

            <div class="mb-4">
                <label for="service_id" class="block text-gray-700">Serviço</label>
                <select name="service_id" id="service_id" class="w-full p-2 border rounded">
                    <option value="">Selecione um serviço</option>
                    <?php foreach($services as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['service']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="description" class="block text-gray-700">Descrição</label>
                <textarea name="description" id="description" rows="4" class="w-full p-2 border rounded" placeholder="Descreva o problema"></textarea>
            </div>

            <?php if($userRole!=='client'): ?>
            <div class="mb-4">
                <label for="priority" class="block text-gray-700">Prioridade</label>
                <select name="priority" id="priority" class="w-full p-2 border rounded">
                    <option value="normal" selected>Normal</option>
                    <option value="high">Alta</option>
                </select>
            </div>
            <?php endif; ?>

            <?php if(!empty($equipmentsAll)): ?>
            <div class="mb-4">
                <label for="equipment_ids" class="block text-gray-700">Equipamentos</label>
                <select name="equipment_ids[]" id="equipment_ids" class="w-full p-2 border rounded" multiple>
                    <?php foreach($equipmentsAll as $e): ?>
                    <?php
                        $label = ($userRole==='client')
                            ? "{$e['type']} - {$e['equipment_code']}"
                            : "{$e['client_name']} - {$e['type']} - {$e['equipment_code']}";
                    ?>
                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-gray-600 text-sm">Ctrl/Cmd para múltipla seleção.</p>
            </div>
            <?php endif; ?>

            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Abrir Chamado</button>
        </form>
    </div>
</div>

<script>
    // Helpers
    function toggleModal(id, show) {
        document.getElementById(id).classList.toggle('hidden', !show);
    }

    // Create
    document.getElementById('btn-open-create').onclick = () => toggleModal('modal-create', true);
    document.getElementById('close-create').onclick = () => toggleModal('modal-create', false);
    window.addEventListener('click', e => {
        if (e.target.id === 'modal-create') toggleModal('modal-create', false);
    });

    // Info, Edit, Delete buttons
    document.querySelectorAll('.js-open-info').forEach(btn => {
        btn.onclick = () => toggleModal('modal-info-' + btn.dataset.id, true);
    });
    document.querySelectorAll('.js-close-info').forEach(btn => {
        btn.onclick = () => {
            let id = btn.closest('div[id^="modal-info-"]').id.replace('modal-info-','');
            toggleModal('modal-info-' + id, false);
        };
    });
    window.addEventListener('click', e => {
        if (e.target.id.startsWith('modal-info-')) toggleModal(e.target.id, false);
    });

    document.querySelectorAll('.js-open-edit').forEach(btn => {
        btn.onclick = () => toggleModal('modal-edit-' + btn.dataset.id, true);
    });
    document.querySelectorAll('.js-close-edit').forEach(btn => {
        btn.onclick = () => {
            let id = btn.closest('div[id^="modal-edit-"]').id.replace('modal-edit-','');
            toggleModal('modal-edit-' + id, false);
        };
    });
    window.addEventListener('click', e => {
        if (e.target.id.startsWith('modal-edit-')) toggleModal(e.target.id, false);
    });

    document.querySelectorAll('.js-open-delete').forEach(btn => {
        btn.onclick = () => toggleModal('modal-delete-' + btn.dataset.id, true);
    });
    document.querySelectorAll('.js-close-delete').forEach(btn => {
        btn.onclick = () => {
            let id = btn.closest('div[id^="modal-delete-"]').id.replace('modal-delete-','');
            toggleModal('modal-delete-' + id, false);
        };
    });
    window.addEventListener('click', e => {
        if (e.target.id.startsWith('modal-delete-')) toggleModal(e.target.id, false);
    });
</script>

<script>
// Client search functionality
document.addEventListener('DOMContentLoaded', function() {
    let searchTimeout;
    
    // Function to search clients
    function searchClients(searchTerm, resultsContainer, inputField, hiddenField) {
        if (searchTerm.length < 2) {
            resultsContainer.classList.add('hidden');
            return;
        }
        
        fetch('search_clients.php?term=' + encodeURIComponent(searchTerm))
            .then(response => response.json())
            .then(data => {
                resultsContainer.innerHTML = '';
                
                if (data.length === 0) {
                    resultsContainer.innerHTML = '<div class="p-2 text-gray-500">Nenhum cliente encontrado</div>';
                } else {
                    data.forEach(client => {
                        const div = document.createElement('div');
                        div.className = 'p-2 hover:bg-gray-100 cursor-pointer border-b';
                        div.textContent = client.name;
                        div.onclick = function() {
                            inputField.value = client.name;
                            hiddenField.value = client.id;
                            resultsContainer.classList.add('hidden');
                            
                            // Auto-submit form for filter
                            if (inputField.id === 'client_search') {
                                inputField.form.submit();
                            }
                        };
                        resultsContainer.appendChild(div);
                    });
                }
                
                resultsContainer.classList.remove('hidden');
            })
            .catch(error => {
                console.error('Erro na busca:', error);
                resultsContainer.innerHTML = '<div class="p-2 text-red-500">Erro na busca</div>';
                resultsContainer.classList.remove('hidden');
            });
    }
    
    // Filter form client search
    const clientSearch = document.getElementById('client_search');
    const clientSearchResults = document.getElementById('client_search_results');
    const clientIdHidden = document.getElementById('client_id');
    
    if (clientSearch) {
        clientSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchClients(this.value, clientSearchResults, this, clientIdHidden);
            }, 300);
            
            // Clear filter if input is empty
            if (this.value.length === 0) {
                clientIdHidden.value = '';
                this.form.submit();
            }
        });
        
        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!clientSearch.contains(e.target) && !clientSearchResults.contains(e.target)) {
                clientSearchResults.classList.add('hidden');
            }
        });
        
        // Clear client filter button
        const clearClientFilter = document.getElementById('clear_client_filter');
        if (clearClientFilter) {
            clearClientFilter.addEventListener('click', function() {
                clientSearch.value = '';
                clientIdHidden.value = '';
                clientSearchResults.classList.add('hidden');
                clientSearch.form.submit();
            });
        }
    }
    
    // Modal client search
    const clientSearchModal = document.getElementById('client_search_modal');
    const clientSearchResultsModal = document.getElementById('client_search_results_modal');
    const clientIdHiddenModal = document.getElementById('client_id_modal');
    
    if (clientSearchModal) {
        clientSearchModal.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchClients(this.value, clientSearchResultsModal, this, clientIdHiddenModal);
            }, 300);
        });
        
        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!clientSearchModal.contains(e.target) && !clientSearchResultsModal.contains(e.target)) {
                clientSearchResultsModal.classList.add('hidden');
            }
        });
    }
    
    // Set current client name in filter if exists
    <?php if ($filter_client_id && $userRole !== 'client'): ?>
    fetch('search_clients.php?id=<?= $filter_client_id ?>')
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                clientSearch.value = data[0].name;
            }
        });
    <?php endif; ?>
});
</script>
</body>
</html>