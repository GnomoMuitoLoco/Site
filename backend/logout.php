<?php
/**
 * Logout
 * Encerra a sessão do usuário
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
load_module('MGT-Auth', 'AuthManager.php');

use MGT\Auth\AuthManager;

// Faz logout
AuthManager::logout();

// Redireciona para login
header('Location: /dashboard/login.php');
exit;

?>
