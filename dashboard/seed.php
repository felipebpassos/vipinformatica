<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

function seedUsers($conn) {
    // Users to seed
    $users = [
        [
            'name' => 'Alysson Melo',
            'email' => 'alysson@gmail.com',
            'phone' => '(11) 98765-4321',
            'password' => 'senhaSegura123',
            'role' => 'owner'
        ],
        [
            'name' => 'Felipe Passos',
            'email' => 'felipebpassos@gmail.com',
            'phone' => '(21) 97654-3210',
            'password' => 'Fec3.,?!',
            'role' => 'admin'
        ],
        [
            'name' => 'Pedro Santos',
            'email' => 'pedro.santos@gmail.com',
            'phone' => '(31) 96543-2109',
            'password' => 'clientPass789',
            'role' => 'client'
        ]
    ];

    // Prepare the insert statement
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");

    foreach ($users as $user) {
        // Hash the password
        $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);

        // Bind parameters and execute
        $stmt->bind_param(
            "sssss", 
            $user['name'], 
            $user['email'], 
            $user['phone'], 
            $hashed_password, 
            $user['role']
        );

        if ($stmt->execute()) {
            echo "User {$user['name']} added successfully.\n";
        } else {
            echo "Error adding user {$user['name']}: " . $stmt->error . "\n";
        }
    }

    $stmt->close();
}

// Check if the script is being run directly
if (php_sapi_name() === 'cli') {
    // Clear existing users before seeding (optional)
    $conn->query("DELETE FROM users");

    // Seed users
    seedUsers($conn);
}

$conn->close();
?>