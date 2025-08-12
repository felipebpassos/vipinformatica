<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db_connect.php';

// Recupera dados de sessão
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role']; // 'admin', 'technician' ou 'client'
$_SESSION['current_page'] = 'clients';

// Apenas admin e technician podem acessar
if (!in_array($userRole, ['admin', 'technician'])) {
    header("Location: " . $_ENV['APP_URL']);
    exit();
}

// --- BUSCA POR NOME ---
$search = trim($_GET['search'] ?? '');
$searchParam = '%' . $search . '%';

// --- PAGINAÇÃO CONFIGURAÇÃO ---
$perPage = 10;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page'])
    ? (int) $_GET['page']
    : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}
$offset = ($currentPage - 1) * $perPage;

// Conta o total de clientes (considerando busca)
if ($search !== '') {
    $countStmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM users
        WHERE role = 'client' AND name LIKE ?
    ");
    $countStmt->bind_param("s", $searchParam);
} else {
    $countStmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM users
        WHERE role = 'client'
    ");
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

    if ($action === 'create_client') {
        // criação de novo cliente
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($name)) {
            $errors[] = 'Informe o nome.';
        }
        if (empty($email)) {
            $errors[] = 'Informe o email.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido.';
        }
        if (empty($password)) {
            $errors[] = 'Informe a senha.';
        }

        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO users (name, email, phone, password, role)
                VALUES (?, ?, ?, ?, 'client')
            ");
            $stmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: clients.php?page={$currentPage}&search=" . urlencode($search) . "&success=Cliente+criado+com+sucesso");
                exit;
            } else {
                $errors[] = 'Erro ao criar cliente: ' . $stmt->error;
            }
        }

    } elseif ($action === 'edit_client' && in_array($userRole, ['admin', 'technician'])) {
        // edição de cliente
        $clientId = intval($_POST['client_id']);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name)) {
            $errors[] = 'Informe o nome.';
        }
        if (empty($email)) {
            $errors[] = 'Informe o email.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido.';
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("
                UPDATE users
                SET name = ?, email = ?, phone = ?
                WHERE id = ? AND role = 'client'
            ");
            $stmt->bind_param("sssi", $name, $email, $phone, $clientId);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: clients.php?page={$currentPage}&search=" . urlencode($search) . "&success=Cliente+atualizado+com+sucesso");
                exit;
            } else {
                $errors[] = 'Erro ao atualizar cliente: ' . $stmt->error;
            }
        }

    } elseif ($action === 'delete_client' && $userRole === 'admin') {
        // exclusão de cliente
        $clientId = intval($_POST['client_id']);
        if ($conn->query("DELETE FROM users WHERE id = {$clientId} AND role = 'client'")) {
            header("Location: clients.php?page={$currentPage}&search=" . urlencode($search) . "&success=Cliente+excluído+com+sucesso");
            exit;
        } else {
            $errors[] = 'Erro ao excluir cliente: ' . $conn->error;
        }
    }
}

