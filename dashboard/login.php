<?php
require_once __DIR__ . '/includes/db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header("Location: " . $_ENV['APP_URL']);
            exit();
        }
    }
    $error = "Credenciais inválidas!";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | VIP Informática</title>
    <link rel="icon" type="image/png" href="./assets/img/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-cover bg-center max-h-screen" style="background-image: url('./assets/img/tech_bg.webp')">
    <div class="absolute inset-0 bg-black opacity-70 z-0"></div>

    <div class="relative z-10 min-h-screen flex items-center justify-center">
        <div class="space-y-4">
            <a href="https://vipltda.com.br" class="inline-block mb-8 flex justify-center">
                <img src="./assets/img/logo-2.png" alt="Logo" class="h-16 w-auto hover:opacity-80 transition-opacity" />
            </a>

            <div class="bg-white p-8 rounded-xl shadow-lg w-96">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Acesse sua conta</h2>

                <?php if ($error): ?>
                    <div class="text-red-500 mb-4"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <input type="email" name="email" placeholder="Email" required
                        class="w-full p-3 border rounded-lg" />

                    <div class="relative">
                        <input type="password" name="password" placeholder="Senha" required
                            class="w-full p-3 border rounded-lg pr-10" />
                        <button type="button" name="toggle_password"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>

                    <button type="submit"
                        class="w-full bg-red-600 text-white py-3 rounded-lg hover:bg-red-700 transition-colors">
                        Entrar
                    </button>
                </form>
            </div>

            <a href="https://vipltda.com.br/chat"
                class="text-white font-medium hover:text-gray-200 transition-colors flex m-auto w-fit">
                O que procura?
            </a>
        </div>
    </div>

    <script src="./assets/js/password-toggle.js"></script>
</body>

</html>