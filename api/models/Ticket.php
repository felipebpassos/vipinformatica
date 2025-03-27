<?php
class Ticket {
    public static function create($clientId, $serviceType, $priority = 'normal') {
        $pdo = Database::getInstance();
        
        // Verifica se o serviço é válido
        $stmt = $pdo->prepare("SELECT id FROM services WHERE service = ?");
        $stmt->execute([$serviceType]);
        $serviceId = $stmt->fetchColumn();

        if (!$serviceId) {
            throw new Exception("Tipo de serviço inválido");
        }

        // Cria o ticket
        $stmt = $pdo->prepare("INSERT INTO tickets 
                             (client_id, service_id, priority) 
                             VALUES (?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $clientId,
            $serviceId,
            $priority
        ]);

        return $pdo->lastInsertId();
    }
}