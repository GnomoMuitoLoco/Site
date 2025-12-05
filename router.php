<?php
/**
 * Router para o Servidor PHP Embutido do PHP 5.4+
 * Use: php -S localhost:8000 router.php
 */

$requested_file = __DIR__ . $_SERVER['REQUEST_URI'];
$requested_file = str_replace('\\', '/', $requested_file); // Normaliza para Windows

// Se for um arquivo físico existente (CSS, JS, imagens, etc)
if (file_exists($requested_file) && is_file($requested_file)) {
    return false;
}

// Se for uma pasta, procura por index.php ou index.html
if (is_dir($requested_file)) {
    $index_php = $requested_file . '/index.php';
    $index_html = $requested_file . '/index.html';
    
    if (file_exists($index_php)) {
        $_SERVER['SCRIPT_FILENAME'] = $index_php;
        include $index_php;
        return true;
    } elseif (file_exists($index_html)) {
        return false;
    }
}

// Se for requisição para arquivo .php existente
$uri = $_SERVER['REQUEST_URI'];
if (strpos($uri, '.php') !== false) {
    $file_path = __DIR__ . $uri;
    if (file_exists($file_path)) {
        $_SERVER['SCRIPT_FILENAME'] = $file_path;
        include $file_path;
        return true;
    }
}

// Se não tiver extensão, tenta adicionar .php
if (strpos($uri, '.') === false || (strpos($uri, '.') > strrpos($uri, '/'))) {
    $file_path = __DIR__ . $uri;
    if (!pathinfo($file_path, PATHINFO_EXTENSION) && file_exists($file_path . '.php')) {
        $_SERVER['SCRIPT_FILENAME'] = $file_path . '.php';
        include $file_path . '.php';
        return true;
    }
}

// Arquivo não encontrado
http_response_code(404);
echo "404 - Arquivo não encontrado: " . htmlspecialchars($_SERVER['REQUEST_URI']);
return false;
