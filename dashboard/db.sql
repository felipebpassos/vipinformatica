DROP DATABASE vipinformatica;

-- Criação do banco de dados
CREATE DATABASE vipinformatica
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

use vipinformatica;

-- Usuários (clientes e administradores)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('owner', 'admin', 'client') DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Serviços/Categorias
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chamados/Tickets
CREATE TABLE tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    client_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
    closed_at DATETIME,
    FOREIGN KEY (client_id) REFERENCES users(id)
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

-- Tabela de Log de Eventos
CREATE TABLE event_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_type ENUM(
        'ticket_created', 
        'ticket_updated', 
        'ticket_closed', 
        'user_created', 
        'user_updated', 
        'user_deleted'
    ) NOT NULL,
    entity_id INT NOT NULL,
    entity_type ENUM('ticket', 'user') NOT NULL,
    performed_by_user_id INT NOT NULL,
    is_client_action BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    details JSON,
    FOREIGN KEY (performed_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
            'title', NEW.title,
            'description', NEW.description,
            'status', NEW.status
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
            'old_title', OLD.title, 
            'new_title', NEW.title,
            'old_description', OLD.description, 
            'new_description', NEW.description, 
            'old_status', OLD.status,
            'new_status', NEW.status
        )
    );
END;//
DELIMITER ;

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