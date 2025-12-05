<?php
/**
 * Setup Inicial do Banco de Dados
 * Execute este arquivo UMA VEZ: http://localhost:8000/setup-database.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Setup - Servidor Magnatas</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #e8f5e9; border: 1px solid #4caf50; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border: 1px solid #f44336; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #e3f2fd; border: 1px solid #2196f3; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 Setup - Servidor Magnatas</h1>";

try {
    // Conectar ao banco de dados
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD);
    
    if ($conn->connect_error) {
        throw new Exception('Erro ao conectar: ' . $conn->connect_error);
    }
    
    echo "<div class='info'>✓ Conectado ao MySQL</div>";
    
    // Se ?reset=true, deletar banco anterior
    if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
        $sql = "DROP DATABASE IF EXISTS " . DB_NAME;
        if ($conn->query($sql)) {
            echo "<div class='success'>✓ Banco anterior deletado</div>";
        }
    }
    
    // Criar banco de dados se não existir
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($sql)) {
        echo "<div class='success'>✓ Banco de dados '" . DB_NAME . "' pronto</div>";
    }
    
    // Selecionar banco
    $conn->select_db(DB_NAME);
    
    // Criar tabelas
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'user',
            status VARCHAR(20) DEFAULT 'active',
            is_master BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL
        )",
        'sessions' => "CREATE TABLE IF NOT EXISTS sessions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            session_token VARCHAR(255) UNIQUE NOT NULL,
            ip_address VARCHAR(45),
            user_agent VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        'login_attempts' => "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50),
            ip_address VARCHAR(45),
            success BOOLEAN DEFAULT FALSE,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'activity_logs' => "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            action VARCHAR(100),
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )",
        'system_settings' => "CREATE TABLE IF NOT EXISTS system_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value LONGTEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($tables as $name => $sql) {
        if ($conn->query($sql)) {
            echo "<div class='success'>✓ Tabela '$name' pronta</div>";
        } else {
            echo "<div class='error'>✗ Erro ao criar tabela '$name': " . $conn->error . "</div>";
        }
    }
    
    // Inserir usuário master se não existir
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $username = 'GnomoMuitoLouco';
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Usuário não existe, criar
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role, is_master, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssii', $user, $email, $hash, $role, $is_master, $status);
        
        $user = 'GnomoMuitoLouco';
        $email = 'admin@servidormagnatas.com.br';
        $hash = '$2y$12$p9.YG5RIX7DwM6Sv3cZFdu7dqCZQlgANqPnHJCeEh.r.LjZP8pcwy'; // Brasil2010!
        $role = 'admin';
        $is_master = 1;
        $status = 'active';
        
        if ($stmt->execute()) {
            echo "<div class='success'>✓ Usuário master 'admin' criado</div>";
        } else {
            echo "<div class='error'>✗ Erro ao criar usuário: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        echo "<div class='info'>ℹ Usuário 'admin' já existe</div>";
    }
    
    $conn->close();
    
    echo "<div class='success' style='margin-top: 20px; font-size: 16px;'>
        <h2>✓ Setup Completo!</h2>
        <p>Agora você pode fazer login com:</p>
        <p><strong>Usuário:</strong> admin</p>
        <p><strong>Senha:</strong> admin123</p>
        <p><a href='http://localhost:8000/dashboard/login.php' style='color: green; text-decoration: none; font-weight: bold;'>→ Ir para Login</a></p>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>Erro:</strong> " . $e->getMessage() . "</div>";
    echo "<p>Verifique se:</p>
    <ul>
        <li>MySQL está rodando (XAMPP Control Panel)</li>
        <li>O arquivo backend/config.php tem as credenciais corretas</li>
    </ul>";
}
?>
</body>
</html>
