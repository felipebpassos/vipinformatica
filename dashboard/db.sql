DROP DATABASE vipinformatica;

select * from services;

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

-- Serviços
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service ENUM(
        'Manutenção e conserto de equipamentos', 
        'Formatação e instalação de programas', 
        'Desenvolvimento de sites e sistemas', 
        'Consultoria em TI'
    ) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO services (service) VALUES
  ('Manutenção e conserto de equipamentos'),
  ('Formatação e instalação de programas'),
  ('Desenvolvimento de sites e sistemas'),
  ('Consultoria em TI');

-- Equipamentos
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

-- Chamados/Tickets
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

-- Relação Tickets ↔ Equipamentos
CREATE TABLE ticket_equipment (
    ticket_id INT NOT NULL,
    equipment_id INT NOT NULL,
    PRIMARY KEY (ticket_id, equipment_id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (equipment_id) REFERENCES equipment(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Histórico de mensagens no ticket
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
        'ticket_deleted',
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

DELIMITER //

-- Triggers de usuário
CREATE TRIGGER user_create_log
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    DECLARE actor_is_client BOOLEAN DEFAULT FALSE;
    SELECT role = 'client' INTO actor_is_client FROM users WHERE id = @current_user_id;
    INSERT INTO event_logs(event_type, entity_id, entity_type, performed_by_user_id, is_client_action, details)
    VALUES(
      'user_created', NEW.id, 'user', @current_user_id, actor_is_client,
      JSON_OBJECT('name', NEW.name, 'email', NEW.email, 'role', NEW.role)
    );
END;
//

CREATE TRIGGER user_update_log
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    DECLARE actor_is_client BOOLEAN DEFAULT FALSE;
    SELECT role = 'client' INTO actor_is_client FROM users WHERE id = @current_user_id;
    INSERT INTO event_logs(event_type, entity_id, entity_type, performed_by_user_id, is_client_action, details)
    VALUES(
      'user_updated', NEW.id, 'user', @current_user_id, actor_is_client,
      JSON_OBJECT(
        'old_name', OLD.name, 'new_name', NEW.name,
        'old_email', OLD.email, 'new_email', NEW.email,
        'old_role', OLD.role, 'new_role', NEW.role
      )
    );
END;
//

CREATE TRIGGER user_delete_log
BEFORE DELETE ON users
FOR EACH ROW
BEGIN
    DECLARE actor_is_client BOOLEAN DEFAULT FALSE;
    SELECT role = 'client' INTO actor_is_client FROM users WHERE id = @current_user_id;
    INSERT INTO event_logs(event_type, entity_id, entity_type, performed_by_user_id, is_client_action, details)
    VALUES(
      'user_deleted', OLD.id, 'user', @current_user_id, actor_is_client,
      JSON_OBJECT('name', OLD.name, 'email', OLD.email, 'role', OLD.role)
    );
END;
//

-- Triggers de ticket
CREATE TRIGGER ticket_create_log
AFTER INSERT ON tickets
FOR EACH ROW
BEGIN
    DECLARE actor_is_client BOOLEAN DEFAULT FALSE;
    SELECT role = 'client' INTO actor_is_client FROM users WHERE id = @current_user_id;
    INSERT INTO event_logs(event_type, entity_id, entity_type, performed_by_user_id, is_client_action, details)
    VALUES(
      'ticket_created', NEW.id, 'ticket', @current_user_id, actor_is_client,
      JSON_OBJECT(
        'client_id', NEW.client_id,
        'description', NEW.description,
        'status', NEW.status,
        'service_id', NEW.service_id,
        'priority', NEW.priority
      )
    );
END;
//

CREATE TRIGGER ticket_update_log
AFTER UPDATE ON tickets
FOR EACH ROW
BEGIN
    DECLARE actor_is_client BOOLEAN DEFAULT FALSE;
    SELECT role = 'client' INTO actor_is_client FROM users WHERE id = @current_user_id;

    IF OLD.status <> NEW.status AND NEW.status = 'closed' THEN
        INSERT INTO event_logs(event_type, entity_id, entity_type, performed_by_user_id, is_client_action, details)
        VALUES(
          'ticket_closed', NEW.id, 'ticket', @current_user_id, actor_is_client,
          JSON_OBJECT('old_status', OLD.status, 'new_status', NEW.status, 'closed_at', NEW.closed_at)
        );
    ELSE
        INSERT INTO event_logs(event_type, entity_id, entity_type, performed_by_user_id, is_client_action, details)
        VALUES(
          'ticket_updated', NEW.id, 'ticket', @current_user_id, actor_is_client,
          JSON_OBJECT(
            'old_description', OLD.description, 'new_description', NEW.description,
            'old_status', OLD.status,           'new_status', NEW.status,
            'old_service_id', OLD.service_id,   'new_service_id', NEW.service_id,
            'old_priority', OLD.priority,       'new_priority', NEW.priority
          )
        );
    END IF;
END;
//

CREATE TRIGGER ticket_delete_log
BEFORE DELETE ON tickets
FOR EACH ROW
BEGIN
    DECLARE actor_is_client BOOLEAN DEFAULT FALSE;
    SELECT role = 'client' INTO actor_is_client FROM users WHERE id = @current_user_id;
    INSERT INTO event_logs(event_type, entity_id, entity_type, performed_by_user_id, is_client_action, details)
    VALUES(
      'ticket_deleted', OLD.id, 'ticket', @current_user_id, actor_is_client,
      JSON_OBJECT(
        'client_id', OLD.client_id,
        'description', OLD.description,
        'status', OLD.status,
        'service_id', OLD.service_id,
        'priority', OLD.priority
      )
    );
END;
//

-- Triggers de equipamento
CREATE TRIGGER equipment_create_log
AFTER INSERT ON equipment
FOR EACH ROW
BEGIN
    DECLARE actor_is_client BOOLEAN DEFAULT FALSE;
    SELECT role = 'client' INTO actor_is_client FROM users WHERE id = @current_user_id;
    INSERT INTO event_logs(event_type, entity_id, entity_type, performed_by_user_id, is_client_action, details)
    VALUES(
      'equipment_created', NEW.id, 'equipment', @current_user_id, actor_is_client,
      JSON_OBJECT(
        'type', NEW.type,
        'equipment_code', NEW.equipment_code,
        'user_id', NEW.user_id
      )
    );
END;
//

CREATE TRIGGER equipment_update_log
AFTER UPDATE ON equipment
FOR EACH ROW
BEGIN
    DECLARE actor_is_client BOOLEAN DEFAULT FALSE;
    SELECT role = 'client' INTO actor_is_client FROM users WHERE id = @current_user_id;
    INSERT INTO event_logs(event_type, entity_id, entity_type, performed_by_user_id, is_client_action, details)
    VALUES(
      'equipment_updated', NEW.id, 'equipment', @current_user_id, actor_is_client,
      JSON_OBJECT(
        'old_type', OLD.type,
        'new_type', NEW.type,
        'old_equipment_code', OLD.equipment_code,
        'new_equipment_code', NEW.equipment_code
      )
    );
END;
//

CREATE TRIGGER equipment_delete_log
BEFORE DELETE ON equipment
FOR EACH ROW
BEGIN
    DECLARE actor_is_client BOOLEAN DEFAULT FALSE;
    SELECT role = 'client' INTO actor_is_client FROM users WHERE id = @current_user_id;
    INSERT INTO event_logs(event_type, entity_id, entity_type, performed_by_user_id, is_client_action, details)
    VALUES(
      'equipment_deleted', OLD.id, 'equipment', @current_user_id, actor_is_client,
      JSON_OBJECT(
        'type', OLD.type,
        'equipment_code', OLD.equipment_code,
        'user_id', OLD.user_id
      )
    );
END;
//

DELIMITER ;