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

                <!-- Vendas -->
                <div class="stats-grid" style="margin-top: 1rem;">
                    <div class="stat-card">
                        <h3>Vendas</h3>
                        <p class="stat-value" id="sales-all">R$ 0,00</p>
                        <span class="stat-label">Desde o início</span>
                    </div>
                    <div class="stat-card">
                        <h3>Esse Ano</h3>
                        <p class="stat-value" id="sales-year">R$ 0,00</p>
                        <span class="stat-label">Acumulado</span>
                    </div>
                    <div class="stat-card">
                        <h3>Esse Mês</h3>
                        <p class="stat-value" id="sales-month">R$ 0,00</p>
                        <span class="stat-label">Últimos 30 dias</span>
                    </div>
                    <div class="stat-card">
                        <h3>Hoje</h3>
                        <p class="stat-value" id="sales-today">R$ 0,00</p>
                        <span class="stat-label">Últimas 24h</span>
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

            <!-- Seção Loja - Menu Principal -->
            <section id="loja" class="dashboard-section" style="display: none;">
                <div class="section-header">
                    <h2>Gerenciamento da Loja</h2>
                    <p>Administre produtos, cupons, pedidos e servidores</p>
                </div>

                <div class="loja-grid">
                    <div class="loja-card" onclick="showSubSection('registros')">
                        <div class="loja-icon">📋</div>
                        <h3>Registros</h3>
                        <p>Verificar todos os pedidos e status</p>
                        <button class="loja-btn">Acessar Registros</button>
                    </div>

                    <div class="loja-card" onclick="showSubSection('meta-comunidade')">
                        <div class="loja-icon">🎯</div>
                        <h3>Meta da Comunidade</h3>
                        <p>Valor mensal a ser atingido</p>
                        <button class="loja-btn">Acessar Meta</button>
                    </div>

                    <div class="loja-card" onclick="showSubSection('servidores')">
                        <div class="loja-icon">🎮</div>
                        <h3>Servidores</h3>
                        <p>Gerenciar servidores e APIs do mod</p>
                        <button class="loja-btn">Acessar Servidores</button>
                    </div>

                    <div class="loja-card" onclick="showSubSection('cupons')">
                        <div class="loja-icon">🎟️</div>
                        <h3>Cupons</h3>
                        <p>Criar cupons de desconto para a loja</p>
                        <button class="loja-btn">Acessar Cupons</button>
                    </div>

                    <div class="loja-card" onclick="showSubSection('configuracoes')">
                        <div class="loja-icon">⚙️</div>
                        <h3>Configurações</h3>
                        <p>Métodos de pagamento e valores</p>
                        <button class="loja-btn">Acessar Configurações</button>
                    </div>
                </div>
            </section>

            <!-- Subseção: Registros -->
            <section id="registros" class="dashboard-section" style="display: none;">
                <div class="section-header">
                    <button class="back-btn" onclick="showSection('loja')">← Voltar</button>
                    <h2>📋 Registros de Transações</h2>
                    <p>Histórico completo de pedidos e entregas</p>
                </div>

                <div class="filters-row">
                    <select id="filterStatusPagamento" onchange="loadTransactions()">
                        <option value="">Todos os pagamentos</option>
                        <option value="pendente">Pendente</option>
                        <option value="processando">Processando</option>
                        <option value="aprovado">Aprovado</option>
                        <option value="recusado">Recusado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>

                    <select id="filterStatusEntrega" onchange="loadTransactions()">
                        <option value="">Todas as entregas</option>
                        <option value="aguardando">Aguardando</option>
                        <option value="enviado">Enviado</option>
                        <option value="entregue">Entregue</option>
                        <option value="fila">Na fila</option>
                        <option value="falha">Falha</option>
                    </select>

                    <button class="btn-refresh" onclick="loadTransactions()">🔄 Atualizar</button>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Jogador</th>
                                <th>Valor</th>
                                <th>Método de Pagamento</th>
                                <th>Status</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody">
                            <tr><td colspan="6" style="text-align: center;">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Subseção: Meta da Comunidade -->
            <section id="meta-comunidade" class="dashboard-section" style="display: none;">
                <div class="section-header">
                    <button class="back-btn" onclick="showSection('loja')">← Voltar</button>
                    <h2>🎯 Meta da Comunidade</h2>
                    <p>Defina e acompanhe a meta mensal de doações</p>
                </div>

                <div class="meta-current">
                    <h3>Meta Atual</h3>
                    <div class="meta-stats">
                        <div class="meta-stat">
                            <span class="label">Mês:</span>
                            <span id="metaMes" class="value">-</span>
                        </div>
                        <div class="meta-stat">
                            <span class="label">Meta:</span>
                            <span id="metaValor" class="value">R$ 0,00</span>
                        </div>
                        <div class="meta-stat">
                            <span class="label">Arrecadado:</span>
                            <span id="metaAtual" class="value">R$ 0,00</span>
                        </div>
                        <div class="meta-stat">
                            <span class="label">Progresso:</span>
                            <span id="metaPercentual" class="value">0%</span>
                        </div>
                    </div>
                    <div class="progress-bar-container">
                        <div id="metaProgressBar" class="progress-bar" style="width: 0%;"></div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Definir Nova Meta</h3>
                    <form id="formMeta" onsubmit="saveMeta(event)">
                        <div class="form-group">
                            <label>Mês/Ano:</label>
                            <input type="month" id="inputMetaMes" required>
                        </div>
                        <div class="form-group">
                            <label>Valor da Meta (R$):</label>
                            <input type="number" step="0.01" id="inputMetaValor" placeholder="1000.00" required>
                        </div>
                        <button type="submit" class="btn-submit">💾 Salvar Meta</button>
                    </form>
                </div>
            </section>

            <!-- Subseção: Servidores -->
            <section id="servidores" class="dashboard-section" style="display: none;">
                <div class="section-header">
                    <button class="back-btn" onclick="showSection('loja')">← Voltar</button>
                    <h2>🎮 Gerenciamento de Servidores</h2>
                    <p>Configure APIs do mod para entrega automática</p>
                </div>

                <div class="servers-list" id="serversList">
                    <p style="text-align: center;">Carregando servidores...</p>
                </div>

                <div class="form-section">
                    <h3>Adicionar/Editar Servidor</h3>
                    <form id="formServidor" onsubmit="saveServidor(event)">
                        <input type="hidden" id="servidorId">
                        
                        <div class="form-group">
                            <label>Nome do Servidor:</label>
                            <input type="text" id="servidorNome" placeholder="Servidor Principal" required>
                        </div>

                        <div class="form-group">
                            <label>Identificador (slug):</label>
                            <input type="text" id="servidorIdentificador" placeholder="mgt" required>
                        </div>

                        <div class="form-group">
                            <label>Endereço IP:</label>
                            <input type="text" id="servidorIP" placeholder="play.magnatas.com">
                        </div>

                        <div class="form-group">
                            <label>URL da API do Mod:</label>
                            <input type="url" id="servidorAPIUrl" placeholder="http://localhost:8080/api" required>
                        </div>

                        <div class="form-group">
                            <label>API Key:</label>
                            <input type="text" id="servidorAPIKey" placeholder="chave-secreta-do-mod" required>
                        </div>

                        <div class="form-group">
                            <label>WebSocket URL:</label>
                            <input type="url" id="servidorWebSocketUrl" placeholder="ws://localhost:8080/ws">
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="servidorAtivo" checked>
                                Servidor Ativo
                            </label>
                        </div>

                        <button type="submit" class="btn-submit">💾 Salvar Servidor</button>
                        <button type="button" class="btn-cancel" onclick="resetFormServidor()">❌ Cancelar</button>
                    </form>
                </div>
            </section>

            <!-- Subseção: Cupons -->
            <section id="cupons" class="dashboard-section" style="display: none;">
                <div class="section-header">
                    <button class="back-btn" onclick="showSection('loja')">← Voltar</button>
                    <h2>🎟️ Gerenciamento de Cupons</h2>
                    <p>Criar e gerenciar cupons de desconto</p>
                </div>

                <div class="cupons-list" id="cuponsList">
                    <p style="text-align: center;">Carregando cupons...</p>
                </div>

                <div class="form-section">
                    <h3>Criar Novo Cupom</h3>
                    <form id="formCupom" onsubmit="saveCupom(event)">
                        <div class="form-group">
                            <label>Código do Cupom:</label>
                            <input type="text" id="cupomCodigo" placeholder="PROMO10" style="text-transform: uppercase;" required>
                        </div>

                        <div class="form-group">
                            <label>Tipo de Desconto:</label>
                            <select id="cupomTipo" required>
                                <option value="percentual">Percentual (%)</option>
                                <option value="fixo">Valor Fixo (R$)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Valor:</label>
                            <input type="number" step="0.01" id="cupomValor" placeholder="10.00" required>
                        </div>

                        <div class="form-group">
                            <label>Valor Mínimo de Compra (R$):</label>
                            <input type="number" step="0.01" id="cupomValorMinimo" placeholder="0.00" value="0">
                        </div>

                        <div class="form-group">
                            <label>Uso Máximo Total:</label>
                            <input type="number" id="cupomUsoMaximo" placeholder="100" required>
                        </div>

                        <div class="form-group">
                            <label>Uso Máximo por Usuário:</label>
                            <input type="number" id="cupomUsoPorUsuario" placeholder="1" value="1" required>
                        </div>

                        <div class="form-group">
                            <label>Data de Validade:</label>
                            <input type="date" id="cupomValidade" required>
                        </div>

                        <button type="submit" class="btn-submit">💾 Criar Cupom</button>
                    </form>
                </div>
            </section>

            <!-- Subseção: Configurações -->
            <section id="configuracoes" class="dashboard-section" style="display: none;">
                <div class="section-header">
                    <button class="back-btn" onclick="showSection('loja')">← Voltar</button>
                    <h2>⚙️ Configurações da Loja</h2>
                    <p>Métodos de pagamento e valores</p>
                </div>

                <div class="config-section">
                    <h3>💳 Métodos de Pagamento</h3>
                    
                    <!-- PayPal Legacy -->
                    <div class="payment-method-card">
                        <h4>🅿️ PayPal Legacy</h4>
                        <div class="form-group">
                            <label>Email da Conta:</label>
                            <input type="email" id="paypalEmail" placeholder="seu-email@exemplo.com">
                        </div>
                        <div class="form-group">
                            <label>Modo Sandbox:</label>
                            <div class="toggle-group">
                                <button type="button" class="toggle-btn" id="paypalSandboxBtn" onclick="togglePayPalSandbox()">
                                    <span id="paypalSandboxLabel">Desativado</span>
                                </button>
                                <input type="hidden" id="paypalSandbox" value="false">
                            </div>
                            <small>Ativa/desativa modo de testes</small>
                        </div>
                        <div class="form-group">
                            <label>URL de Callback:</label>
                            <div class="callback-display" id="paypalCallbackUrl"></div>
                            <small style="color: #999;">Copie esta URL para seu dashboard PayPal</small>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="paypalAtivo">
                                ✅ PayPal Ativo
                            </label>
                        </div>
                        <button class="btn-submit" onclick="savePaymentMethod('paypal')">💾 Salvar PayPal</button>
                    </div>

                    <!-- Mercado Pago -->
                    <div class="payment-method-card">
                        <h4>🟖 Mercado Pago</h4>
                        <div class="form-group">
                            <label>Access Token:</label>
                            <input type="password" id="mercadopagoAccessToken" placeholder="APP_USR-xxxxxxxxxxxx">
                        </div>
                        <div class="form-group">
                            <label>URL de Callback:</label>
                            <div class="callback-display" id="mercadopagoCallbackUrl"></div>
                            <small style="color: #999;">Copie esta URL para seu dashboard Mercado Pago</small>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="mercadopagoAtivo">
                                ✅ Mercado Pago Ativo
                            </label>
                        </div>
                        <button class="btn-submit" onclick="savePaymentMethod('mercadopago')">💾 Salvar Mercado Pago</button>
                    </div>

                    <!-- PIX -->
                    <div class="payment-method-card">
                        <h4>🔑 PIX</h4>
                        <div class="form-group">
                            <label>Chave PIX:</label>
                            <input type="text" id="pixChave" placeholder="email@exemplo.com ou CPF ou CNPJ">
                        </div>
                        <div class="form-group">
                            <label>Nome do Beneficiário:</label>
                            <input type="text" id="pixBeneficiario" placeholder="Servidor Magnatas">
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="pixAtivo">
                                ✅ PIX Ativo
                            </label>
                        </div>
                        <button class="btn-submit" onclick="savePaymentMethod('pix')">💾 Salvar PIX</button>
                    </div>
                </div>

                <div class="config-section">
                    <h3>💰 Valores e Sistema</h3>
                    
                    <div class="form-group">
                        <label>Valor do MGT-Cash (R$):</label>
                        <input type="number" step="0.001" id="configMGTCashValor" placeholder="0.050">
                        <small>1 Cash = R$ 0,05</small>
                    </div>

                    <div class="form-group">
                        <label>Máximo de Tentativas de Entrega:</label>
                        <input type="number" id="configMaxTentativas" placeholder="3" value="3">
                    </div>

                    <button class="btn-submit" onclick="saveGeneralConfig()">💾 Salvar Configurações</button>
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

        // Vendas iniciais
        loadSalesSummary();

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

        // ===================================
        // FUNÇÕES DE NAVEGAÇÃO
        // ===================================
        
        function showSection(sectionId) {
            document.querySelectorAll('.dashboard-section').forEach(s => s.style.display = 'none');
            document.getElementById(sectionId).style.display = 'block';
        }

        function showSubSection(subSectionId) {
            showSection(subSectionId);
            
            // Carregar dados específicos da seção
            switch(subSectionId) {
                case 'registros':
                    loadTransactions();
                    break;
                case 'meta-comunidade':
                    loadMetaComunidade();
                    break;
                case 'servidores':
                    loadServidores();
                    break;
                case 'cupons':
                    loadCupons();
                    break;
                case 'configuracoes':
                    loadConfiguracoes();
                    break;
            }
        }

        // ===================================
        // REGISTROS (TRANSAÇÕES)
        // ===================================
        
        async function loadTransactions() {
            const statusPagamento = document.getElementById('filterStatusPagamento').value;
            const statusEntrega = document.getElementById('filterStatusEntrega').value;
            
            const params = new URLSearchParams();
            if (statusPagamento) params.append('status_pagamento', statusPagamento);
            if (statusEntrega) params.append('status_entrega', statusEntrega);
            
            try {
                const response = await fetch(`/backend/api_loja.php?path=transactions&${params}`);
                const result = await response.json();
                
                if (result.success) {
                    renderTransactions(result.data);
                } else {
                    alert('Erro ao carregar transações: ' + result.error);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao conectar com o servidor');
            }
        }

        function renderTransactions(transactions) {
            const tbody = document.getElementById('transactionsTableBody');
            
            if (!transactions || transactions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: #aaa;">Nenhum registro</td></tr>';
                return;
            }
            
            tbody.innerHTML = transactions.map(t => `
                <tr>
                    <td><strong>${t.pedido_numero}</strong></td>
                    <td>${t.jogador_nick}</td>
                    <td>${formatCurrency(t.valor_total)}</td>
                    <td>${formatPaymentMethod(t.metodo_pagamento)}</td>
                    <td><span class="badge badge-${t.status_pagamento}">${t.status_pagamento_label || t.status_pagamento}</span></td>
                    <td>${formatDateTime(t.criado_em)}</td>
                </tr>
            `).join('');
        }

        async function retryDelivery(transactionId) {
            if (!confirm('Tentar reenviar este pedido?')) return;
            
            // TODO: Implementar chamada à API para retentar entrega
            alert('Funcionalidade em desenvolvimento');
        }

        // ===================================
        // META DA COMUNIDADE
        // ===================================
        
        async function loadMetaComunidade() {
            try {
                const response = await fetch('/backend/api_loja.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'obter_meta_comunidade'
                    })
                });
                const result = await response.json();
                
                if (result.success && result.meta) {
                    renderMetaComunidade(result.meta);
                } else {
                    renderMetaVazia();
                }
            } catch (error) {
                console.error('Erro ao carregar meta:', error);
                renderMetaVazia();
            }
        }
        
        function renderMetaComunidade(meta) {
            const percentual = meta.valor_objetivo > 0 
                ? (meta.valor_atual / meta.valor_objetivo * 100).toFixed(1) 
                : 0;
            
            document.getElementById('metaMes').textContent = new Date(meta.mes + '-01').toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
            document.getElementById('metaValor').textContent = `R$ ${parseFloat(meta.valor_objetivo).toFixed(2)}`;
            document.getElementById('metaAtual').textContent = `R$ ${parseFloat(meta.valor_atual).toFixed(2)}`;
            document.getElementById('metaPercentual').textContent = `${percentual}%`;
            document.getElementById('metaProgressBar').style.width = `${Math.min(percentual, 100)}%`;
        }
        
        function renderMetaVazia() {
            document.getElementById('metaMes').textContent = '-';
            document.getElementById('metaValor').textContent = 'R$ 0,00';
            document.getElementById('metaAtual').textContent = 'R$ 0,00';
            document.getElementById('metaPercentual').textContent = '0%';
            document.getElementById('metaProgressBar').style.width = '0%';
        }

        function saveMeta(event) {
            event.preventDefault();
            
            const mes = document.getElementById('inputMetaMes').value;
            const valor = parseFloat(document.getElementById('inputMetaValor').value);
            
            // TODO: Implementar salvamento no banco
            alert(`Meta definida: ${mes} - R$ ${valor.toFixed(2)}`);
        }

        // ===================================
        // SERVIDORES
        // ===================================
        
        async function loadServidores() {
            try {
                const response = await fetch('/backend/api_loja.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'listar_servidores'
                    })
                });
                const result = await response.json();
                
                if (result.success && result.servidores && result.servidores.length > 0) {
                    renderServidores(result.servidores);
                } else {
                    renderServidoresVazio();
                }
            } catch (error) {
                console.error('Erro ao carregar servidores:', error);
                renderServidoresVazio();
            }
        }
        
        function renderServidores(servidores) {
            const container = document.getElementById('serversList');
            container.innerHTML = servidores.map(s => `
                <div class="server-item">
                    <div class="server-info">
                        <h4>${s.nome} (${s.identificador})</h4>
                        <span class="badge ${s.ativo ? 'badge-ativo' : 'badge-inativo'}">
                            ${s.ativo ? '✓ Ativo' : '✗ Inativo'}
                        </span>
                    </div>
                    <div class="server-actions">
                        <button onclick="editServidor(${s.id})">✏️ Editar</button>
                        <button onclick="testServerConnection(${s.id})">🔌 Testar</button>
                    </div>
                </div>
            `).join('');
        }
        
        function renderServidoresVazio() {
            const container = document.getElementById('serversList');
            container.innerHTML = '<p style="text-align: center; padding: 2rem; color: #aaa;">Nenhum registro</p>';
        }

        function editServidor(id) {
            // TODO: Carregar dados do servidor e preencher formulário
            alert('Edição de servidor em desenvolvimento');
        }

        function testServerConnection(id) {
            // TODO: Testar conexão com API do mod
            alert('Teste de conexão em desenvolvimento');
        }

        function saveServidor(event) {
            event.preventDefault();
            // TODO: Implementar salvamento no banco
            alert('Salvamento de servidor em desenvolvimento');
        }

        function resetFormServidor() {
            document.getElementById('formServidor').reset();
            document.getElementById('servidorId').value = '';
        }

        // ===================================
        // CUPONS
        // ===================================
        
        async function loadCupons() {
            try {
                const response = await fetch('/backend/api_loja.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'listar_cupons'
                    })
                });
                const result = await response.json();
                
                if (result.success && result.cupons && result.cupons.length > 0) {
                    renderCupons(result.cupons);
                } else {
                    renderCuponsVazio();
                }
            } catch (error) {
                console.error('Erro ao carregar cupons:', error);
                renderCuponsVazio();
            }
        }
        
        function renderCupons(cupons) {
            const container = document.getElementById('cuponsList');
            container.innerHTML = cupons.map(c => `
                <div class="cupom-item">
                    <div class="cupom-info">
                        <h4>${c.codigo}</h4>
                        <p>${c.tipo_desconto === 'percentual' ? c.valor_desconto + '%' : 'R$ ' + c.valor_desconto}</p>
                        <small>Válido até: ${new Date(c.data_validade).toLocaleDateString('pt-BR')}</small>
                    </div>
                    <div class="cupom-actions">
                        <span class="badge ${c.ativo ? 'badge-ativo' : 'badge-inativo'}">
                            ${c.ativo ? '✓ Ativo' : '✗ Inativo'}
                        </span>
                    </div>
                </div>
            `).join('');
        }
        
        function renderCuponsVazio() {
            const container = document.getElementById('cuponsList');
            container.innerHTML = '<p style="text-align: center; padding: 2rem; color: #aaa;">Nenhum registro</p>';
        }

        function saveCupom(event) {
            event.preventDefault();
            
            const codigo = document.getElementById('cupomCodigo').value.toUpperCase();
            const tipo = document.getElementById('cupomTipo').value;
            const valor = parseFloat(document.getElementById('cupomValor').value);
            
            // TODO: Implementar salvamento no banco
            alert(`Cupom ${codigo} criado com sucesso!`);
            document.getElementById('formCupom').reset();
        }

        // ===================================
        // CONFIGURAÇÕES
        // ===================================
        
        async function loadConfiguracoes() {
            try {
                const response = await fetch('/backend/api_loja.php?path=config');
                const result = await response.json();

                if (!result.success) throw new Error(result.error || 'Erro ao carregar');

                const general = result.data.general || {};
                const methods = result.data.paymentMethods || {};

                // Gerais
                if (general.mgt_cash_valor !== undefined) {
                    document.getElementById('configMGTCashValor').value = general.mgt_cash_valor;
                }
                if (general.max_tentativas_entrega !== undefined) {
                    document.getElementById('configMaxTentativas').value = general.max_tentativas_entrega;
                }

                // PayPal (simplificado)
                const paypal = methods.paypal || {};
                document.getElementById('paypalAtivo').checked = !!paypal.ativo;
                document.getElementById('paypalEmail').value = paypal.config?.email || '';
                document.getElementById('paypalSandbox').value = (paypal.config?.sandbox === true).toString();
                
                // Atualizar visual do toggle de sandbox
                if (paypal.config?.sandbox === true) {
                    document.getElementById('paypalSandboxBtn').classList.add('active');
                    document.getElementById('paypalSandboxBtn').style.backgroundColor = '#27ae60';
                    document.getElementById('paypalSandboxBtn').style.color = 'white';
                    document.getElementById('paypalSandboxLabel').textContent = 'Ativado';
                } else {
                    document.getElementById('paypalSandboxBtn').classList.remove('active');
                    document.getElementById('paypalSandboxBtn').style.backgroundColor = '#e74c3c';
                    document.getElementById('paypalSandboxBtn').style.color = 'white';
                    document.getElementById('paypalSandboxLabel').textContent = 'Desativado';
                }

                // Mercado Pago (simplificado)
                const mp = methods.mercadopago || {};
                document.getElementById('mercadopagoAtivo').checked = !!mp.ativo;
                document.getElementById('mercadopagoAccessToken').value = mp.config?.accessToken || '';

                // PIX
                const pix = methods.pix || {};
                document.getElementById('pixAtivo').checked = !!pix.ativo;
                document.getElementById('pixChave').value = pix.config?.chave || '';
                document.getElementById('pixBeneficiario').value = pix.config?.beneficiario || '';
                
                // Gerar e exibir URLs de callback
                generateCallbackURLs();
            } catch (error) {
                console.error('Erro ao carregar configurações:', error);
                alert('Não foi possível carregar as configurações da loja.');
                // Mesmo com erro, gerar URLs de callback
                generateCallbackURLs();
            }
        }
                document.getElementById('pixChave').value = pix.config?.chave || '';
                document.getElementById('pixBeneficiario').value = pix.config?.beneficiario || '';

            } catch (error) {
                console.error('Erro ao carregar configurações:', error);
                alert('Não foi possível carregar as configurações da loja.');
            }
        }

        // ===================================
        // FUNÇÕES DE CONFIGURAÇÃO DE PAGAMENTO
        // ===================================
        
        // Gera e exibe as URLs de callback automaticamente
        function generateCallbackURLs() {
            const baseURL = window.location.origin;
            
            // PayPal Callback
            const paypalCallbackUrl = `${baseURL}/backend/callback/paypal_legacy`;
            document.getElementById('paypalCallbackUrl').textContent = paypalCallbackUrl;
            document.getElementById('paypalCallbackUrl').style.padding = '8px 12px';
            document.getElementById('paypalCallbackUrl').style.backgroundColor = '#f5f5f5';
            document.getElementById('paypalCallbackUrl').style.border = '1px solid #ddd';
            document.getElementById('paypalCallbackUrl').style.borderRadius = '4px';
            document.getElementById('paypalCallbackUrl').style.fontFamily = 'monospace';
            document.getElementById('paypalCallbackUrl').style.fontSize = '12px';
            document.getElementById('paypalCallbackUrl').style.wordBreak = 'break-all';
            document.getElementById('paypalCallbackUrl').style.cursor = 'pointer';
            document.getElementById('paypalCallbackUrl').title = 'Clique para copiar';
            document.getElementById('paypalCallbackUrl').onclick = function() {
                navigator.clipboard.writeText(paypalCallbackUrl);
                alert('URL copiada para a área de transferência!');
            };
            
            // Mercado Pago Callback
            const mercadopagoCallbackUrl = `${baseURL}/backend/callback/mercadopago`;
            document.getElementById('mercadopagoCallbackUrl').textContent = mercadopagoCallbackUrl;
            document.getElementById('mercadopagoCallbackUrl').style.padding = '8px 12px';
            document.getElementById('mercadopagoCallbackUrl').style.backgroundColor = '#f5f5f5';
            document.getElementById('mercadopagoCallbackUrl').style.border = '1px solid #ddd';
            document.getElementById('mercadopagoCallbackUrl').style.borderRadius = '4px';
            document.getElementById('mercadopagoCallbackUrl').style.fontFamily = 'monospace';
            document.getElementById('mercadopagoCallbackUrl').style.fontSize = '12px';
            document.getElementById('mercadopagoCallbackUrl').style.wordBreak = 'break-all';
            document.getElementById('mercadopagoCallbackUrl').style.cursor = 'pointer';
            document.getElementById('mercadopagoCallbackUrl').title = 'Clique para copiar';
            document.getElementById('mercadopagoCallbackUrl').onclick = function() {
                navigator.clipboard.writeText(mercadopagoCallbackUrl);
                alert('URL copiada para a área de transferência!');
            };
        }

        // Toggle para o Sandbox do PayPal
        function togglePayPalSandbox() {
            const currentValue = document.getElementById('paypalSandbox').value === 'true';
            const newValue = !currentValue;
            
            document.getElementById('paypalSandbox').value = newValue.toString();
            document.getElementById('paypalSandboxBtn').classList.toggle('active');
            document.getElementById('paypalSandboxLabel').textContent = newValue ? 'Ativado' : 'Desativado';
            document.getElementById('paypalSandboxBtn').style.backgroundColor = newValue ? '#27ae60' : '#e74c3c';
            document.getElementById('paypalSandboxBtn').style.color = 'white';
        }

        function savePaymentMethod(method) {
            saveConfigPayload();
            alert(`Configurações de ${method} salvas com sucesso!`);
        }

        function saveGeneralConfig() {
            saveConfigPayload();
            alert('Configurações da loja salvas com sucesso!');
        }

        async function saveConfigPayload() {
            const payload = {
                general: {
                    mgt_cash_valor: document.getElementById('configMGTCashValor').value,
                    max_tentativas_entrega: document.getElementById('configMaxTentativas').value,
                },
                paymentMethods: {
                    paypal: {
                        ativo: document.getElementById('paypalAtivo').checked,
                        config: {
                            email: document.getElementById('paypalEmail').value,
                            sandbox: document.getElementById('paypalSandbox').value === 'true',
                        }
                    },
                    mercadopago: {
                        ativo: document.getElementById('mercadopagoAtivo').checked,
                        config: {
                            accessToken: document.getElementById('mercadopagoAccessToken').value,
                        }
                    },
                    pix: {
                        ativo: document.getElementById('pixAtivo').checked,
                        config: {
                            chave: document.getElementById('pixChave').value,
                            beneficiario: document.getElementById('pixBeneficiario').value,
                        }
                    }
                }
            };

            try {
                await fetch('/backend/api_loja.php?path=config', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
            } catch (error) {
                console.error('Erro ao salvar configurações:', error);
                alert('Erro ao salvar configurações da loja.');
            }
        }

        // ===================================
        // VENDAS
        // ===================================

        async function loadSalesSummary() {
            try {
                const response = await fetch('/backend/api_dashboard.php?action=get_store_stats');
                const data = await response.json();
                
                if (data.total_all !== undefined) {
                    document.getElementById('sales-all').textContent = formatCurrency(data.total_all);
                    document.getElementById('sales-year').textContent = formatCurrency(data.total_year);
                    document.getElementById('sales-month').textContent = formatCurrency(data.total_month);
                    document.getElementById('sales-today').textContent = formatCurrency(data.total_today);
                }
            } catch (error) {
                console.error('Erro ao carregar vendas:', error);
            }
        }

        function formatCurrency(value) {
            const numero = Number(value || 0);
            return numero.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        function formatPaymentMethod(method) {
            if (!method) return '-';
            const map = {
                paypal: 'PayPal',
                mercadopago: 'Mercado Pago',
                pix: 'PIX',
            };
            return map[method] || method;
        }

        function formatDateTime(dt) {
            const date = new Date(dt);
            if (Number.isNaN(date.getTime())) return dt;
            return date.toLocaleString('pt-BR', {
                timeZone: 'America/Sao_Paulo',
                day: '2-digit', month: '2-digit', year: '2-digit',
                hour: '2-digit', minute: '2-digit'
            });
        }
    </script>
</body>
</html>
