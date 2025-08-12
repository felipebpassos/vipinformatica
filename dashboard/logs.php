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

// --- ARRAYS DE TRADUÇÃO ---
$eventTypeTranslations = [
    'ticket_created' => 'Chamado Criado',
    'ticket_updated' => 'Chamado Atualizado',
    'ticket_closed' => 'Chamado Fechado',
    'ticket_deleted' => 'Chamado Excluído',
    'user_created' => 'Usuário Criado',
    'user_updated' => 'Usuário Atualizado',
    'user_deleted' => 'Usuário Excluído',
    'equipment_created' => 'Equipamento Criado',
    'equipment_updated' => 'Equipamento Atualizado',
    'equipment_deleted' => 'Equipamento Excluído'
];

$entityTypeTranslations = [
    'ticket' => 'Chamado',
    'user' => 'Usuário',
    'equipment' => 'Equipamento'
];

$statusTranslations = [
    'open' => 'Aberto',
    'in_progress' => 'Em Andamento',
    'closed' => 'Fechado'
];

$priorityTranslations = [
    'normal' => 'Normal',
    'high' => 'Alta'
];

$roleTranslations = [
    'admin' => 'Administrador',
    'technician' => 'Técnico',
    'client' => 'Cliente'
];

$equipmentTypeTranslations = [
    'Impressora' => 'Impressora',
    'Monitor' => 'Monitor',
    'Nobreak' => 'Nobreak',
    'Gabinete' => 'Gabinete',
    'Notebook' => 'Notebook',
    'Periféricos' => 'Periféricos',
    'Outros' => 'Outros'
];

// Função para traduzir valores
function translateValue($value, $translations)
{
    return isset($translations[$value]) ? $translations[$value] : $value;
}

// Função para formatar data/hora
function formatDateTime($datetime)
{
    $date = new DateTime($datetime);
    return $date->format('d/m/Y H:i:s');
}

// Função para processar e traduzir detalhes JSON
function formatDetails($details, $eventType)
{
    global $statusTranslations, $priorityTranslations, $roleTranslations, $equipmentTypeTranslations;

    if (empty($details)) {
        return 'Nenhum detalhe disponível';
    }

    $data = json_decode($details, true);
    if (!$data) {
        return htmlspecialchars($details);
    }

    $formatted = [];

    foreach ($data as $key => $value) {
        // Pular valores vazios ou nulos
        if (empty($value) && $value !== '0') {
            continue;
        }

        $translatedKey = translateKey($key);
        $translatedValue = translateValue($value, $statusTranslations);
        if ($translatedValue === $value) {
            $translatedValue = translateValue($value, $priorityTranslations);
        }
        if ($translatedValue === $value) {
            $translatedValue = translateValue($value, $roleTranslations);
        }
        if ($translatedValue === $value) {
            $translatedValue = translateValue($value, $equipmentTypeTranslations);
        }
        if ($translatedValue === $value) {
            $translatedValue = $value;
        }

        $formatted[] = "<strong>{$translatedKey}:</strong> " . htmlspecialchars($translatedValue);
    }

    return implode("\n", $formatted);
}

