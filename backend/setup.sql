-- ========================================
-- Banco de Dados: Servidor Magnatas
-- Dashboard Administrativo
-- ========================================

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS servidor_magnatas;
USE servidor_magnatas;

-- ========================================
-- Tabela: Usuários
-- ========================================

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('master', 'admin', 'moderator') DEFAULT 'admin',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    is_master BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login DATETIME,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Tabela: Sessões
-- ========================================

CREATE TABLE IF NOT EXISTS sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Tabela: Logs de Atividade
-- ========================================

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Tabela: Tentativas de Login
-- ========================================

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50),
    ip_address VARCHAR(45),
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    INDEX idx_username (username),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Tabela: Configurações do Sistema
-- ========================================

CREATE TABLE IF NOT EXISTS system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value LONGTEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Inserir Usuário Master Padrão
-- ========================================

INSERT INTO users (username, email, password_hash, role, is_master, status) 
VALUES (
    'admin', 
    'admin@servidormagnatas.com.br',
    '$2y$12$pKxXZm5DYXyQcR7vJ2nU2eZb.T.nWxQxP7qKjPvL9mN8oR3sK5Q4q',
    'master',
    TRUE,
    'active'
) ON DUPLICATE KEY UPDATE password_hash=password_hash;

-- ========================================
-- Inserir Configurações Padrão
-- ========================================

INSERT INTO system_settings (setting_key, setting_value, description)
VALUES 
    ('site_name', 'Servidor Magnatas', 'Nome do servidor'),
    ('site_url', 'http://localhost/', 'URL do site'),
    ('max_login_attempts', '5', 'Máximo de tentativas de login'),
    ('session_lifetime', '3600', 'Tempo de vida da sessão em segundos')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

-- ========================================
-- Verificar estrutura criada
-- ========================================

-- SELECT * FROM users;
-- SELECT * FROM sessions;
-- SELECT * FROM activity_logs;
-- SELECT * FROM login_attempts;
