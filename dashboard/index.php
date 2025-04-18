<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connect.php';

// Recupera informações do usuário autenticado
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role']; // 'admin', 'technician' ou 'client'
$_SESSION['current_page'] = 'tickets';

// --- PAGINAÇÃO CONFIGURAÇÃO ---
$perPage = 10;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page'])
    ? (int) $_GET['page']
    : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}
$offset = ($currentPage - 1) * $perPage;

// Conta o total de chamados
if ($userRole === 'client') {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM tickets WHERE client_id = ?");
    $countStmt->bind_param("i", $userId);
} else {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM tickets");
}
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

// Calcula total de páginas
$totalPages = (int) ceil($totalRecords / $perPage);

// Inicializa variável de erros
$errors = [];

// Se for POST, trata a criação de novo chamado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    $serviceId = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    $description = trim($_POST['description'] ?? '');
    $selectedEquipment = $_POST['equipment_ids'] ?? [];

    if (empty($serviceId)) {
        $errors[] = 'Selecione um serviço.';
    }
    if (empty($description)) {
        $errors[] = 'Informe uma descrição para o chamado.';
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
            header("Location: tickets.php?success=Chamado+criado+com+sucesso");
            exit;
        } else {
            $errors[] = "Erro ao criar o chamado: " . $stmt->error;
        }
    }
}

// Carrega lista de serviços
$services = [];
$res = $conn->query("SELECT * FROM services ORDER BY service ASC");
while ($row = $res->fetch_assoc()) {
    $services[] = $row;
}

