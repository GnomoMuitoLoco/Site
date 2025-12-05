<?php
/**
 * MGT-Auth Middleware
 * Verifica autenticação e redireciona para login se necessário
 */

require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
load_module('MGT-Auth', 'AuthManager.php');

use MGT\Auth\AuthManager;

if (!AuthManager::isLoggedIn()) {
    header('Location: /dashboard/login.php');
    exit;
}

?>
