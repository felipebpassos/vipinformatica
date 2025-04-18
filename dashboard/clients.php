<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db_connect.php';

// Recupera papel do usuário e define página atual
$userRole = $_SESSION['user_role']; // 'admin', 'technician' ou 'client'
$_SESSION['current_page'] = 'clients';

// Apenas admin e technician podem acessar
if (!in_array($userRole, ['admin', 'technician'])) {
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

// Conta o total de clientes
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'client'");
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = (int) ceil($totalRecords / $perPage);

// Inicializa variável de erros
$errors = [];

// Se for POST, trata criação de novo cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            header("Location: clients.php?success=Cliente+criado+com+sucesso");
            exit;
        } else {
            $errors[] = 'Erro ao criar cliente: ' . $stmt->error;
        }
    }
}

// Carrega clientes paginados
$stmt = $conn->prepare("
    SELECT id, name, email, phone, created_at
    FROM users
    WHERE role = 'client'
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
$clients = $result->fetch_all(MYSQLI_ASSOC);
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
                <!-- Título e botão de novo -->
                <div class="flex justify-between items-center mb-4">
                    <h1 class="text-base font-bold">Clientes</h1>
                    <button id="openModalButton"
                        class="bg-red-500 text-white text-sm px-4 py-2 rounded hover:bg-red-600 flex items-center gap-2">
                        <i class="fas fa-plus"></i> Novo cliente
                    </button>
                </div>

                <!-- Mensagens de sucesso/erro -->
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

                <!-- Tabela de clientes -->
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
                        <?php foreach ($clients as $client): ?>
                            <tr class="text-center border-t">
                                <td class="py-2 px-4"><?= $client['id'] ?></td>
                                <td class="py-2 px-4"><?= htmlspecialchars($client['name']) ?></td>
                                <td class="py-2 px-4"><?= htmlspecialchars($client['email']) ?></td>
                                <td class="py-2 px-4">
                                    <?php
                                    // 1. Remove tudo que não for dígito
                                    $digits = preg_replace('/\D/', '', $client['phone']);

                                    // 2. Formata (XX) XXXXX-XXXX
                                    if (strlen($digits) === 11) {
                                        $formatted = sprintf(
                                            '(%s) %s-%s',
                                            substr($digits, 0, 2),
                                            substr($digits, 2, 5),
                                            substr($digits, 7)
                                        );
                                    } else {
                                        // fallback genérico
                                        $formatted = $client['phone'];
                                    }

                                    // 3. Monta link pro WhatsApp (usando DDI 55)
                                    $whatsLink = 'https://wa.me/55' . $digits;
                                    ?>
                                    <a href="<?= $whatsLink ?>" target="_blank" rel="noopener noreferrer"
                                        class="flex items-center justify-center text-gray-500 hover:underline">
                                        <!-- 4. Ícone FA WhatsApp em verde -->
                                        <i class="fab fa-whatsapp text-green-500 mr-2"></i>
                                        <?= $formatted ?>
                                    </a>
                                </td>
                                <td class="py-2 px-4">
                                    <?= date("d/m/Y H:i", strtotime($client['created_at'])) ?>
                                </td>
                                <td class="py-2 px-4">
                                    <a href="client_detail.php?id=<?= $client['id'] ?>"
                                        class="text-gray-500 hover:underline">
                                        Ver detalhes
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="6" class="py-4 text-center text-gray-500">
                                    Nenhum cliente encontrado.
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
                        $end = $offset + count($clients);
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

            <!-- Modal para criação de novo Cliente -->
            <div id="modal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
                <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
                    <button id="closeModalButton" class="absolute top-2 right-2 text-gray-500 text-2xl">&times;</button>
                    <h2 class="text-xl font-bold mb-4">Novo Cliente</h2>
                    <form action="clients.php" method="POST">
                        <div class="mb-4">
                            <label for="name" class="block text-gray-700">Nome</label>
                            <input type="text" name="name" id="name" class="w-full p-2 border rounded"
                                placeholder="Nome completo">
                        </div>
                        <div class="mb-4">
                            <label for="email" class="block text-gray-700">Email</label>
                            <input type="email" name="email" id="email" class="w-full p-2 border rounded"
                                placeholder="email@exemplo.com">
                        </div>
                        <div class="mb-4">
                            <label for="phone" class="block text-gray-700">Whatsapp</label>
                            <input type="text" name="phone" id="phone" class="w-full p-2 border rounded"
                                placeholder="(XX) XXXXX-XXXX">
                        </div>
                        <div class="mb-4">
                            <label for="password" class="block text-gray-700">Senha</label>
                            <input type="password" name="password" id="password" class="w-full p-2 border rounded"
                                placeholder="Senha de acesso">
                        </div>
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                            Salvar Cliente
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- Script de abertura/fechamento do modal -->
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