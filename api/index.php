<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/Database.php';
require __DIR__ . '/config/Mailer.php';
require __DIR__ . '/models/User.php';
require __DIR__ . '/models/Ticket.php';
require __DIR__ . '/utils/helpers.php';

use Dotenv\Dotenv;

// Carregar variáveis de ambiente
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Configurações de CORS mais seguras
$allowedOrigins = [
    'https://vipltda.com.br',
];

$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Verificação de origem
if (in_array($requestOrigin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: {$requestOrigin}");
} else {
    // Bloqueie origens não autorizadas
    header("HTTP/1.1 403 Forbidden");
    exit('Acesso não autorizado');
}

// Configurações de cabeçalho mais específicas
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 86400"); // 24 horas

// Responda a preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 204 No Content");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        error_log('Data recebida: ' . json_encode($data));


        // Validação dos campos obrigatórios
        $requiredFields = ['name', 'email', 'phone', 'service'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                jsonResponse('error', "Campo obrigatório faltando ou vazio: {$field}", 400);
            }
        }

        // Sanitização e validação
        $name = htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8');
        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $service = htmlspecialchars($data['service'], ENT_QUOTES, 'UTF-8');
        $priority = isset($data['priority']) ? $data['priority'] : 'normal';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse('error', 'Email inválido', 400);
        }

        $password = generateRandomPassword();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $pdo = Database::getInstance();

        // Definimos um ator genérico para permitir o INSERT sem erro na trigger
        $pdo->exec("SET @current_user_id = 14");

        $userId = User::createOrUpdate($name, $email, $phone, $passwordHash);

        // Agora sim definimos o ator real para as ações subsequentes
        $pdo->exec("SET @current_user_id = " . intval($userId));

        // agora cria o chamado usando o mesmo usuário como ator
        Ticket::create($userId, $service, $priority);

        // Envia e-mail de confirmação
        Mailer::sendWelcomeEmail($email, $name, $password, $service);

        jsonResponse('success', 'Operação realizada com sucesso. Verifique seu email.');

    } catch (Exception $e) {
        jsonResponse('error', $e->getMessage(), 500);
    }
} else {
    jsonResponse('error', 'Método não permitido', 405);
}