// Função para traduzir chaves dos detalhes
function translateKey($key)
{
    $keyTranslations = [
        'name' => 'Nome',
        'email' => 'E-mail',
        'role' => 'Perfil',
        'old_name' => 'Nome Anterior',
        'new_name' => 'Nome Novo',
        'old_email' => 'E-mail Anterior',
        'new_email' => 'E-mail Novo',
        'old_role' => 'Perfil Anterior',
        'new_role' => 'Perfil Novo',
        'client_id' => 'ID do Cliente',
        'description' => 'Descrição',
        'status' => 'Status',
        'old_status' => 'Status Anterior',
        'new_status' => 'Status Novo',
        'service_id' => 'ID do Serviço',
        'priority' => 'Prioridade',
        'closed_at' => 'Fechado em',
        'type' => 'Tipo',
        'equipment_code' => 'Código do Equipamento'
    ];

    return isset($keyTranslations[$key]) ? $keyTranslations[$key] : ucfirst(str_replace('_', ' ', $key));
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
                                    <option value="<?= htmlspecialchars($et) ?>" <?= $filterEventType === $et ? 'selected' : '' ?>><?= htmlspecialchars(translateValue($et, $eventTypeTranslations)) ?></option>
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
                                <td class="py-2 px-4">
                                    <?= htmlspecialchars(translateValue($log['event_type'], $eventTypeTranslations)) ?>
                                </td>
                                <td class="py-2 px-4">
                                    <?= htmlspecialchars(translateValue($log['entity_type'], $entityTypeTranslations)) ?>
                                </td>
                                <td class="py-2 px-4"><?= $log['entity_id'] ?></td>
                                <td class="py-2 px-4"><?= htmlspecialchars($log['performed_by']) ?></td>
                                <td class="py-2 px-4"><?= $log['is_client_action'] ? 'Sim' : 'Não' ?></td>
                                <td class="py-2 px-4"><?= formatDateTime($log['created_at']) ?></td>
                                <td class="py-2 px-4">
                                    <button onclick="openDetailsModal(<?= htmlspecialchars(json_encode($log)) ?>)"
                                        class="text-gray-400 hover:text-gray-500 transition-colors">
                                        <i class="fas fa-file-alt text-lg"></i>
                                    </button>
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

    <!-- Modal de Detalhes -->
    <div id="detailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
                <div class="flex items-center justify-between p-6 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Detalhes do Evento</h3>
                    <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto max-h-[60vh]">
                    <div id="modalContent" class="space-y-4">
                        <!-- Conteúdo será preenchido via JavaScript -->
                    </div>
                </div>
                <div class="flex justify-end p-6 border-t">
                    <button onclick="closeDetailsModal()"
                        class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openDetailsModal(logData) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('modalContent');

            // Traduzir os valores para exibição
            const eventTypeTranslations = <?= json_encode($eventTypeTranslations) ?>;
            const entityTypeTranslations = <?= json_encode($entityTypeTranslations) ?>;
            const statusTranslations = <?= json_encode($statusTranslations) ?>;
            const priorityTranslations = <?= json_encode($priorityTranslations) ?>;
            const roleTranslations = <?= json_encode($roleTranslations) ?>;
            const equipmentTypeTranslations = <?= json_encode($equipmentTypeTranslations) ?>;

            function translateValue(value, translations) {
                return translations[value] || value;
            }

            function formatDateTime(datetime) {
                const date = new Date(datetime);
                return date.toLocaleString('pt-BR');
            }

            function formatDetails(details, eventType) {
                if (!details) {
                    return 'Nenhum detalhe disponível';
                }

                try {
                    const data = JSON.parse(details);
                    if (!data) {
                        return details;
                    }

                    const keyTranslations = {
                        'name': 'Nome',
                        'email': 'E-mail',
                        'role': 'Perfil',
                        'old_name': 'Nome Anterior',
                        'new_name': 'Nome Novo',
                        'old_email': 'E-mail Anterior',
                        'new_email': 'E-mail Novo',
                        'old_role': 'Perfil Anterior',
                        'new_role': 'Perfil Novo',
                        'client_id': 'ID do Cliente',
                        'description': 'Descrição',
                        'status': 'Status',
                        'old_status': 'Status Anterior',
                        'new_status': 'Status Novo',
                        'service_id': 'ID do Serviço',
                        'priority': 'Prioridade',
                        'closed_at': 'Fechado em',
                        'type': 'Tipo',
                        'equipment_code': 'Código do Equipamento'
                    };

                    function translateKey(key) {
                        return keyTranslations[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    }

                    const formatted = [];
                    for (const [key, value] of Object.entries(data)) {
                        if (value === null || value === '' || value === undefined) continue;

                        const translatedKey = translateKey(key);
                        let translatedValue = translateValue(value, statusTranslations);
                        if (translatedValue === value) {
                            translatedValue = translateValue(value, priorityTranslations);
                        }
                        if (translatedValue === value) {
                            translatedValue = translateValue(value, roleTranslations);
                        }
                        if (translatedValue === value) {
                            translatedValue = translateValue(value, equipmentTypeTranslations);
                        }
                        if (translatedValue === value) {
                            translatedValue = value;
                        }

                        formatted.push(`<div><strong>${translatedKey}:</strong> ${translatedValue}</div>`);
                    }

                    return formatted.join('');
                } catch (e) {
                    return details;
                }
            }

            // Montar o conteúdo do modal
            const modalHtml = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">Informações Gerais</h4>
                        <div class="space-y-2 text-sm">
                            <div><strong>Tipo de Evento:</strong> ${translateValue(logData.event_type, eventTypeTranslations)}</div>
                            <div><strong>Entidade:</strong> ${translateValue(logData.entity_type, entityTypeTranslations)}</div>
                            <div><strong>ID da Entidade:</strong> ${logData.entity_id}</div>
                            <div><strong>Realizado por:</strong> ${logData.performed_by}</div>
                            <div><strong>Ação do Cliente:</strong> ${logData.is_client_action ? 'Sim' : 'Não'}</div>
                            <div><strong>Data/Hora:</strong> ${formatDateTime(logData.created_at)}</div>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">Detalhes Específicos</h4>
                        <div class="text-sm space-y-1">
                            ${formatDetails(logData.details, logData.event_type)}
                        </div>
                    </div>
                </div>
            `;

            content.innerHTML = modalHtml;
            modal.classList.remove('hidden');
        }

        function closeDetailsModal() {
            const modal = document.getElementById('detailsModal');
            modal.classList.add('hidden');
        }

        // Fechar modal ao clicar fora dele
        document.getElementById('detailsModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeDetailsModal();
            }
        });

        // Fechar modal com tecla ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeDetailsModal();
            }
        });
    </script>
</body>

</html>