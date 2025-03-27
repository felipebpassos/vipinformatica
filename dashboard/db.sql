DROP DATABASE vipinformatica;

select * from users;

-- Criação do banco de dados
CREATE DATABASE vipinformatica
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE vipinformatica;

-- Usuários (clientes e administradores)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'technician', 'client') DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Serviços (atualizado para ter apenas id e service)
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service ENUM(
        'Manutenção e conserto de equipamentos', 
        'Formatação e instalação de programas', 
        'Desenvolvimento de sites e sistemas', 
        'Consultoria em TI'
    ) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Equipamentos (para serviços de manutenção)
CREATE TABLE equipment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM(
        'Impressora', 
        'Monitor', 
        'Nobreak', 
        'Gabinete', 
        'Notebook', 
        'Periféricos', 
        'Outros'
    ) NOT NULL,
    equipment_code VARCHAR(50) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chamados/Tickets (atualizado com service_id e prioridade)
CREATE TABLE tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_id INT NOT NULL,
    client_id INT NOT NULL,
    description TEXT,
    status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    priority ENUM('normal', 'high') DEFAULT 'normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at DATETIME,
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de relação entre Tickets e Equipamentos
CREATE TABLE ticket_equipment (
    ticket_id INT NOT NULL,
    equipment_id INT NOT NULL,
    PRIMARY KEY (ticket_id, equipment_id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (equipment_id) REFERENCES equipment(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Histórico/Updates dos Chamados
CREATE TABLE ticket_updates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Log de Eventos (atualizada para incluir eventos de equipamento)
CREATE TABLE event_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_type ENUM(
        'ticket_created', 
        'ticket_updated', 
        'ticket_closed', 
        'user_created', 
        'user_updated', 
        'user_deleted',
        'equipment_created',
        'equipment_updated',
        'equipment_deleted'
    ) NOT NULL,
    entity_id INT NOT NULL,
    entity_type ENUM('ticket', 'user', 'equipment') NOT NULL,
    performed_by_user_id INT NOT NULL,
    is_client_action BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    details JSON,
    FOREIGN KEY (performed_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Trigger para log de criação de usuário
DELIMITER //
CREATE TRIGGER user_create_log AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO event_logs (
        event_type, 
        entity_id, 
        entity_type, 
        performed_by_user_id,
        is_client_action,
        details
    ) VALUES (
        'user_created', 
        NEW.id, 
        'user', 
        NEW.id,
        NEW.role = 'client',
        JSON_OBJECT(
            'name', NEW.name,
            'email', NEW.email,
            'role', NEW.role
        )
    );
END;//
DELIMITER ;

-- Trigger para log de atualização de usuário
DELIMITER //
CREATE TRIGGER user_update_log AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    INSERT INTO event_logs (
        event_type, 
        entity_id, 
        entity_type, 
        performed_by_user_id,
        is_client_action,
        details
    ) VALUES (
        'user_updated', 
        NEW.id, 
        'user', 
        @current_user_id,  
        FALSE,
        JSON_OBJECT(
            'old_name', OLD.name, 
            'new_name', NEW.name,
            'old_email', OLD.email, 
            'new_email', NEW.email, 
            'old_role', OLD.role,
            'new_role', NEW.role
        )
    );
END;//
DELIMITER ;

-- Trigger para log de deleção de usuário
DELIMITER //
CREATE TRIGGER user_delete_log BEFORE DELETE ON users
FOR EACH ROW
BEGIN
    INSERT INTO event_logs (
        event_type, 
        entity_id, 
        entity_type, 
        performed_by_user_id,
        is_client_action,
        details
    ) VALUES (
        'user_deleted', 
        OLD.id, 
        'user', 
        @current_user_id,  
        FALSE,
        JSON_OBJECT(
            'name', OLD.name,
            'email', OLD.email,
            'role', OLD.role
        )
    );
END;//
DELIMITER ;

-- Trigger para log de criação de chamado
DELIMITER //
CREATE TRIGGER ticket_create_log AFTER INSERT ON tickets
FOR EACH ROW
BEGIN
    DECLARE is_client_created BOOLEAN;
    
    SELECT role = 'client' INTO is_client_created 
    FROM users 
    WHERE id = NEW.client_id;
    
    INSERT INTO event_logs (
        event_type, 
        entity_id, 
        entity_type, 
        performed_by_user_id, 
        is_client_action,
        details
    ) VALUES (
        'ticket_created', 
        NEW.id, 
        'ticket', 
        NEW.client_id, 
        is_client_created,
        JSON_OBJECT(
            'description', NEW.description,
            'status', NEW.status,
            'service_id', NEW.service_id,
            'priority', NEW.priority
        )
    );
END;//
DELIMITER ;

-- Trigger para log de atualização de chamado
DELIMITER //
CREATE TRIGGER ticket_update_log AFTER UPDATE ON tickets
FOR EACH ROW
BEGIN
    INSERT INTO event_logs (
        event_type, 
        entity_id, 
        entity_type, 
        performed_by_user_id,
        is_client_action,
        details
    ) VALUES (
        'ticket_updated', 
        NEW.id, 
        'ticket', 
        @current_user_id,  
        FALSE,
        JSON_OBJECT(
            'old_description', OLD.description, 
            'new_description', NEW.description, 
            'old_status', OLD.status,
            'new_status', NEW.status,
            'old_service_id', OLD.service_id,
            'new_service_id', NEW.service_id,
            'old_priority', OLD.priority,
            'new_priority', NEW.priority
        )
    );
END;//
DELIMITER ;

-- Trigger para log de criação de equipamento
DELIMITER //
CREATE TRIGGER equipment_create_log AFTER INSERT ON equipment
FOR EACH ROW
BEGIN
    DECLARE is_client_created BOOLEAN;
    
    SELECT role = 'client' INTO is_client_created 
    FROM users 
    WHERE id = NEW.user_id;
    
    INSERT INTO event_logs (
        event_type, 
        entity_id, 
        entity_type, 
        performed_by_user_id, 
        is_client_action,
        details
    ) VALUES (
        'equipment_created', 
        NEW.id, 
        'equipment', 
        NEW.user_id, 
        is_client_created,
        JSON_OBJECT(
            'type', NEW.type,
            'equipment_code', NEW.equipment_code,
            'user_id', NEW.user_id
        )
    );
END;//
DELIMITER ;

-- Trigger para log de atualização de equipamento
DELIMITER //
CREATE TRIGGER equipment_update_log AFTER UPDATE ON equipment
FOR EACH ROW
BEGIN
    INSERT INTO event_logs (
        event_type, 
        entity_id, 
        entity_type, 
        performed_by_user_id,
        is_client_action,
        details
    ) VALUES (
        'equipment_updated', 
        NEW.id, 
        'equipment', 
        @current_user_id,  
        FALSE,
        JSON_OBJECT(
            'old_type', OLD.type, 
            'new_type', NEW.type,
            'old_equipment_code', OLD.equipment_code, 
            'new_equipment_code', NEW.equipment_code,
            'old_user_id', OLD.user_id,
            'new_user_id', NEW.user_id
        )
    );
END;//
DELIMITER ;

-- Trigger para log de deleção de equipamento
DELIMITER //
CREATE TRIGGER equipment_delete_log BEFORE DELETE ON equipment
FOR EACH ROW
BEGIN
    INSERT INTO event_logs (
        event_type, 
        entity_id, 
        entity_type, 
        performed_by_user_id,
        is_client_action,
        details
    ) VALUES (
        'equipment_deleted', 
        OLD.id, 
        'equipment', 
        @current_user_id,  
        FALSE,
        JSON_OBJECT(
            'type', OLD.type,
            'equipment_code', OLD.equipment_code,
            'user_id', OLD.user_id
        )
    );
END;//
DELIMITER ;