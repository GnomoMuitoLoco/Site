/**
 * Dashboard JavaScript
 * Arquivo: dashboard/dashboard.js
 */

document.addEventListener('DOMContentLoaded', function() {
    // Carregar dados do dashboard
    loadDashboardData();

    // Atualizar hora do servidor
    updateServerTime();
    setInterval(updateServerTime, 1000);

    // Carregar informações do sistema
    loadSystemInfo();

    // Atualizar dados a cada 30 segundos
    setInterval(loadDashboardData, 30000);
});

/**
 * Carregar dados do dashboard via API
 */
async function loadDashboardData() {
    try {
        const response = await fetch('../backend/api_dashboard.php?action=get_stats');
        const data = await response.json();

        if (data.active_users !== undefined) {
            document.getElementById('active-users').textContent = data.active_users;
        }

        if (data.total_logins !== undefined) {
            document.getElementById('total-logins').textContent = data.total_logins;
        }

        if (data.active_sessions !== undefined) {
            document.getElementById('active-sessions').textContent = data.active_sessions;
        }

        if (data.system_status !== undefined) {
            document.getElementById('system-status').textContent = data.system_status;
        }

        // Atualizar timestamp
        updateLastUpdate();
    } catch (error) {
        console.error('Erro ao carregar dados:', error);
    }
}

/**
 * Carregar informações do sistema
 */
async function loadSystemInfo() {
    try {
        const response = await fetch('../backend/api_dashboard.php?action=get_system_info');
        const data = await response.json();

        if (data.php_version) {
            document.getElementById('php-version').textContent = data.php_version;
        }

        if (data.server_ip) {
            document.getElementById('server-ip').textContent = data.server_ip;
        }
    } catch (error) {
        console.error('Erro ao carregar informações do sistema:', error);
    }
}

/**
 * Atualizar hora do servidor
 */
function updateServerTime() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    document.getElementById('server-time').textContent = `${hours}:${minutes}:${seconds}`;
}

/**
 * Atualizar timestamp da última atualização
 */
function updateLastUpdate() {
    const now = new Date();
    document.getElementById('last-update').textContent = 'agora';
}

/**
 * Obter usuário atual
 */
function getCurrentUserInfo() {
    // Em produção, isso vem do servidor
    const userElement = document.getElementById('current-user');
    if (userElement) {
        console.log('Usuário atual:', userElement.textContent);
    }
}

/**
 * Logout
 */
function logout() {
    if (confirm('Tem certeza que deseja sair?')) {
        window.location.href = '../backend/logout.php';
    }
}

/**
 * Inicializar tooltips (opcional)
 */
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(el => {
        el.addEventListener('mouseenter', function() {
            const tooltip = this.getAttribute('data-tooltip');
            console.log('Tooltip:', tooltip);
        });
    });
}

/**
 * Animação de carregamento
 */
function showLoadingAnimation() {
    const loader = document.createElement('div');
    loader.className = 'loading-spinner';
    loader.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(loader);
    
    setTimeout(() => {
        loader.remove();
    }, 1000);
}

// Iniciar quando estiver pronto
window.addEventListener('load', function() {
    console.log('Dashboard carregado com sucesso');
    getCurrentUserInfo();
});
