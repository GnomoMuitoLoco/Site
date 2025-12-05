<?php
/**
 * Middleware de Autenticação
 * Verifica se usuário está logado
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
load_module('MGT-Auth', 'AuthManager.php');

use MGT\Auth\AuthManager;

// Inicializa sessão
AuthManager::initSession();

// Verifica se está logado
if (!AuthManager::isLoggedIn()) {
    header('Location: /dashboard/login.php');
    exit;
}

?>
