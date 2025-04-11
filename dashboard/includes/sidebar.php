<!-- Sidebar -->
<div class="hidden sm:flex flex-col w-64 h-screen bg-gray-800 text-white">
    <!-- Logo -->
    <div class="p-4">
        <a href="<?= $_ENV['APP_URL'] ?>">
            <img src="<?= $_ENV['APP_URL'] ?>/assets/img/logo-2.png" alt="Logo" class="w-36 h-auto my-4 mx-auto">
        </a>
    </div>

    <!-- Navegação -->
    <nav class="flex flex-col px-4">
        <?php
        function activeClass($page)
        {
            return ($_SESSION['current_page'] === $page) ? 'bg-gray-900' : '';
        }
        ?>

        <?php if ($_SESSION['user_role'] === 'client'): ?>
            <a href="<?= $_ENV['APP_URL'] . '/tickets.php' ?>"
                class="mb-1 py-2 px-3 rounded hover:bg-gray-700 <?= activeClass('tickets') ?>">
                <i class="fa-solid fa-file-lines mr-2"></i> Meus Chamados
            </a>

        <?php elseif ($_SESSION['user_role'] === 'technician'): ?>
            <a href="<?= $_ENV['APP_URL'] . '/tickets.php' ?>"
                class="mb-1 py-2 px-3 rounded hover:bg-gray-700 <?= activeClass('tickets') ?>">
                <i class="fa-solid fa-file-lines mr-2"></i> Chamados
            </a>
            <a href="<?= $_ENV['APP_URL'] . '/equipments.php' ?>"
                class="mb-1 py-2 px-3 rounded hover:bg-gray-700 <?= activeClass('equipments') ?>">
                <i class="fa-solid fa-laptop mr-2"></i> Equipamentos
            </a>
            <a href="<?= $_ENV['APP_URL'] . '/clients.php' ?>"
                class="mb-1 py-2 px-3 rounded hover:bg-gray-700 <?= activeClass('clients') ?>">
                <i class="fa-solid fa-user-group mr-2"></i> Clientes
            </a>

        <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
            <a href="<?= $_ENV['APP_URL'] . '/tickets.php' ?>"
                class="mb-1 py-2 px-3 rounded hover:bg-gray-700 <?= activeClass('tickets') ?>">
                <i class="fa-regular fa-clipboard mr-2"></i> Chamados
            </a>
            <a href="<?= $_ENV['APP_URL'] . '/equipments.php' ?>"
                class="mb-1 py-2 px-3 rounded hover:bg-gray-700 <?= activeClass('equipments') ?>">
                <i class="fa-solid fa-toolbox mr-2"></i> Equipamentos
            </a>
            <a href="<?= $_ENV['APP_URL'] . '/clients.php' ?>"
                class="mb-1 py-2 px-3 rounded hover:bg-gray-700 <?= activeClass('clients') ?>">
                <i class="fa-solid fa-user-group mr-2"></i> Clientes
            </a>
            <a href="<?= $_ENV['APP_URL'] . '/logs.php' ?>"
                class="mb-1 py-2 px-3 rounded hover:bg-gray-700 <?= activeClass('logs') ?>">
                <i class="fa-solid fa-scroll mr-2"></i> Histórico
            </a>
        <?php endif; ?>

        <a href="<?= $_ENV['APP_URL'] . '/logout.php' ?>" class="mt-0 py-2 px-3 rounded hover:bg-gray-700">
            <i class="fa-solid fa-right-from-bracket mr-2"></i> Sair
        </a>
    </nav>
</div>