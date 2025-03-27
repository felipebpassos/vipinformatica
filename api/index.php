<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/Database.php';
require __DIR__ . '/config/Mailer.php';
require __DIR__ . '/models/User.php';
require __DIR__ . '/utils/helpers.php';

use Dotenv\Dotenv;

// Carregar variáveis de ambiente
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Configurações de cabeçalho
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validação dos campos obrigatórios
        $requiredFields = ['name', 'email', 'phone', 'service'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                jsonResponse('error', "Campo obrigatório faltando: {$field}", 400);
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

        // Cria/atualiza usuário
        User::createOrUpdate($name, $email, $phone, $passwordHash);

        // Cria o ticket
        $ticketId = Ticket::create($userId, $service, $priority);

        // Envia e-mail de confirmação
        Mailer::sendWelcomeEmail($email, $name, $password, $service);

        jsonResponse('success', 'Operação realizada com sucesso. Verifique seu email.');

    } catch (Exception $e) {
        jsonResponse('error', $e->getMessage(), 500);
    }
} else {
    jsonResponse('error', 'Método não permitido', 405);
}