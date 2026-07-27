CREATE DATABASE IF NOT EXISTS support_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE support_portal;
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS activity_logs; DROP TABLE IF EXISTS notifications; DROP TABLE IF EXISTS ticket_status_history;
DROP TABLE IF EXISTS ticket_attachments; DROP TABLE IF EXISTS ticket_conversations; DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS ticket_categories; DROP TABLE IF EXISTS api_tokens; DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS users; DROP TABLE IF EXISTS companies; DROP TABLE IF EXISTS settings;

CREATE TABLE companies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL, address TEXT DEFAULT NULL, website VARCHAR(255) DEFAULT NULL,
    logo VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id INT UNSIGNED DEFAULT NULL,
    first_name VARCHAR(80) NOT NULL, last_name VARCHAR(80) NOT NULL, email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL, role ENUM('super_admin','employee','client') NOT NULL DEFAULT 'client',
    avatar VARCHAR(255) DEFAULT NULL, phone VARCHAR(30) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1, email_verified TINYINT(1) NOT NULL DEFAULT 0,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_email (email), INDEX idx_role (role), INDEX idx_company (company_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, email VARCHAR(150) NOT NULL, token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL, used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email), INDEX idx_token (token(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE api_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL, name VARCHAR(100) DEFAULT 'default',
    last_used TIMESTAMP NULL DEFAULT NULL, expires_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_token (token(64)), INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL,
    color VARCHAR(20) NOT NULL DEFAULT '#6c757d', description TEXT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ticket_number VARCHAR(20) NOT NULL,
    subject VARCHAR(255) NOT NULL, description TEXT NOT NULL,
    status ENUM('open','assigned','in_progress','waiting_for_client','resolved','closed') NOT NULL DEFAULT 'open',
    priority ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    category_id INT UNSIGNED DEFAULT NULL, company_id INT UNSIGNED DEFAULT NULL,
    created_by INT UNSIGNED NOT NULL, assigned_to INT UNSIGNED DEFAULT NULL,
    due_date DATE DEFAULT NULL, resolved_at TIMESTAMP NULL DEFAULT NULL, closed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_ticket_number (ticket_number), INDEX idx_status (status), INDEX idx_priority (priority),
    INDEX idx_created_by (created_by), INDEX idx_assigned_to (assigned_to), INDEX idx_created_at (created_at),
    FOREIGN KEY (category_id) REFERENCES ticket_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (company_id)  REFERENCES companies(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_conversations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ticket_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL, is_internal TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ticket (ticket_id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ticket_id INT UNSIGNED NOT NULL,
    conversation_id INT UNSIGNED DEFAULT NULL, user_id INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL, stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL, file_size INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ticket (ticket_id),
    FOREIGN KEY (ticket_id)       REFERENCES tickets(id)              ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES ticket_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)         REFERENCES users(id)                ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_status_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ticket_id INT UNSIGNED NOT NULL, changed_by INT UNSIGNED NOT NULL,
    old_status VARCHAR(30) DEFAULT NULL, new_status VARCHAR(30) NOT NULL, note TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ticket (ticket_id),
    FOREIGN KEY (ticket_id)  REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL,
    type VARCHAR(80) NOT NULL, title VARCHAR(255) NOT NULL, message TEXT NOT NULL,
    data JSON DEFAULT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_read (user_id, is_read),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(100) NOT NULL, entity_type VARCHAR(80) DEFAULT NULL, entity_id INT UNSIGNED DEFAULT NULL,
    description TEXT DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id), INDEX idx_user (user_id), INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, key_name VARCHAR(100) NOT NULL,
    value TEXT DEFAULT NULL, group_name VARCHAR(80) NOT NULL DEFAULT 'general',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_key (key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;

-- Default super admin (password: Admin@12345)
INSERT INTO users (first_name,last_name,email,password,role,is_active,email_verified) VALUES
('Super','Admin','admin@support-portal.com','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','super_admin',1,1);

-- Default categories
INSERT INTO ticket_categories (name,color,description) VALUES
('Technical Support','#0d6efd','Software, hardware, and technical issues'),
('Billing','#198754','Invoices, payments, and subscriptions'),
('General Inquiry','#6c757d','General questions and information'),
('Feature Request','#6610f2','Suggestions for new features'),
('Bug Report','#dc3545','Reporting software bugs'),
('Account','#fd7e14','Account access and profile issues');

-- Default settings
INSERT INTO settings (key_name,value,group_name) VALUES
('site_name','Support Portal','general'),('support_email','support@example.com','general'),
('tickets_per_page','20','tickets'),('auto_close_days','7','tickets'),
('email_notifications','1','mail'),('notify_new_ticket','1','mail'),
('notify_ticket_assigned','1','mail'),('notify_ticket_resolved','1','mail');
