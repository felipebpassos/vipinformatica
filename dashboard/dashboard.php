<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main>
    <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
    <!-- Conteúdo do dashboard -->
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>