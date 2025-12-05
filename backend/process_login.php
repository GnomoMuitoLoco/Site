<?php
/**
 * API - Process Login
 * Processa o formulário de login
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
load_module('MGT-Auth', 'AuthManager.php');

use MGT\Auth\AuthManager;

// Inicializa sessão
AuthManager::initSession();

// Verifica se é um POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /dashboard/login.php');
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$csrf_token = $_POST['csrf_token'] ?? '';

// Valida CSRF token
if (!AuthManager::verifyCSRFToken($csrf_token)) {
    $_SESSION['login_error'] = 'Token de segurança inválido.';
    header('Location: /dashboard/login.php');
    exit;
}

// Tentar fazer login
if (doLogin($username, $password)) {
    // Login bem-sucedido
    header('Location: ../dashboard/');
    exit;
} else {
    // Login falhou
    $_SESSION['login_error'] = 'Usuário ou senha incorretos.';
    header('Location: ../dashboard/login.php');
    exit;
}
if (doLogin($username, $password)) {
    // Login bem-sucedido
    header('Location: ../dashboard/dashboard.php');
    exit;
} else {
    // Login falhou
    $_SESSION['login_error'] = 'Usuário ou senha incorretos.';
    header('Location: ../dashboard/login.php');
    exit;
}
?>
