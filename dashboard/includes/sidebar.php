<div class="sidebar">
    <div class="logo">
        <img src="assets/images/logo.png" alt="Logo">
    </div>
    <nav>
        <?php if ($_SESSION['user_role'] === 'client'): ?>
            <a href="tickets.php">Meus Chamados</a>
        <?php elseif ($_SESSION['user_role'] === 'technician'): ?>
            <a href="tickets.php">Chamados</a>
            <a href="clients.php">Clientes</a>
        <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
            <a href="tickets.php">Chamados</a>
            <a href="clients.php">Clientes</a>
            <a href="logs.php">Logs</a>
        <?php endif; ?>
        <a href="logout.php">Sair</a>
    </nav>
</div>