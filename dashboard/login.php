<?php
/**
 * Dashboard - Página de Login
 * Sistema de autenticação do Servidor Magnatas
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
load_module('MGT-Auth', 'AuthManager.php');

use MGT\Auth\AuthManager;

// Inicializa sessão
AuthManager::initSession();

// Se já está logado, redireciona para dashboard
if (AuthManager::isLoggedIn()) {
    header('Location: /dashboard/');
    exit;
}

// Processa formulário de login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!AuthManager::verifyCSRFToken($csrf_token)) {
        $error = 'Token de segurança inválido.';
    } elseif (AuthManager::login($username, $password)) {
        header('Location: /dashboard/');
        exit;
    } else {
        $error = 'Usuário ou senha incorretos.';
    }
}

$csrf_token = AuthManager::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Servidor Magnatas | Painel Administrativo</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-box">
            <!-- Logo/Título -->
            <div class="login-header">
                <h1>Servidor <span>Magnatas</span></h1>
                <p>Painel Administrativo</p>
            </div>

            <!-- Mensagens de Erro -->
            <?php if ($error): ?>
                <div class="form-message show error" id="login-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Formulário de Login -->
            <form method="POST" action="../backend/process_login.php" class="login-form" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <!-- Campo de Usuário -->
                <div class="form-group">
                    <label for="username">Usuário</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        placeholder="Digite seu usuário"
                        autocomplete="username"
                        autofocus
                    >
                    <span class="error-message" id="username-error"></span>
                </div>

                <!-- Campo de Senha -->
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        placeholder="Digite sua senha"
                        autocomplete="current-password"
                    >
                    <span class="error-message" id="password-error"></span>
                </div>

                <!-- Lembrar Usuário -->
                <div class="form-checkbox">
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <label for="remember">Manter-me conectado</label>
                </div>

                <!-- Botão de Login -->
                <button type="submit" class="btn btn-login">Entrar</button>
            </form>

            <!-- Links Adicionais -->
            <div class="login-footer">
                <p class="help-text">
                    Problemas para acessar? Contacte o administrador do servidor.
                </p>
                <div class="footer-links">
                    <a href="../index.html">Voltar ao Site</a>
                </div>
            </div>

            <!-- Informações de Acesso (Desenvolvimento) -->
            <div class="dev-info">
                <p><strong>Credenciais padrão (Desenvolvimento):</strong></p>
                <p>Usuário: <code>GnomoMuitoLouco</code></p>
                <p>Senha: <code>Brasil2010!</code></p>
                <p style="color: #ff6b6b; margin-top: 10px;">⚠️ Altere a senha imediatamente após o primeiro acesso!</p>
            </div>
        </div>

        <!-- Background Effect -->
        <div class="login-background">
            <div class="bg-decoration bg-1"></div>
            <div class="bg-decoration bg-2"></div>
            <div class="bg-decoration bg-3"></div>
        </div>
    </div>

    <script>
        // Passar CSRF token para JavaScript
        const csrfToken = <?php echo json_encode($csrf_token); ?>;
    </script>
    <script src="login.js"></script>
</body>
</html>