// Carrega clientes paginados (considerando busca)
if ($search !== '') {
    $stmt = $conn->prepare("
        SELECT id, name, email, phone, created_at
        FROM users
        WHERE role = 'client' AND name LIKE ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("sii", $searchParam, $perPage, $offset);
} else {
    $stmt = $conn->prepare("
        SELECT id, name, email, phone, created_at
        FROM users
        WHERE role = 'client'
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $perPage, $offset);
}
$stmt->execute();
$clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes | Vip Informática</title>
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
                <span class="font-semibold text-gray-800">Clientes</span>
            </nav>

            <div class="container p-4 bg-white rounded shadow mb-4">
                <!-- Título e Novo -->
                <div class="flex justify-between items-center mb-4">
                    <h1 class="text-base font-bold">Clientes</h1>
                    <button id="btn-open-create"
                        class="bg-red-500 text-white text-sm px-4 py-2 rounded hover:bg-red-600 flex items-center gap-2">
                        <i class="fas fa-plus"></i> Novo cliente
                    </button>
                </div>

                <!-- Formulário de busca -->
                <form method="GET" action="clients.php" class="mb-4 flex items-center">
                    <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Buscar por nome" class="w-full max-w-xs p-2 border rounded mr-2">
                    <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 mr-2">
                        Buscar
                    </button>
                    <?php if ($search !== ''): ?>
                        <a href="clients.php" class="text-gray-500 hover:underline">Limpar</a>
                    <?php endif; ?>
                </form>

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

                <!-- Tabela -->
                <table class="min-w-full bg-white shadow-md rounded mb-0">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">ID</th>
                            <th class="py-2 px-4 border-b">Nome</th>
                            <th class="py-2 px-4 border-b">Email</th>
                            <th class="py-2 px-4 border-b">Whatsapp</th>
                            <th class="py-2 px-4 border-b">Criado em</th>
                            <th class="py-2 px-4 border-b">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="6" class="py-4 text-center text-gray-500">
                                    Nenhum cliente encontrado.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($clients as $c): ?>
                            <tr class="text-center border-t">
                                <td class="py-2 px-4"><?= $c['id'] ?></td>
                                <td class="py-2 px-4"><?= htmlspecialchars($c['name']) ?></td>
                                <td class="py-2 px-4"><?= htmlspecialchars($c['email']) ?></td>
                                <td class="py-2 px-4">
                                    <?php if (empty($c['phone'])): ?>
                                        -
                                    <?php else: ?>
                                        <?php
                                        $digits = preg_replace('/\D/', '', $c['phone']);
                                        if (strlen($digits) === 11) {
                                            $formatted = sprintf(
                                                '(%s) %s-%s',
                                                substr($digits, 0, 2),
                                                substr($digits, 2, 5),
                                                substr($digits, 7)
                                            );
                                        } else {
                                            $formatted = $c['phone'];
                                        }
                                        $whatsLink = 'https://wa.me/55' . $digits;
                                        ?>
                                        <a href="<?= $whatsLink ?>" target="_blank" rel="noopener"
                                            class="flex items-center justify-center text-gray-500 hover:underline">
                                            <i class="fab fa-whatsapp text-green-500 mr-2"></i>
                                            <?= $formatted ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4">
                                    <?= date("d/m/Y H:i", strtotime($c['created_at'])) ?>
                                </td>
                                <td class="py-2 px-4 space-x-2">
                                    <!-- Botões de ação -->
                                    <button class="js-open-info" data-id="<?= $c['id'] ?>">
                                        <i class="fas fa-info-circle text-gray-400 hover:text-gray-500"></i>
                                    </button>
                                    <button class="js-open-edit" data-id="<?= $c['id'] ?>">
                                        <i class="fas fa-edit text-gray-400 hover:text-gray-500"></i>
                                    </button>
                                    <?php if ($userRole === 'admin'): ?>
                                        <button class="js-open-delete" data-id="<?= $c['id'] ?>">
                                            <i class="fas fa-trash text-red-500 hover:text-red-600"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Info Modal -->
                            <div id="modal-info-<?= $c['id'] ?>"
                                class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
                                <div class="bg-white p-6 rounded-lg w-full max-w-lg relative">
                                    <button
                                        class="js-close-info absolute top-2 right-2 text-gray-500 text-2xl">&times;</button>
                                    <h2 class="text-xl font-bold mb-4">Detalhes do Cliente #<?= $c['id'] ?></h2>
                                    <ul class="space-y-2 text-gray-700">
                                        <li><strong>Nome:</strong> <?= htmlspecialchars($c['name']) ?></li>
                                        <li><strong>Email:</strong> <?= htmlspecialchars($c['email']) ?></li>
                                        <li><strong>Whatsapp:</strong>
                                            <?= empty($c['phone']) ? '-' : htmlspecialchars($formatted) ?></li>
                                        <li><strong>Criado em:</strong>
                                            <?= date("d/m/Y H:i", strtotime($c['created_at'])) ?></li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Edit Modal -->
                            <div id="modal-edit-<?= $c['id'] ?>"
                                class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
                                <div class="bg-white p-6 rounded-lg w-full max-w-md relative overflow-auto max-h-screen">
                                    <button
                                        class="js-close-edit absolute top-2 right-2 text-gray-500 text-2xl">&times;</button>
                                    <h2 class="text-xl font-bold mb-4">Editar Cliente #<?= $c['id'] ?></h2>
                                    <form action="clients.php?page=<?= $currentPage ?>&search=<?= urlencode($search) ?>"
                                        method="POST">
                                        <input type="hidden" name="action" value="edit_client">
                                        <input type="hidden" name="client_id" value="<?= $c['id'] ?>">

                                        <div class="mb-4">
                                            <label class="block text-gray-700">Nome</label>
                                            <input type="text" name="name" value="<?= htmlspecialchars($c['name']) ?>"
                                                class="w-full p-2 border rounded" required>
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-gray-700">Email</label>
                                            <input type="email" name="email" value="<?= htmlspecialchars($c['email']) ?>"
                                                class="w-full p-2 border rounded" required>
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-gray-700">Whatsapp</label>
                                            <input type="text" name="phone" value="<?= htmlspecialchars($c['phone']) ?>"
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
                            <div id="modal-delete-<?= $c['id'] ?>"
                                class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
                                <div class="bg-white p-6 rounded-lg w-full max-w-sm relative">
                                    <button
                                        class="js-close-delete absolute top-2 right-2 text-gray-500 text-2xl">&times;</button>
                                    <h2 class="text-xl font-bold mb-4">Excluir Cliente #<?= $c['id'] ?></h2>
                                    <p class="mb-4">Você tem certeza que deseja excluir este cliente?</p>
                                    <form action="clients.php?page=<?= $currentPage ?>&search=<?= urlencode($search) ?>"
                                        method="POST" class="flex justify-end space-x-2">
                                        <input type="hidden" name="action" value="delete_client">
                                        <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
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
                        $end = $offset + count($clients);
                        echo "{$start} a {$end} de {$totalRecords} registros";
                        ?>
                    </div>
                    <?php if ($totalRecords > 0): ?>
                        <nav aria-label="Paginação">
                            <ul class="inline-flex items-center -space-x-px mt-2">
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <li>
                                        <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>" class="px-3 py-2 border text-sm font-medium
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
                    <h2 class="text-xl font-bold mb-4">Novo Cliente</h2>
                    <form action="clients.php?page=<?= $currentPage ?>&search=<?= urlencode($search) ?>" method="POST">
                        <input type="hidden" name="action" value="create_client">
                        <div class="mb-4">
                            <label for="name" class="block text-gray-700">Nome</label>
                            <input type="text" name="name" id="name" class="w-full p-2 border rounded"
                                placeholder="Nome completo" required>
                        </div>
                        <div class="mb-4">
                            <label for="email" class="block text-gray-700">Email</label>
                            <input type="email" name="email" id="email" class="w-full p-2 border rounded"
                                placeholder="email@exemplo.com" required>
                        </div>
                        <div class="mb-4">
                            <label for="phone" class="block text-gray-700">Whatsapp</label>
                            <input type="text" name="phone" id="phone" class="w-full p-2 border rounded"
                                placeholder="(XX) XXXXX-XXXX">
                        </div>
                        <div class="mb-4">
                            <label for="password" class="block text-gray-700">Senha</label>
                            <input type="password" name="password" id="password" class="w-full p-2 border rounded"
                                placeholder="Senha de acesso" required>
                        </div>
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                            Salvar Cliente
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- Script de abertura/fechamento dos Modais -->
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
            if (e.target.id.startsWith('modal-info-')) toggleModal(e.target.id, false);
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
            if (e.target.id.startsWith('modal-edit-')) toggleModal(e.target.id, false);
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
            if (e.target.id.startsWith('modal-delete-')) toggleModal(e.target.id, false);
        });
    </script>
</body>

</html>