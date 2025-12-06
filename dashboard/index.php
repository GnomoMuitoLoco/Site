<?php
/**
 * Dashboard - Home Page
 * Painel principal de administração do Servidor Magnatas
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
load_module('MGT-Auth', 'AuthManager.php');
load_module('MGT-Dashboard', 'DashboardManager.php');

use MGT\Auth\AuthManager;
use MGT\Dashboard\DashboardManager;

// Verifica autenticação
AuthManager::initSession();
if (!AuthManager::isLoggedIn()) {
    header('Location: /dashboard/login.php');
    exit;
}

// Obtém dados para a página
$username = AuthManager::getUser();
$csrf_token = AuthManager::generateCSRFToken();
$stats = DashboardManager::getStats();
$system_info = DashboardManager::getSystemInfo();
$menu_items = DashboardManager::getMenuItems();
$store_items = DashboardManager::getStoreItems();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Servidor Magnatas</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="/dashboard/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">
    <!-- Navegação -->
    <nav class="dashboard-navbar">
        <div class="navbar-container">
            <div class="navbar-left">
                <h1>Servidor <span>Magnatas</span></h1>
            </div>
            <div class="navbar-right">
                <span class="user-name">Olá, <strong><?php echo htmlspecialchars($username ?? 'Admin'); ?></strong></span>
                <a href="/backend/logout.php" class="logout-btn">Sair</a>
            </div>
        </div>
    </nav>

    <!-- Conteúdo Principal -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <ul class="menu">
                <li><a href="#home" class="menu-item active">📊 Dashboard</a></li>
                <li><a href="#loja" class="menu-item">🛍️ Loja</a></li>
                <!-- <li><a href="#servidores" class="menu-item">🎮 Servidores</a></li> -->
                <!-- <li><a href="#usuarios" class="menu-item">👥 Usuários</a></li> -->
                <!-- <li><a href="#configuracoes" class="menu-item">⚙️ Configurações</a></li> -->
                <li><a href="/index.html" class="menu-item">🌐 Ver Site</a></li>
            </ul>
        </aside>

        <!-- Área de Conteúdo -->
        <main class="dashboard-content">
            <!-- Seção de Boas-vindas -->
            <section id="home" class="dashboard-section">
                <div class="section-header">
                    <h2>Bem-vindo ao Painel!</h2>
                    <p>Gerencie sua presença online</p>
                </div>

                <!-- Cards de Informações -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Status</h3>
                        <p class="stat-value" style="color: #4ade80;">✓ <?php echo ucfirst($stats['status']); ?></p>
                        <span class="stat-label">Servidor funcionando</span>
                    </div>
                    <div class="stat-card">
                        <h3>Visitantes</h3>
                        <p class="stat-value"><?php echo $stats['visitors']; ?></p>
                        <span class="stat-label">Este mês</span>
                    </div>
                    <div class="stat-card">
                        <h3>Versão</h3>
                        <p class="stat-value"><?php echo $stats['php_version']; ?></p>
                        <span class="stat-label">PHP <?php echo $stats['php_version']; ?></span>
                    </div>
                    <div class="stat-card">
                        <h3>Hora</h3>
                        <p class="stat-value" id="current-time"><?php echo $stats['current_time']; ?></p>
                        <span class="stat-label" id="current-date"><?php echo $stats['current_date']; ?></span>
                    </div>
                </div>

                <!-- Ações Rápidas -->
                <div class="actions-section">
                    <h3>Ações Rápidas</h3>
                    <div class="actions-grid">
                        <a href="/index.html" class="action-btn">
                            <span class="action-icon">🌐</span>
                            <span>Ver Site</span>
                        </a>
                        <a href="/backend/logout.php" class="action-btn" onclick="return confirm('Deseja sair?')">
                            <span class="action-icon">🚪</span>
                            <span>Sair</span>
                        </a>
                    </div>
                </div>

                <!-- Informações do Sistema -->
                <div class="system-info">
                    <h3>Informações do Sistema</h3>
                    <table>
                        <tr>
                            <td><strong>Usuário Logado:</strong></td>
                            <td><?php echo htmlspecialchars($system_info['logged_user']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>IP:</strong></td>
                            <td><?php echo htmlspecialchars($system_info['ip']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Host:</strong></td>
                            <td><?php echo htmlspecialchars($system_info['host']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>SO:</strong></td>
                            <td><?php echo htmlspecialchars($system_info['os']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Timezone:</strong></td>
                            <td><?php echo $system_info['timezone']; ?></td>
                        </tr>
                    </table>
                </div>
            </section>

            <!-- Seção Loja -->
            <section id="loja" class="dashboard-section" style="display: none;">
                <div class="section-header">
                    <h2>Gerenciamento da Loja</h2>
                    <p>Administre produtos, categorias, cupons e pedidos</p>
                </div>

                <div class="loja-grid">
                    <div class="loja-card">
                        <div class="loja-icon">📦</div>
                        <h3>Produtos</h3>
                        <p>Cadastrar e gerenciar produtos da loja</p>
                        <button class="loja-btn">Acessar Produtos</button>
                    </div>

                    <div class="loja-card">
                        <div class="loja-icon">🏷️</div>
                        <h3>Categorias</h3>
                        <p>Criar categorias para vincular produtos</p>
                        <button class="loja-btn">Acessar Categorias</button>
                    </div>

                    <div class="loja-card">
                        <div class="loja-icon">🎮</div>
                        <h3>Servidores</h3>
                        <p>Vincular Remote Console para entrega automática</p>
                        <button class="loja-btn">Acessar Servidores</button>
                    </div>

                    <div class="loja-card">
                        <div class="loja-icon">🎟️</div>
                        <h3>Cupons</h3>
                        <p>Criar cupons de desconto para a loja</p>
                        <button class="loja-btn">Acessar Cupons</button>
                    </div>

                    <div class="loja-card">
                        <div class="loja-icon">📋</div>
                        <h3>Registros</h3>
                        <p>Verificar todos os pedidos e status</p>
                        <button class="loja-btn">Acessar Registros</button>
                    </div>

                    <div class="loja-card">
                        <div class="loja-icon">🎯</div>
                        <h3>Meta da Comunidade</h3>
                        <p>Valor mensal a ser atingido</p>
                        <button class="loja-btn">Acessar Meta</button>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Rodapé -->
    <footer class="dashboard-footer">
        <p>&copy; 2025 Servidor Magnatas - Todos os direitos reservados</p>
    </footer>

    <script>
        // Atualizar hora em tempo real
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = 
                now.toLocaleTimeString('pt-BR');
            document.getElementById('current-date').textContent = 
                now.toLocaleDateString('pt-BR');
        }
        
        setInterval(updateTime, 1000);
        updateTime();

        // Menu interativo
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                // Se o link não começa com #, permite navegação normal
                if (!href.startsWith('#')) {
                    return;
                }
                
                e.preventDefault();
                
                // Remove active de todos os itens
                document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                // Esconde todas as seções
                document.querySelectorAll('.dashboard-section').forEach(section => {
                    section.style.display = 'none';
                });
                
                // Mostra a seção correspondente
                const targetId = href.substring(1);
                const targetSection = document.getElementById(targetId);
                if (targetSection) {
                    targetSection.style.display = 'block';
                }
            });
        });
    </script>
</body>
</html>