// Carrega equipamentos ou clientes conforme perfil
if ($userRole === 'client') {
    $equipments = [];
    $res = $conn->query("SELECT * FROM equipment WHERE user_id = $userId ORDER BY type ASC");
    while ($row = $res->fetch_assoc()) {
        $equipments[] = $row;
    }
} else {
    $clients = [];
    $res = $conn->query("SELECT id, name FROM users WHERE role = 'client' ORDER BY name ASC");
    while ($row = $res->fetch_assoc()) {
        $clients[] = $row;
    }
    $equipments = [];
    $res = $conn->query("
        SELECT e.*, u.name AS client_name
        FROM equipment e
        JOIN users u ON e.user_id = u.id
        ORDER BY u.name, e.type ASC
    ");
    while ($row = $res->fetch_assoc()) {
        $equipments[] = $row;
    }
}

// Busca chamados paginados
if ($userRole === 'client') {
    $stmt = $conn->prepare("
        SELECT t.*, s.service
        FROM tickets t
        JOIN services s ON t.service_id = s.id
        WHERE t.client_id = ?
        ORDER BY t.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("iii", $userId, $perPage, $offset);
} else {
    $stmt = $conn->prepare("
        SELECT t.*, s.service, u.name AS client_name
        FROM tickets t
        JOIN services s ON t.service_id = s.id
        JOIN users u ON t.client_id = u.id
        ORDER BY t.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $perPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$tickets = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
                    <button id="openModalButton"
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

                <table class="min-w-full bg-white shadow-md rounded mb-0">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">ID</th>
                            <?php if ($userRole !== 'client'): ?>
                                <th class="py-2 px-4 border-b">Cliente</th>
                            <?php endif; ?>
                            <th class="py-2 px-4 border-b">Serviço</th>
                            <th class="py-2 px-4 border-b">Descrição</th>
                            <?php if ($userRole !== 'client'): ?>
                                <th class="py-2 px-4 border-b">Prioridade</th>
                            <?php endif; ?>
                            <th class="py-2 px-4 border-b">Status</th>
                            <th class="py-2 px-4 border-b">Criado em</th>
                            <th class="py-2 px-4 border-b">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr class="text-center border-t">
                                <td class="py-2 px-4"><?= $ticket['id'] ?></td>
                                <?php if ($userRole !== 'client'): ?>
                                    <td class="py-2 px-4"><?= htmlspecialchars($ticket['client_name']) ?></td>
                                <?php endif; ?>
                                <td class="py-2 px-4"><?= htmlspecialchars($ticket['service']) ?></td>
                                <td class="py-2 px-4">
                                    <?= htmlspecialchars(substr($ticket['description'], 0, 50)) ?>
                                    <?= strlen($ticket['description']) > 50 ? '...' : '' ?>
                                </td>
                                <?php if ($userRole !== 'client'): ?>
                                    <td class="py-2 px-4"><?= ucfirst(htmlspecialchars($ticket['priority'])) ?></td>
                                <?php endif; ?>
                                <td class="py-2 px-4"><?= ucfirst(htmlspecialchars($ticket['status'])) ?></td>
                                <td class="py-2 px-4">
                                    <?= date("d/m/Y H:i", strtotime($ticket['created_at'])) ?>
                                </td>
                                <td class="py-2 px-4">
                                    <a href="ticket_detail.php?id=<?= $ticket['id'] ?>"
                                        class="text-blue-500 hover:underline">Ver detalhes</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="<?= $userRole !== 'client' ? 8 : 7 ?>" class="py-4 text-center text-gray-500">
                                    Nenhum registro encontrado.
                                </td>
                            </tr>
                        <?php endif; ?>
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

            <!-- Modal para criação de novo Chamado -->
            <div id="modal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
                <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
                    <button id="closeModalButton" class="absolute top-2 right-2 text-gray-500 text-2xl">&times;</button>
                    <h2 class="text-xl font-bold mb-4">Novo Chamado</h2>
                    <form action="tickets.php" method="POST">
                        <?php if ($userRole !== 'client'): ?>
                            <div class="mb-4">
                                <label for="client_id" class="block text-gray-700">Cliente</label>
                                <select name="client_id" id="client_id" class="w-full p-2 border rounded">
                                    <option value="">Selecione um cliente</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?= $client['id'] ?>">
                                            <?= htmlspecialchars($client['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <label for="service_id" class="block text-gray-700">Serviço</label>
                            <select name="service_id" id="service_id" class="w-full p-2 border rounded">
                                <option value="">Selecione um serviço</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?= $service['id'] ?>">
                                        <?= htmlspecialchars($service['service']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-gray-700">Descrição</label>
                            <textarea name="description" id="description" rows="4" class="w-full p-2 border rounded"
                                placeholder="Descreva o problema"></textarea>
                        </div>

                        <?php if ($userRole !== 'client'): ?>
                            <div class="mb-4">
                                <label for="priority" class="block text-gray-700">Prioridade</label>
                                <select name="priority" id="priority" class="w-full p-2 border rounded">
                                    <option value="normal" selected>Normal</option>
                                    <option value="high">Alta</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($equipments)): ?>
                            <div class="mb-4">
                                <label for="equipment_ids" class="block text-gray-700">Equipamentos</label>
                                <select name="equipment_ids[]" id="equipment_ids" class="w-full p-2 border rounded"
                                    multiple>
                                    <?php foreach ($equipments as $equipment): ?>
                                        <option value="<?= $equipment['id'] ?>">
                                            <?php
                                            if ($userRole === 'client') {
                                                echo htmlspecialchars($equipment['type'] . ' - ' . $equipment['equipment_code']);
                                            } else {
                                                echo htmlspecialchars($equipment['client_name'] . ' - ' . $equipment['type'] . ' - ' . $equipment['equipment_code']);
                                            }
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-gray-600 text-sm">
                                    Mantenha Ctrl (Windows) ou Cmd (Mac) para selecionar múltiplos.
                                </p>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                            Abrir Chamado
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
        const openModalButtons = document.querySelectorAll('#openModalButton');
        const closeModalButton = document.getElementById('closeModalButton');
        const modal = document.getElementById('modal');

        openModalButtons.forEach(btn => {
            btn.addEventListener('click', () => modal.classList.remove('hidden'));
        });
        closeModalButton.addEventListener('click', () => modal.classList.add('hidden'));
        window.addEventListener('click', e => {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    </script>
</body>

</html>