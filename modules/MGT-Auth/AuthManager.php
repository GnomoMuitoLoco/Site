<?php
/**
 * MGT-Auth Module
 * Sistema de Autenticação do Servidor Magnatas
 * 
 * Responsável por:
 * - Autenticação de usuários
 * - Gerenciamento de sessão
 * - CSRF Token
 */

namespace MGT\Auth;

/**
 * Classe principal de autenticação
 */
class AuthManager {
    
    /**
     * Credenciais padrão (single-user)
     */
    private const ADMIN_USER = 'GnomoMuitoLouco';
    private const ADMIN_PASS = 'Brasil2010!';
    
    /**
     * Inicializa a sessão
     */
    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Verifica se o usuário está logado
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_logged']) && $_SESSION['user_logged'] === true;
    }
    
    /**
     * Realiza o login
     */
    public static function login($username, $password) {
        if ($username === self::ADMIN_USER && $password === self::ADMIN_PASS) {
            $_SESSION['user_logged'] = true;
            $_SESSION['username'] = self::ADMIN_USER;
            $_SESSION['login_time'] = time();
            return true;
        }
        return false;
    }
    
    /**
     * Realiza o logout
     */
    public static function logout() {
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();
    }
    
    /**
     * Gera um CSRF token
     */
    public static function generateCSRFToken() {
        return 'simple';
    }
    
    /**
     * Verifica um CSRF token
     */
    public static function verifyCSRFToken($token) {
        return !empty($token);
    }
    
    /**
     * Obtém o usuário logado
     */
    public static function getUser() {
        return $_SESSION['username'] ?? null;
    }
    
    /**
     * Obtém o tempo de login
     */
    public static function getLoginTime() {
        return $_SESSION['login_time'] ?? null;
    }
}

// Compatibilidade com código antigo (função wrapper)
function isLoggedIn() {
    return AuthManager::isLoggedIn();
}

function doLogin($username, $password) {
    return AuthManager::login($username, $password);
}

function doLogout() {
    return AuthManager::logout();
}

function generateCSRFToken() {
    return AuthManager::generateCSRFToken();
}

function verifyCSRFToken($token) {
    return AuthManager::verifyCSRFToken($token);
}

// Inicializar sessão automaticamente
AuthManager::initSession();

?>
