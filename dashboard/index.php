<?php
require_once __DIR__ . '/includes/auth_check.php';

$_SESSION['current_page'] = 'home';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Vip Informática</title>
    <link rel="icon" type="image/png" href="./assets/img/icon.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-gray-100">
    <div class="flex">
        <!-- Inclui a sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <div class="flex-1 container mx-auto p-4">
            <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
            <!-- Conteúdo do dashboard -->
        </div>
    </div>
</body>

</html>