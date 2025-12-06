<?php
/**
 * API para obter dados do Dashboard
 * Arquivo: backend/api_dashboard.php
 */

require_once 'config.php';

// Verificar autenticação
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

// Verificar token CSRF em requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['csrf_token']) || !verifyCSRFToken($input['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Token inválido']);
        exit;
    }
}

// Definir header JSON
header('Content-Type: application/json');

// Ação solicitada
$action = $_GET['action'] ?? '';

$conn = connectDatabase();

switch ($action) {
    case 'get_stats':
        getStats($conn);
        break;
    
    case 'get_users':
        getUsers($conn);
        break;
    
    case 'get_logs':
        getLogs($conn);
        break;
    
    case 'get_system_info':
        getSystemInfo();
        break;

    case 'get_store_stats':
        getStoreStats($conn);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Ação inválida']);
        break;
}

$conn->close();

/**
 * Obter estatísticas do dashboard
 */
function getStats($conn) {
    $data = [];

    // Usuários ativos
    $result = $conn->query('SELECT COUNT(*) as count FROM users WHERE status = "active"');
    $data['active_users'] = $result->fetch_assoc()['count'];

    // Total de logins
    $result = $conn->query('SELECT COUNT(*) as count FROM login_attempts WHERE success = 1');
    $data['total_logins'] = $result->fetch_assoc()['count'];

    // Sessões ativas
    $result = $conn->query('SELECT COUNT(*) as count FROM sessions WHERE expires_at > NOW()');
    $data['active_sessions'] = $result->fetch_assoc()['count'];

    // Status do sistema
    $data['system_status'] = 'Online';

    echo json_encode($data);
}

/**
 * Obter lista de usuários
 */
function getUsers($conn) {
    $result = $conn->query('SELECT id, username, email, role, status, created_at, last_login FROM users ORDER BY created_at DESC LIMIT 10');
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode($users);
}

/**
 * Obter logs de atividade
 */
function getLogs($conn) {
    $result = $conn->query('
        SELECT 
            l.id, 
            l.user_id, 
            l.action, 
            l.details, 
            l.ip_address, 
            l.created_at,
            u.username
        FROM activity_logs l
        LEFT JOIN users u ON l.user_id = u.id
        ORDER BY l.created_at DESC
        LIMIT 20
    ');
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }

    echo json_encode($logs);
}

/**
 * Obter informações do sistema
 */
function getSystemInfo() {
    $info = [
        'php_version' => phpversion(),
        'server_time' => date('H:i:s'),
        'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'localhost',
        'mysql_version' => 'N/A'
    ];

    echo json_encode($info);
}

/**
 * Obter estatísticas de vendas
 */
function getStoreStats($conn) {
    // Considera apenas pagamentos aprovados
    $sql = "
        SELECT 
            COALESCE(SUM(valor_total), 0) AS total_all,
            COALESCE(SUM(CASE WHEN YEAR(criado_em) = YEAR(NOW()) THEN valor_total END), 0) AS total_year,
            COALESCE(SUM(CASE WHEN YEAR(criado_em) = YEAR(NOW()) AND MONTH(criado_em) = MONTH(NOW()) THEN valor_total END), 0) AS total_month,
            COALESCE(SUM(CASE WHEN DATE(criado_em) = CURDATE() THEN valor_total END), 0) AS total_today
        FROM mgt_transacoes
        WHERE status_pagamento = 'aprovado'
    ";

    $result = $conn->query($sql);
    $row = $result ? $result->fetch_assoc() : [
        'total_all' => 0,
        'total_year' => 0,
        'total_month' => 0,
        'total_today' => 0,
    ];

    echo json_encode([
        'total_all' => (float)$row['total_all'],
        'total_year' => (float)$row['total_year'],
        'total_month' => (float)$row['total_month'],
        'total_today' => (float)$row['total_today'],
    ]);
}
?>
