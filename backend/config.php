<?php
/**
 * Configuração do Servidor Magnatas - Dashboard Administrativo
 * Este arquivo contém as configurações de banco de dados e credenciais master
 */

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// CONFIGURAÇÃO DO BANCO DE DADOS
// ========================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'servidor_magnatas');
define('DB_PORT', 3306);

// ========================================
// CONFIGURAÇÃO DE SESSÃO
// ========================================

define('SESSION_LIFETIME', 3600); // 1 hora em segundos
define('SESSION_PATH', '/');
define('SESSION_DOMAIN', $_SERVER['HTTP_HOST']);
define('SESSION_SECURE', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'); // HTTPS only em produção
define('SESSION_HTTPONLY', true); // Impede acesso via JavaScript

// ========================================
// CONFIGURAÇÃO DE SENHA
// ========================================

define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_COST', 12); // Custo computacional

// ========================================
// CREDENCIAIS MASTER (ADMINISTRADOR PRINCIPAL)
// ========================================
// IMPORTANTE: Altere a senha padrão imediatamente após o primeiro acesso!
// Para gerar um hash: php -r "echo password_hash('sua_senha_aqui', PASSWORD_BCRYPT, ['cost' => 12]);"

define('MASTER_USERNAME', 'GnomoMuitoLouco');
define('MASTER_PASSWORD_HASH', '$2y$12$p9.YG5RIX7DwM6Sv3cZFdu7dqCZQlgANqPnHJCeEh.r.LjZP8pcwy'); // senha: Brasil2010!
define('MASTER_EMAIL', 'admin@servidormagnatas.com.br');

// ========================================
// CONFIGURAÇÃO DE SEGURANÇA
// ========================================

define('SITE_URL', 'http://localhost/'); // Mude para seu domínio em produção
define('ADMIN_PATH', '/dashboard');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutos em segundos

// ========================================
// TIMEZONE
// ========================================

date_default_timezone_set('America/Sao_Paulo');

// ========================================
// FUNÇÃO DE CONEXÃO COM BANCO DE DADOS
// ========================================

function connectDatabase() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        
        // Verificar conexão
        if ($conn->connect_error) {
            throw new Exception('Erro de conexão: ' . $conn->connect_error);
        }
        
        // Configurar charset para UTF-8
        $conn->set_charset('utf8mb4');
        
        return $conn;
    } catch (Exception $e) {
        die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
    }
}

// ========================================
// FUNÇÃO DE HASH DE SENHA
// ========================================

function hashPassword($password) {
    return password_hash($password, PASSWORD_ALGO, ['cost' => PASSWORD_COST]);
}

// ========================================
// FUNÇÃO DE VERIFICAÇÃO DE SENHA
// ========================================

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// ========================================
// INICIALIZAR SESSÃO SEGURA
// ========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => SESSION_LIFETIME,
        'cookie_path' => SESSION_PATH,
        'cookie_secure' => SESSION_SECURE,
        'cookie_httponly' => SESSION_HTTPONLY,
        'cookie_samesite' => 'Strict'
    ]);
}

// ========================================
// VERIFICAÇÃO DE AUTENTICAÇÃO
// ========================================

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'] ?? 'admin',
            'login_time' => $_SESSION['login_time'] ?? null
        ];
    }
    return null;
}

// ========================================
// FUNÇÃO DE LOG DE ATIVIDADES
// ========================================

function logActivity($user_id, $action, $details = '') {
    $conn = connectDatabase();
    $ip = $_SERVER['REMOTE_ADDR'];
    $timestamp = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare('INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('issss', $user_id, $action, $details, $ip, $timestamp);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// ========================================
// FUNÇÃO DE SEGURANÇA - CSRF Token
// ========================================

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ========================================
// FUNÇÃO DE SANITIZAÇÃO
// ========================================

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// ========================================
// TRATAMENTO DE ERROS
// ========================================

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Log de erros
    error_log("[" . date('Y-m-d H:i:s') . "] Erro ($errno): $errstr em $errfile:$errline");
    return true;
});
?>
