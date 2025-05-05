<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

// Limpa tabelas (e desabilita temporariamente as checks)
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("TRUNCATE TABLE event_logs");
$conn->query("TRUNCATE TABLE users");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Aqui definimos o ator das operações como '1' (será o ID do primeiro usuário criado)
$conn->query("SET @current_user_id = 1");

function seedUsers($conn)
{
    $users = [
        [
            'name' => 'Alysson Melo',
            'email' => 'alysson@gmail.com',
            'phone' => '11987654321',
            'password' => 'senhaSegura123',
            'role' => 'admin'
        ],
        [
            'name' => 'Felipe Passos',
            'email' => 'felipebpassos@gmail.com',
            'phone' => '21976543210',
            'password' => 'senhaSegura123',
            'role' => 'technician'
        ],
        [
            'name' => 'Pedro Santos',
            'email' => 'pedro.santos@gmail.com',
            'phone' => '31965432109',
            'password' => 'clientPass789',
            'role' => 'client'
        ]
    ];

    $stmt = $conn->prepare("
        INSERT INTO users (name, email, phone, password, role)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($users as $user) {
        $hashed = password_hash($user['password'], PASSWORD_DEFAULT);
        $stmt->bind_param(
            "sssss",
            $user['name'],
            $user['email'],
            $user['phone'],
            $hashed,
            $user['role']
        );
        if (!$stmt->execute()) {
            echo "Erro ao adicionar {$user['name']}: " . $stmt->error . "\n";
        } else {
            echo "Usuário {$user['name']} adicionado com sucesso.\n";
        }
    }

    $stmt->close();
}

if (php_sapi_name() === 'cli') {
    seedUsers($conn);
}

$conn->close();
