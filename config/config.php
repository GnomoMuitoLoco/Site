<?php
/**
 * Configurações Globais do Projeto
 * 
 * Arquivo central de configuração para toda a aplicação
 * Define constantes, paths e configurações gerais
 */

// ===================================
// MODO DE AMBIENTE
// ===================================
define('ENVIRONMENT', getenv('APP_ENV') ?: 'development');
define('DEBUG_MODE', ENVIRONMENT === 'development');

// ===================================
// PATHS ABSOLUTOS
// ===================================
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('MODULES_PATH', ROOT_PATH . '/modules');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('CSS_PATH', PUBLIC_PATH . '/css');
define('JS_PATH', PUBLIC_PATH . '/js');
define('ASSETS_PATH', PUBLIC_PATH . '/assets');

// ===================================
// MÓDULOS DISPONÍVEIS
// ===================================
define('MODULES', [
    'MGT-Auth' => MODULES_PATH . '/MGT-Auth',
    'MGT-Dashboard' => MODULES_PATH . '/MGT-Dashboard',
    'MGT-Store' => MODULES_PATH . '/MGT-Store',
    'MGT-ServerStatus' => MODULES_PATH . '/MGT-ServerStatus',
    'MGT-API' => MODULES_PATH . '/MGT-API',
    'MGT-Utils' => MODULES_PATH . '/MGT-Utils',
]);

// ===================================
// CONFIGURAÇÕES DE APLICAÇÃO
// ===================================
define('APP_NAME', 'Servidor Magnatas');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8000');
define('APP_VERSION', '1.0.0');

// ===================================
// CONFIGURAÇÕES DE SESSÃO
// ===================================
define('SESSION_TIMEOUT', 3600); // 1 hora
define('SESSION_COOKIE_NAME', 'magnatas_session');

// ===================================
// CONFIGURAÇÕES DE SEGURANÇA
// ===================================
define('CSRF_TOKEN_LENGTH', 32);
define('HASH_ALGORITHM', 'sha256');

// ===================================
// AUTO-LOADER DE MODULES
// ===================================
/**
 * Carrega um arquivo de um módulo
 */
function load_module($module_name, $file) {
    $module_path = MODULES[$module_name] ?? null;
    if (!$module_path) {
        throw new Exception("Módulo '{$module_name}' não encontrado");
    }
    
    $file_path = $module_path . '/' . $file;
    if (!file_exists($file_path)) {
        throw new Exception("Arquivo '{$file}' não encontrado no módulo '{$module_name}'");
    }
    
    require_once $file_path;
}

/**
 * Carrega um arquivo de configuração
 */
function load_config($file) {
    $file_path = CONFIG_PATH . '/' . $file;
    if (!file_exists($file_path)) {
        throw new Exception("Arquivo de configuração '{$file}' não encontrado");
    }
    
    require_once $file_path;
}

// ===================================
// ERROR HANDLING
// ===================================
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
}

// ===================================
// TIMEZONE
// ===================================
date_default_timezone_set('America/Sao_Paulo');

?>
