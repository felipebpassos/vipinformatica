<?php
class User
{
    public static function createOrUpdate($name, $email, $phone, $passwordHash)
    {
        $pdo = Database::getInstance();

        // Verificar se usuário existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userExists = $stmt->fetchColumn();

        if ($userExists) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, password = ? WHERE email = ?");
            $stmt->execute([$name, $phone, $passwordHash, $email]);
            return $userExists;
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $passwordHash]);
            return $pdo->lastInsertId();
        }
    }
}
