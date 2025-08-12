<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db_connect.php';

// Define a página atual
$_SESSION['current_page'] = 'equipments';

// Permissão (apenas admin e technician)
$userRole = $_SESSION['user_role'];
if (!in_array($userRole, ['admin', 'technician'])) {
    header("Location: " . $_ENV['APP_URL']);
    exit();
}

$errors = [];

// --- PAGINAÇÃO CONFIGURAÇÃO ---
$perPage = 10;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page'])
    ? (int) $_GET['page']
    : 1;
if ($currentPage < 1)
    $currentPage = 1;
$offset = ($currentPage - 1) * $perPage;

// --- FILTROS ---
$filter_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';

// Monta WHERE dinâmico
$where = [];
$params = [];
$types = '';
if ($filter_client_id) {
    $where[] = 'e.user_id = ?';
    $params[] = $filter_client_id;
    $types .= 'i';
}
if ($filter_type) {
    $where[] = 'e.type = ?';
    $params[] = $filter_type;
    $types .= 's';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Conta total de equipamentos com filtro
if ($filter_client_id || $filter_type) {
    $countSql = "SELECT COUNT(*) AS total FROM equipment e $whereSql";
    $countStmt = $conn->prepare($countSql);
    if ($params)
        $countStmt->bind_param($types, ...$params);
} else {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM equipment");
}
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = (int) ceil($totalRecords / $perPage);

// Trata ações de criação, edição e exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $conn->query("SET @current_user_id = " . intval($_SESSION['user_id']));
    $action = $_POST['action'];

    if ($action === 'create_equipment') {
        $userId = intval($_POST['user_id'] ?? 0);
        $type = $_POST['type'] ?? '';
        $equipmentCode = trim($_POST['equipment_code'] ?? '');

        if ($userId <= 0)
            $errors[] = 'Selecione um cliente.';
        if (empty($type))
            $errors[] = 'Selecione o tipo de equipamento.';
        if (empty($equipmentCode))
            $errors[] = 'Informe o código do equipamento.';

        if (empty($errors)) {
            $stmt = $conn->prepare("
                INSERT INTO equipment (user_id, type, equipment_code)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("iss", $userId, $type, $equipmentCode);
            if ($stmt->execute()) {
                header("Location: equipments.php?page={$currentPage}&success=Equipamento+criado+com+sucesso");
                exit;
            } else {
                $errors[] = 'Erro ao criar o equipamento: ' . $stmt->error;
            }
        }

    } elseif ($action === 'edit_equipment') {
        $eqId = intval($_POST['equipment_id']);
        $userId = intval($_POST['user_id'] ?? 0);
        $type = $_POST['type'] ?? '';
        $equipmentCode = trim($_POST['equipment_code'] ?? '');

        if ($userId <= 0)
            $errors[] = 'Selecione um cliente.';
        if (empty($type))
            $errors[] = 'Selecione o tipo de equipamento.';
        if (empty($equipmentCode))
            $errors[] = 'Informe o código do equipamento.';

        if (empty($errors)) {
            $stmt = $conn->prepare("
                UPDATE equipment
                SET user_id = ?, type = ?, equipment_code = ?
                WHERE id = ?
            ");
            $stmt->bind_param("issi", $userId, $type, $equipmentCode, $eqId);
            if ($stmt->execute()) {
                header("Location: equipments.php?page={$currentPage}&success=Equipamento+atualizado+com+sucesso");
                exit;
            } else {
                $errors[] = 'Erro ao atualizar: ' . $stmt->error;
            }
        }

    } elseif ($action === 'delete_equipment' && $userRole === 'admin') {
        $eqId = intval($_POST['equipment_id']);
        if ($conn->query("DELETE FROM equipment WHERE id = {$eqId}")) {
            header("Location: equipments.php?page={$currentPage}&success=Equipamento+excluído+com+sucesso");
            exit;
        } else {
            $errors[] = 'Erro ao excluir: ' . $conn->error;
        }
    }
}

// Lista de clientes não é mais necessária - busca via AJAX

// Busca equipamentos paginados com filtro
if ($filter_client_id || $filter_type) {
    $sql = "SELECT e.*, u.name AS client_name FROM equipment e JOIN users u ON e.user_id = u.id $whereSql ORDER BY u.name ASC, e.type ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $bindParams = $params;
    $bindTypes = $types . 'ii';
    $bindParams[] = $perPage;
    $bindParams[] = $offset;
    $stmt->bind_param($bindTypes, ...$bindParams);
} else {
    $stmt = $conn->prepare("SELECT e.*, u.name AS client_name FROM equipment e JOIN users u ON e.user_id = u.id ORDER BY u.name ASC, e.type ASC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $perPage, $offset);
}
$stmt->execute();
$equipments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipamentos | Vip Informática</title>
    <link rel="icon" type="image/png" href="./assets/img/icon.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-gray-100">
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        <div class="container mx-32 flex-1 px-8 py-6">

            <!-- Breadcrumb -->
            <nav class="flex items-center text-gray-600 mb-4">
                <i class="fas fa-home mr-2"></i><span>Home</span>
                <span class="mx-2 text-gray-400">/</span>
                <span class="font-semibold text-gray-800">Equipamentos</span>
            </nav>

            <div class="container p-4 bg-white rounded shadow mb-4">
                <!-- Título e Novo -->
                <div class="flex justify-between items-center mb-4">
                    <h1 class="text-base font-bold">Equipamentos</h1>
                    <button id="btn-open-create"
                        class="bg-red-500 text-white text-sm px-4 py-2 rounded hover:bg-red-600 flex items-center gap-2">
                        <i class="fas fa-plus"></i> Novo equipamento
                    </button>
                </div>

                <!-- Sucesso / Erros -->
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

                <!-- Adicionar formulário de filtro acima da tabela, igual logs.php/index.php -->
                <form method="GET" class="mb-4 flex flex-wrap gap-2 items-end" id="filter-form">
                    <div class="relative">
                        <label class="block text-xs text-gray-600" for="client_id">Cliente</label>
                        <div class="flex">
                            <input type="text" name="client_search" id="client_search"
                                placeholder="Digite para buscar..." class="border rounded px-2 py-1 w-48"
                                autocomplete="off">
                            <?php if ($filter_client_id): ?>
                                <button type="button" id="clear_client_filter"
                                    class="ml-1 px-2 py-1 bg-gray-300 text-gray-600 rounded hover:bg-gray-400 text-xs">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="client_id" id="client_id" value="<?= $filter_client_id ?>">
                        <div id="client_search_results"
                            class="absolute bg-white border rounded shadow-lg hidden max-h-48 overflow-y-auto z-10 w-48 top-full left-0">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600" for="type">Tipo</label>
                        <select name="type" id="type" class="border rounded px-2 py-1">
                            <option value="">Todos</option>
                            <?php $types = ['Impressora', 'Monitor', 'Nobreak', 'Gabinete', 'Notebook', 'Periféricos', 'Outros'];
                            foreach ($types as $t): ?>
                                <option value="<?= $t ?>" <?= $filter_type === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            // Aplicar apenas aos selects do formulário de filtro
                            document.querySelectorAll('#filter-form select').forEach(function (select) {
                                select.addEventListener('change', function () {
                                    this.form.submit();
                                });
                            });
                        });
                    </script>
                </form>

                <!-- Tabela de Equipamentos -->
                <table class="min-w-full bg-white shadow-md rounded mb-0">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">ID</th>
                            <th class="py-2 px-4 border-b">Cliente</th>
                            <th class="py-2 px-4 border-b">Tipo</th>
                            <th class="py-2 px-4 border-b">Código</th>
                            <th class="py-2 px-4 border-b">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($equipments)): ?>
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">
                                    Nenhum registro encontrado.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($equipments as $eq): ?>
                            <tr class="text-center border-t">
                                <td class="py-2 px-4"><?= $eq['id'] ?></td>
                                <td class="py-2 px-4"><?= htmlspecialchars($eq['client_name']) ?></td>
                                <td class="py-2 px-4"><?= htmlspecialchars($eq['type']) ?></td>
                                <td class="py-2 px-4"><?= htmlspecialchars($eq['equipment_code']) ?></td>
                                <td class="py-2 px-4 space-x-2">
                                    <!-- Info -->
                                    <button class="js-open-info" data-id="<?= $eq['id'] ?>">
                                        <i class="fas fa-info-circle text-gray-400 hover:text-gray-500"></i>
                                    </button>
                                    <!-- Edit -->
                                    <button class="js-open-edit" data-id="<?= $eq['id'] ?>">
                                        <i class="fas fa-edit text-gray-400 hover:text-gray-500"></i>
                                    </button>
                                    <!-- Delete (só admin) -->
                                    <?php if ($userRole === 'admin'): ?>
                                        <button class="js-open-delete" data-id="<?= $eq['id'] ?>">
                                            <i class="fas fa-trash text-red-500 hover:text-red-600"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Info Modal -->
                            <div id="modal-info-<?= $eq['id'] ?>"
                                class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
                                <div class="bg-white p-6 rounded-lg w-full max-w-lg relative">
                                    <button
                                        class="js-close-info absolute top-2 right-2 text-gray-500 text-2xl">&times;</button>
                                    <h2 class="text-xl font-bold mb-4">
                                        Detalhes do Equipamento #<?= $eq['id'] ?>
                                    </h2>
                                    <ul class="space-y-2 text-gray-700">
                                        <li><strong>Cliente:</strong> <?= htmlspecialchars($eq['client_name']) ?></li>
                                        <li><strong>Tipo:</strong> <?= htmlspecialchars($eq['type']) ?></li>
                                        <li><strong>Código:</strong> <?= htmlspecialchars($eq['equipment_code']) ?></li>
                                        <?php if (isset($eq['created_at'])): ?>
                                            <li><strong>Criado em:</strong>
                                                <?= date("d/m/Y H:i", strtotime($eq['created_at'])) ?>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>

                            <!-- Edit Modal -->
                            <div id="modal-edit-<?= $eq['id'] ?>"
                                class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
                                <div class="bg-white p-6 rounded-lg w-full max-w-md relative overflow-auto max-h-screen">
                                    <button
                                        class="js-close-edit absolute top-2 right-2 text-gray-500 text-2xl">&times;</button>
                                    <h2 class="text-xl font-bold mb-4">
                                        Editar Equipamento #<?= $eq['id'] ?>
                                    </h2>
                                    <form action="equipments.php?page=<?= $currentPage ?>" method="POST">
                                        <input type="hidden" name="action" value="edit_equipment">
                                        <input type="hidden" name="equipment_id" value="<?= $eq['id'] ?>">

                                        <div class="mb-4 relative">
                                            <label for="user_id_<?= $eq['id'] ?>"
                                                class="block text-gray-700">Cliente</label>
                                            <input type="text" name="client_search_modal"
                                                id="client_search_modal_<?= $eq['id'] ?>"
                                                placeholder="Digite para buscar..." class="w-full p-2 border rounded"
                                                autocomplete="off">
                                            <input type="hidden" name="user_id" id="user_id_<?= $eq['id'] ?>"
                                                value="<?= $eq['user_id'] ?>">
                                            <div id="client_search_results_modal_<?= $eq['id'] ?>"
                                                class="absolute bg-white border rounded shadow-lg hidden max-h-48 overflow-y-auto z-10 w-full top-full left-0">
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="type_<?= $eq['id'] ?>" class="block text-gray-700">Tipo</label>
                                            <select name="type" id="type_<?= $eq['id'] ?>"
                                                class="w-full p-2 border rounded">
                                                <?php
                                                $types = ['Impressora', 'Monitor', 'Nobreak', 'Gabinete', 'Notebook', 'Periféricos', 'Outros'];
                                                foreach ($types as $t): ?>
                                                    <option value="<?= $t ?>" <?= $eq['type'] === $t ? 'selected' : '' ?>>
                                                        <?= $t ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-4">
                                            <label for="equipment_code_<?= $eq['id'] ?>"
                                                class="block text-gray-700">Código</label>
                                            <input type="text" name="equipment_code" id="equipment_code_<?= $eq['id'] ?>"
                                                value="<?= htmlspecialchars($eq['equipment_code']) ?>"
                                                class="w-full p-2 border rounded">
                                        </div>

                                        <button type="submit"
                                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                                            Salvar alterações
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Delete Modal -->
                            <div id="modal-delete-<?= $eq['id'] ?>"
                                class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
                                <div class="bg-white p-6 rounded-lg w-full max-w-sm relative">
                                    <button
                                        class="js-close-delete absolute top-2 right-2 text-gray-500 text-2xl">&times;</button>
                                    <h2 class="text-xl font-bold mb-4">
                                        Excluir Equipamento #<?= $eq['id'] ?>
                                    </h2>
                                    <p class="mb-4">Tem certeza que deseja excluir este equipamento?</p>
                                    <form action="equipments.php?page=<?= $currentPage ?>" method="POST"
                                        class="flex justify-end space-x-2">
                                        <input type="hidden" name="action" value="delete_equipment">
                                        <input type="hidden" name="equipment_id" value="<?= $eq['id'] ?>">
                                        <button type="button"
                                            class="px-4 py-2 rounded border hover:bg-gray-100 js-close-delete">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                                            Excluir
                                        </button>
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
                        $end = $offset + count($equipments);
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
            <div id="modal-create"
                class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
                <div class="bg-white p-6 rounded-lg w-full max-w-md relative overflow-auto max-h-screen">
                    <button id="close-create" class="absolute top-2 right-2 text-gray-500 text-2xl">&times;</button>
                    <h2 class="text-xl font-bold mb-4">Novo Equipamento</h2>
                    <form action="equipments.php?page=<?= $currentPage ?>" method="POST">
                        <input type="hidden" name="action" value="create_equipment">
                        <div class="mb-4 relative">
                            <label for="user_id" class="block text-gray-700">Cliente</label>
                            <input type="text" name="client_search_modal" id="client_search_modal"
                                placeholder="Digite para buscar..." class="w-full p-2 border rounded"
                                autocomplete="off">
                            <input type="hidden" name="user_id" id="user_id" value="">
                            <div id="client_search_results_modal"
                                class="absolute bg-white border rounded shadow-lg hidden max-h-48 overflow-y-auto z-10 w-full top-full left-0">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="type" class="block text-gray-700">Tipo</label>
                            <select name="type" id="type" class="w-full p-2 border rounded">
                                <option value="">Selecione o tipo</option>
                                <option value="Impressora">Impressora</option>
                                <option value="Monitor">Monitor</option>
                                <option value="Nobreak">Nobreak</option>
                                <option value="Gabinete">Gabinete</option>
                                <option value="Notebook">Notebook</option>
                                <option value="Periféricos">Periféricos</option>
                                <option value="Outros">Outros</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="equipment_code" class="block text-gray-700">Código</label>
                            <input type="text" name="equipment_code" id="equipment_code"
                                class="w-full p-2 border rounded" placeholder="Código do equipamento">
                        </div>
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                            Criar Equipamento
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- Script de abertura/fechamento dos modais -->
    <script>
        function toggleModal(id, show) {
            document.getElementById(id).classList.toggle('hidden', !show);
        }

        // Create
        document.getElementById('btn-open-create').onclick = () => toggleModal('modal-create', true);
        document.getElementById('close-create').onclick = () => toggleModal('modal-create', false);
        window.addEventListener('click', e => {
            if (e.target.id === 'modal-create') toggleModal('modal-create', false);
        });

        // Info
        document.querySelectorAll('.js-open-info').forEach(btn => {
            btn.onclick = () => toggleModal('modal-info-' + btn.dataset.id, true);
        });
        document.querySelectorAll('.js-close-info').forEach(btn => {
            btn.onclick = () => {
                const id = btn.closest('[id^="modal-info-"]').id.replace('modal-info-', '');
                toggleModal('modal-info-' + id, false);
            };
        });
        window.addEventListener('click', e => {
            if (e.target.id.startsWith('modal-info-'))
                toggleModal(e.target.id, false);
        });

        // Edit
        document.querySelectorAll('.js-open-edit').forEach(btn => {
            btn.onclick = () => toggleModal('modal-edit-' + btn.dataset.id, true);
        });
        document.querySelectorAll('.js-close-edit').forEach(btn => {
            btn.onclick = () => {
                const id = btn.closest('[id^="modal-edit-"]').id.replace('modal-edit-', '');
                toggleModal('modal-edit-' + id, false);
            };
        });
        window.addEventListener('click', e => {
            if (e.target.id.startsWith('modal-edit-'))
                toggleModal(e.target.id, false);
        });

        // Delete
        document.querySelectorAll('.js-open-delete').forEach(btn => {
            btn.onclick = () => toggleModal('modal-delete-' + btn.dataset.id, true);
        });
        document.querySelectorAll('.js-close-delete').forEach(btn => {
            btn.onclick = () => {
                const id = btn.closest('[id^="modal-delete-"]').id.replace('modal-delete-', '');
                toggleModal('modal-delete-' + id, false);
            };
        });
        window.addEventListener('click', e => {
            if (e.target.id.startsWith('modal-delete-'))
                toggleModal(e.target.id, false);
        });
    </script>

    <script>
        // Client search functionality
        document.addEventListener('DOMContentLoaded', function () {
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
                                div.onclick = function () {
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
                clientSearch.addEventListener('input', function () {
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
                document.addEventListener('click', function (e) {
                    if (!clientSearch.contains(e.target) && !clientSearchResults.contains(e.target)) {
                        clientSearchResults.classList.add('hidden');
                    }
                });

                // Clear client filter button
                const clearClientFilter = document.getElementById('clear_client_filter');
                if (clearClientFilter) {
                    clearClientFilter.addEventListener('click', function () {
                        clientSearch.value = '';
                        clientIdHidden.value = '';
                        clientSearchResults.classList.add('hidden');
                        clientSearch.form.submit();
                    });
                }
            }

            // Modal client search (create)
            const clientSearchModal = document.getElementById('client_search_modal');
            const clientSearchResultsModal = document.getElementById('client_search_results_modal');
            const clientIdHiddenModal = document.getElementById('user_id');

            if (clientSearchModal) {
                clientSearchModal.addEventListener('input', function () {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        searchClients(this.value, clientSearchResultsModal, this, clientIdHiddenModal);
                    }, 300);
                });

                // Hide results when clicking outside
                document.addEventListener('click', function (e) {
                    if (!clientSearchModal.contains(e.target) && !clientSearchResultsModal.contains(e.target)) {
                        clientSearchResultsModal.classList.add('hidden');
                    }
                });
            }

            // Edit modals client search
            document.querySelectorAll('[id^="client_search_modal_"]').forEach(function (input) {
                const modalId = input.id.replace('client_search_modal_', '');
                const resultsContainer = document.getElementById('client_search_results_modal_' + modalId);
                const hiddenField = document.getElementById('user_id_' + modalId);

                input.addEventListener('input', function () {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        searchClients(this.value, resultsContainer, this, hiddenField);
                    }, 300);
                });

                // Hide results when clicking outside
                document.addEventListener('click', function (e) {
                    if (!input.contains(e.target) && !resultsContainer.contains(e.target)) {
                        resultsContainer.classList.add('hidden');
                    }
                });
            });

            // Set current client name in filter if exists
            <?php if ($filter_client_id): ?>
                fetch('search_clients.php?id=<?= $filter_client_id ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            clientSearch.value = data[0].name;
                        }
                    });
            <?php endif; ?>

            // Set current client names in edit modals
            <?php foreach ($equipments as $eq): ?>
                <?php if ($eq['user_id']): ?>
                    fetch('search_clients.php?id=<?= $eq['user_id'] ?>')
                        .then(response => response.json())
                        .then(data => {
                            if (data.length > 0) {
                                const input = document.getElementById('client_search_modal_<?= $eq['id'] ?>');
                                if (input) {
                                    input.value = data[0].name;
                                }
                            }
                        });
                <?php endif; ?>
            <?php endforeach; ?>
        });
    </script>
</body>

</html>