<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connect.php';

// Recupera informações do usuário autenticado
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role']; // 'admin', 'technician' ou 'client'
$_SESSION['current_page'] = 'tickets';

// Inicializa variável de erros
$errors = [];

// Se for POST, trata a criação de novo chamado (todos os perfis podem abrir)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Define o client_id com base no perfil
    if ($userRole === 'client') {
        $clientId = $userId;
        $priority = 'normal'; // sempre normal para cliente
    } else {
        $clientId = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
        if (empty($clientId)) {
            $errors[] = 'Selecione um cliente.';
        }
        $priority = $_POST['priority'] ?? 'normal';
    }

    // Recebe os demais dados do formulário
    $serviceId = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    $description = trim($_POST['description'] ?? '');
    $selectedEquipment = $_POST['equipment_ids'] ?? [];

    // Validação básica
    if (empty($serviceId)) {
        $errors[] = 'Selecione um serviço.';
    }
    if (empty($description)) {
        $errors[] = 'Informe uma descrição para o chamado.';
    }

    // Insere o chamado se não houver erros
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO tickets (service_id, client_id, description, priority) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $serviceId, $clientId, $description, $priority);
        if ($stmt->execute()) {
            $ticketId = $stmt->insert_id;
            $stmt->close();

            // Se houver equipamentos selecionados, insere na tabela ticket_equipment
            if (!empty($selectedEquipment)) {
                $stmt2 = $conn->prepare("INSERT INTO ticket_equipment (ticket_id, equipment_id) VALUES (?, ?)");
                foreach ($selectedEquipment as $equipmentId) {
                    $stmt2->bind_param("ii", $ticketId, $equipmentId);
                    $stmt2->execute();
                }
                $stmt2->close();
            }
            header("Location: " . $_ENV['APP_URL'] . "/tickets.php?success=Chamado+criado+com+sucesso");
            exit;
        } else {
            $errors[] = "Erro ao criar o chamado: " . $stmt->error;
        }
    }
}

// Carrega os dados comuns para o formulário de criação de chamado: Lista de serviços
$services = [];
$result = $conn->query("SELECT * FROM services ORDER BY service ASC");
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

if ($userRole === 'client') {
    // Para clientes, carrega apenas os seus equipamentos
    $equipments = [];
    $result = $conn->query("SELECT * FROM equipment WHERE user_id = $userId ORDER BY type ASC");
    while ($row = $result->fetch_assoc()) {
        $equipments[] = $row;
    }
} else {
    // Para admin/technician, carrega a lista de clientes e a lista de todos os equipamentos (com identificação do cliente)
    $clients = [];
    $result = $conn->query("SELECT id, name FROM users WHERE role = 'client' ORDER BY name ASC");
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
    $equipments = [];
    $result = $conn->query("SELECT e.*, u.name AS client_name FROM equipment e JOIN users u ON e.user_id = u.id ORDER BY u.name, e.type ASC");
    while ($row = $result->fetch_assoc()) {
        $equipments[] = $row;
    }
}

// Consulta os chamados conforme o perfil:
if ($userRole === 'client') {
    $stmt = $conn->prepare("SELECT t.*, s.service FROM tickets t JOIN services s ON t.service_id = s.id WHERE t.client_id = ? ORDER BY t.created_at DESC");
    $stmt->bind_param("i", $userId);
} else {
    $stmt = $conn->prepare("SELECT t.*, s.service, u.name AS client_name FROM tickets t JOIN services s ON t.service_id = s.id JOIN users u ON t.client_id = u.id ORDER BY t.created_at DESC");
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
        <!-- Inclui a sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <div class="flex-1 container mx-auto p-4">
            <!-- Exibe o o cabeçalho somente se houver chamados -->
            <?php if (!empty($tickets)): ?>
                <!-- Cabeçalho com título à esquerda e botão de novo chamado à direita -->
                <div class="flex justify-between items-center mb-4">
                    <?php if ($userRole === 'client'): ?>
                        <h1 class="text-2xl font-bold">Meus chamados</h1>
                    <?php else: ?>
                        <h1 class="text-2xl font-bold">Chamados</h1>
                    <?php endif; ?>
                    <button id="openModalButton" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                        Novo chamado
                    </button>
                </div>
            <?php endif; ?>

            <!-- Mensagens de sucesso ou erro -->
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

            <!-- Área de listagem de chamados ou mensagem de vazio -->
            <?php if (count($tickets) > 0): ?>
                <!-- Tabela de chamados -->
                <table class="min-w-full bg-white shadow-md rounded mb-4">
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
                                <td class="py-2 px-4 border-b"><?= $ticket['id'] ?></td>
                                <?php if ($userRole !== 'client'): ?>
                                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($ticket['client_name']) ?></td>
                                <?php endif; ?>
                                <td class="py-2 px-4 border-b"><?= htmlspecialchars($ticket['service']) ?></td>
                                <td class="py-2 px-4 border-b">
                                    <?= htmlspecialchars(substr($ticket['description'], 0, 50)) ?>
                                    <?= strlen($ticket['description']) > 50 ? '...' : '' ?>
                                </td>
                                <?php if ($userRole !== 'client'): ?>
                                    <td class="py-2 px-4 border-b"><?= ucfirst(htmlspecialchars($ticket['priority'])) ?></td>
                                <?php endif; ?>
                                <td class="py-2 px-4 border-b"><?= ucfirst(htmlspecialchars($ticket['status'])) ?></td>
                                <td class="py-2 px-4 border-b"><?= date("d/m/Y H:i", strtotime($ticket['created_at'])) ?></td>
                                <td class="py-2 px-4 border-b">
                                    <a href="ticket_detail.php?id=<?= $ticket['id'] ?>"
                                        class="text-blue-500 hover:underline">Ver
                                        detalhes</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <!-- Área para quando não houver chamados -->
                <div class="flex flex-col items-center justify-center mt-16">
                    <!-- Utilize a imagem desejada (substitua "no_records.png" ou URL abaixo conforme necessário) -->
                    <img src="./assets/img/no_data.png" alt="Nenhum chamado aberto"
                        class="mb-12 w-96">
                    <h2 class="text-xl font-bold mb-2">Nenhum chamado aberto</h2>
                    <button id="openModalButton" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                        Abrir novo chamado
                    </button>
                </div>
            <?php endif; ?>

            <!-- Modal para criação de novo Chamado -->
            <div id="modal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
                <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
                    <!-- Botão para fechar o Modal -->
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
                                        <?php if ($userRole === 'client'): ?>
                                            <option value="<?= $equipment['id'] ?>">
                                                <?= htmlspecialchars($equipment['type'] . ' - ' . $equipment['equipment_code']) ?>
                                            </option>
                                        <?php else: ?>
                                            <option value="<?= $equipment['id'] ?>">
                                                <?= htmlspecialchars($equipment['client_name'] . " - " . $equipment['type'] . ' - ' . $equipment['equipment_code']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-gray-600 text-sm">Mantenha Ctrl (Windows) ou Cmd (Mac) para selecionar
                                    múltiplos
                                    equipamentos.</p>
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

    <!-- Script para abrir/fechar o Modal -->
    <script>
        // Obtém todos os botões de abertura do modal (nas duas seções)
        const openModalButtons = document.querySelectorAll('#openModalButton');
        const closeModalButton = document.getElementById('closeModalButton');
        const modal = document.getElementById('modal');

        openModalButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                modal.classList.remove('hidden');
            });
        });

        closeModalButton.addEventListener('click', function () {
            modal.classList.add('hidden');
        });

        // Fecha o modal ao clicar fora do conteúdo
        window.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    </script>
</body>

</html>