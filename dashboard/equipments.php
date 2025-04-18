<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db_connect.php';

// Define a página atual para navegação
$_SESSION['current_page'] = 'equipments';

// Verificar permissão (apenas admin e technician)
if (!in_array($_SESSION['user_role'], ['admin', 'technician'])) {
    header("Location: " . $_ENV['APP_URL']);
    exit();
}

$errors = [];

// --- PAGINAÇÃO CONFIGURAÇÃO ---
$perPage = 10;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page'])
    ? (int) $_GET['page']
    : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}
$offset = ($currentPage - 1) * $perPage;

// Conta total de equipamentos
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM equipment");
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

// Calcula total de páginas
$totalPages = (int) ceil($totalRecords / $perPage);

// Se for POST, trata criação de novo equipamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $type = $_POST['type'] ?? '';
    $equipmentCode = trim($_POST['equipment_code'] ?? '');
    if (empty($userId)) {
        $errors[] = 'Selecione um cliente.';
    }
    if (empty($type)) {
        $errors[] = 'Selecione o tipo de equipamento.';
    }
    if (empty($equipmentCode)) {
        $errors[] = 'Informe o código do equipamento.';
    }
    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO equipment (user_id, type, equipment_code) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iss", $userId, $type, $equipmentCode);
        if ($stmt->execute()) {
            header("Location: equipments.php?success=Equipamento+criado+com+sucesso");
            exit;
        } else {
            $errors[] = 'Erro ao criar o equipamento: ' . $stmt->error;
        }
    }
}

// Carrega lista de clientes para o select
$clients = [];
$res = $conn->query("SELECT id, name FROM users WHERE role = 'client' ORDER BY name ASC");
while ($row = $res->fetch_assoc()) {
    $clients[] = $row;
}

// Carrega equipamentos paginados
$stmt = $conn->prepare(
    "SELECT e.*, u.name AS client_name
     FROM equipment e
     JOIN users u ON e.user_id = u.id
     ORDER BY u.name ASC, e.type ASC
     LIMIT ? OFFSET ?"
);
$stmt->bind_param("ii", $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
$equipments = $result->fetch_all(MYSQLI_ASSOC);
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
            <nav class="flex items-center text-gray-600 mb-4">
                <i class="fas fa-home mr-2"></i><span>Home</span>
                <span class="mx-2 text-gray-400">/</span>
                <span class="font-semibold text-gray-800">Equipamentos</span>
            </nav>

            <div class="container p-4 bg-white rounded shadow mb-4">
                <div class="flex justify-between items-center mb-4">
                    <h1 class="text-base font-bold">Equipamentos</h1>
                    <button id="openModalButton"
                        class="bg-red-500 text-white text-sm px-4 py-2 rounded hover:bg-red-600 flex items-center gap-2">
                        <i class="fas fa-plus"></i> Novo equipamento
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
                            <th class="py-2 px-4 border-b">Cliente</th>
                            <th class="py-2 px-4 border-b">Tipo</th>
                            <th class="py-2 px-4 border-b">Código</th>
                            <th class="py-2 px-4 border-b">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipments as $eq): ?>
                        <tr class="text-center border-t">
                            <td class="py-2 px-4"><?= $eq['id'] ?></td>
                            <td class="py-2 px-4"><?= htmlspecialchars($eq['client_name']) ?></td>
                            <td class="py-2 px-4"><?= htmlspecialchars($eq['type']) ?></td>
                            <td class="py-2 px-4"><?= htmlspecialchars($eq['equipment_code']) ?></td>
                            <td class="py-2 px-4">
                                <a href="equipment_detail.php?id=<?= $eq['id'] ?>"
                                   class="text-blue-500 hover:underline">Ver detalhes</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($equipments)): ?>
                        <tr>
                            <td colspan="5" class="py-4 text-center text-gray-500">Nenhum registro encontrado.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- PAGINAÇÃO -->
                <div class="flex items-center justify-between px-4 py-2 bg-white rounded-b shadow-md">
                    <div class="text-sm text-gray-700">
                        <?php
                        $start = $totalRecords > 0 ? $offset + 1 : 0;
                        $end = $offset + count($equipments);
                        echo "{$start} a {$end} de {$totalRecords} registros";
                        ?>
                    </div>
                    <nav aria-label="Paginação">
                        <ul class="inline-flex items-center -space-x-px">
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
                </div>
            </div>

            <!-- Modal para criação de novo Equipamento -->
            <div id="modal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
                <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
                    <button id="closeModalButton" class="absolute top-2 right-2 text-gray-500 text-2xl">&times;</button>
                    <h2 class="text-xl font-bold mb-4">Novo Equipamento</h2>
                    <form action="equipments.php" method="POST">
                        <div class="mb-4">
                            <label for="user_id" class="block text-gray-700">Cliente</label>
                            <select name="user_id" id="user_id" class="w-full p-2 border rounded">
                                <option value="">Selecione um cliente</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= $client['id'] ?>">
                                        <?= htmlspecialchars($client['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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

    <script>
        const openModalButton = document.getElementById('openModalButton');
        const closeModalButton = document.getElementById('closeModalButton');
        const modal = document.getElementById('modal');

        openModalButton.addEventListener('click', () => modal.classList.remove('hidden'));
        closeModalButton.addEventListener('click', () => modal.classList.add('hidden'));
        window.addEventListener('click', e => {
            if (e.target === modal) modal.classList.add('hidden');
        });
    </script>
</body>
</html>
