<?php
/**
 * Dashboard - Painel Administrativo
 */

require_once '../backend/check_auth.php';

$csrf_token = generateCSRFToken();
$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Servidor Magnatas</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="dashboard.css">
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
                <span class="user-name">Olá, <strong><?php echo htmlspecialchars($username); ?></strong></span>
                <a href="../backend/logout.php" class="logout-btn">Sair</a>
            </div>
        </div>
    </nav>

    <!-- Conteúdo Principal -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <ul class="menu">
                <li><a href="#home" class="menu-item active">📊 Dashboard</a></li>
                <li><a href="#servidores" class="menu-item">🎮 Servidores</a></li>
                <li><a href="#usuarios" class="menu-item">👥 Usuários</a></li>
                <li><a href="#configuracoes" class="menu-item">⚙️ Configurações</a></li>
                <li><a href="../index.html" class="menu-item">🌐 Ver Site</a></li>
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
                        <p class="stat-value" style="color: #4caf50;">✓ Online</p>
                        <span class="stat-label">Servidor funcionando</span>
                    </div>
                    <div class="stat-card">
                        <h3>Visitantes</h3>
                        <p class="stat-value"><?php echo rand(100, 1000); ?></p>
                        <span class="stat-label">Este mês</span>
                    </div>
                    <div class="stat-card">
                        <h3>Versão</h3>
                        <p class="stat-value"><?php echo phpversion(); ?></p>
                        <span class="stat-label">PHP <?php echo phpversion(); ?></span>
                    </div>
                    <div class="stat-card">
                        <h3>Hora</h3>
                        <p class="stat-value" id="current-time"><?php echo date('H:i:s'); ?></p>
                        <span class="stat-label" id="current-date"><?php echo strftime('%d/%m/%Y'); ?></span>
                    </div>
                </div>

                <!-- Ações Rápidas -->
                <div class="actions-section">
                    <h3>Ações Rápidas</h3>
                    <div class="actions-grid">
                        <a href="../index.html" class="action-btn">
                            <span class="action-icon">🌐</span>
                            <span>Ver Site</span>
                        </a>
                        <a href="#configuracoes" class="action-btn">
                            <span class="action-icon">⚙️</span>
                            <span>Configurações</span>
                        </a>
                        <a href="../backend/logout.php" class="action-btn" onclick="return confirm('Deseja sair?')">
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
                            <td><?php echo htmlspecialchars($username); ?></td>
                        </tr>
                        <tr>
                            <td><strong>IP:</strong></td>
                            <td><?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Host:</strong></td>
                            <td><?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost:8000'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>SO:</strong></td>
                            <td><?php echo htmlspecialchars(php_uname('s')); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Timezone:</strong></td>
                            <td><?php echo date_default_timezone_get(); ?></td>
                        </tr>
                    </table>
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
            item.addEventListener('click', function() {
                document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>
