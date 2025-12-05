<?php
/**
 * Router para o Servidor PHP Embutido
 * Arquivo: index.php (raiz do projeto)
 * Este arquivo redireciona requisições para os arquivos corretos
 */

// Pegar o caminho da requisição
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Se a requisição for para arquivo estático, deixa o PHP servir normalmente
if (preg_match('/\.(?:css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$/', $uri)) {
    return false;
}

// Se for raiz, redireciona para index.html
if ($uri === '/' || $uri === '') {
    $_GET['page'] = 'index';
}

// Remover a barra inicial se existir
$uri = ltrim($uri, '/');

// Se a requisição for para um arquivo HTML na raiz, servir normalmente
if (file_exists(__DIR__ . '/' . $uri) && pathinfo($uri, PATHINFO_EXTENSION) === 'html') {
    return false;
}

// Se for uma pasta (ex: /dashboard/), procura por index.php ou index.html
if (is_dir(__DIR__ . '/' . rtrim($uri, '/'))) {
    $index_php = __DIR__ . '/' . rtrim($uri, '/') . '/index.php';
    $index_html = __DIR__ . '/' . rtrim($uri, '/') . '/index.html';
    
    if (file_exists($index_php)) {
        require $index_php;
        return true;
    } elseif (file_exists($index_html)) {
        return false;
    }
}

// Se for arquivo PHP direto (ex: /dashboard/login.php)
if (pathinfo($uri, PATHINFO_EXTENSION) === 'php') {
    $file_path = __DIR__ . '/' . $uri;
    if (file_exists($file_path)) {
        require $file_path;
        return true;
    }
}

// Tentar adicionar .php se não tiver extensão
if (pathinfo($uri, PATHINFO_EXTENSION) === '') {
    $file_path = __DIR__ . '/' . $uri . '.php';
    if (file_exists($file_path)) {
        require $file_path;
        return true;
    }
}

// Se nenhum arquivo encontrado, retorna 404
http_response_code(404);
echo "Not Found: " . htmlspecialchars($uri);
echo '<h1>404 - Página não encontrada</h1>';
return false;
?>
