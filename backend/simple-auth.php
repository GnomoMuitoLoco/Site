<?php
/**
 * Sistema de Autenticação Simples
 * Sem banco de dados, apenas para uso pessoal
 */

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Credenciais hardcoded
define('ADMIN_USER', 'GnomoMuitoLouco');
define('ADMIN_PASS', 'Brasil2010!');

/**
 * Verificar se o usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['user_logged']) && $_SESSION['user_logged'] === true;
}

/**
 * Fazer login
 */
function doLogin($username, $password) {
    if ($username === ADMIN_USER && $password === ADMIN_PASS) {
        $_SESSION['user_logged'] = true;
        $_SESSION['username'] = ADMIN_USER;
        $_SESSION['login_time'] = time();
        return true;
    }
    return false;
}

/**
 * Fazer logout
 */
function doLogout() {
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}

/**
 * Gerar CSRF Token (simplificado)
 */
function generateCSRFToken() {
    // Não precisamos de CSRF token para um usuário só
    return 'simple';
}

/**
 * Verificar CSRF Token (simplificado)
 */
function verifyCSRFToken($token) {
    // Qualquer token válido passa
    return !empty($token);
}
?>
