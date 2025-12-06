/* =======================================
   LOJA - INTERATIVIDADE
   ======================================= */

document.addEventListener('DOMContentLoaded', function() {
    initializeStore();
});

// =======================================
// INICIALIZAÇÃO DA LOJA
// =======================================

function initializeStore() {
    // Carregar dados iniciais
    loadCommunityGoalData();
    loadRecentDonorsData();
    setupEventListeners();
}

// =======================================
// REDIRECIONAMENTO PARA CHECKOUT
// =======================================

function redirectToCheckout(server) {
    // Salvar servidor selecionado na sessão
    sessionStorage.setItem('selectedServer', server);
    
    // Redirecionar para checkout
    window.location.href = `checkout.html?server=${server}`;
}

// =======================================
// CARREGAMENTO DE DADOS - DOADORES
// ======================================= */

function loadRecentDonorsData() {
    // Buscar transações aprovadas (últimas 10)
    fetch('backend/api_loja.php?path=transactions&status_pagamento=aprovado&limit=10')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data && data.data.length > 0) {
            // Ordenar por data decrescente
            const sorted = data.data.sort((a, b) => 
                new Date(b.criado_em) - new Date(a.criado_em)
            );
            
            // Top doador é o primeiro da lista (mais recente aprovado)
            const topDonor = sorted[0];
            
            renderDonorsData(sorted, topDonor);
        } else {
            renderEmptyDonorsState();
        }
    })
    .catch(error => {
        console.error('Erro ao carregar doadores:', error);
        renderEmptyDonorsState();
    });
}

function renderDonorsData(transactions, topDonor) {
    // Renderizar top doador se existir
    const topDonorCard = document.getElementById('topDonorCard');
    if (topDonor) {
        topDonorCard.innerHTML = `
            <div class="top-donor-avatar">
                <img src="https://minotar.net/body/${topDonor.jogador_nick}/100.png" 
                     alt="Avatar" 
                     class="avatar-img-full"
                     onerror="this.src='https://minotar.net/avatar/${topDonor.jogador_nick}/100.png'">
            </div>
            <div class="top-donor-info">
                <h4>${topDonor.jogador_nick}</h4>
                <p class="top-donor-message">"¡Obrigado pelo apoio extraordinário!"</p>
            </div>
        `;
    } else {
        topDonorCard.innerHTML = '<p style="text-align: center; color: #aaa; padding: 2rem;">Nenhum registro</p>';
    }
    
    // Preencher tabela de doadores (últimas transações)
    const tableBody = document.getElementById('donorsTableBody');
    tableBody.innerHTML = transactions.slice(0, 10).map(transaction => {
        const date = new Date(transaction.criado_em);
        const timeAgo = getTimeAgo(date);
        
        return `
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div class="donor-avatar-mini">
                            <img src="https://minotar.net/avatar/${transaction.jogador_nick}/32" 
                                 alt="${transaction.jogador_nick}" 
                                 class="avatar-img" 
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiBmaWxsPSIjMmEyYTJhIi8+PC9zdmc+'">
                        </div>
                        <span class="donor-name">${transaction.jogador_nick}</span>
                    </div>
                </td>
                <td class="donor-date">${timeAgo}</td>
            </tr>
        `;
    }).join('');
    
    // Atualizar estatísticas
    updateStoreStats(transactions.length);
}

function renderEmptyDonorsState() {
    // Estado vazio para top doador
    const topDonorCard = document.getElementById('topDonorCard');
    topDonorCard.innerHTML = '<p style="text-align: center; color: #aaa; padding: 2rem;">Nenhum registro</p>';
    
    // Estado vazio para tabela
    const tableBody = document.getElementById('donorsTableBody');
    tableBody.innerHTML = '<tr><td colspan="2" style="text-align: center; color: #aaa; padding: 2rem;">Nenhum registro</td></tr>';
    
    // Zerar estatísticas
    updateStoreStats(0);
}

function updateStoreStats(totalDonors) {
    const totalDonorsEl = document.getElementById('totalDonors');
    const itemsDeliveredEl = document.getElementById('itemsDelivered');
    
    if (totalDonorsEl) totalDonorsEl.textContent = totalDonors;
    if (itemsDeliveredEl) itemsDeliveredEl.textContent = totalDonors; // Por enquanto usar mesmo valor
}

function getTimeAgo(date) {
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 60) {
        return `Há ${diffMins} minuto${diffMins !== 1 ? 's' : ''}`;
    } else if (diffHours < 24) {
        return `Há ${diffHours} hora${diffHours !== 1 ? 's' : ''}`;
    } else {
        return `Há ${diffDays} dia${diffDays !== 1 ? 's' : ''}`;
    }
}

function loadCommunityGoalData() {
    // Buscar meta da comunidade (mês/ano atual)
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const year = now.getFullYear();
    
    fetch(`backend/api_loja.php?path=meta-comunidade&mes=${month}&ano=${year}`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            const meta = data.data;
            const percentage = meta.valor_meta > 0 
                ? Math.min(100, Math.floor((meta.valor_atual / meta.valor_meta) * 100))
                : 0;
            
            updateGoalProgress(percentage, meta);
        } else {
            updateGoalProgress(0, null);
        }
    })
    .catch(error => {
        console.error('Erro ao carregar meta da comunidade:', error);
        updateGoalProgress(0, null);
    });
}

function updateGoalProgress(percentage, meta = null) {
    const progressFill = document.getElementById('goalProgressBar');
    const progressText = document.getElementById('goalProgressText');
    
    if (progressFill) progressFill.style.width = `${percentage}%`;
    if (progressText) {
        if (meta) {
            const atual = formatCurrency(meta.valor_atual);
            const objetivo = formatCurrency(meta.valor_meta);
            progressText.textContent = `${atual} / ${objetivo} (${percentage}%)`;
        } else {
            progressText.textContent = `${percentage}% atingido`;
        }
    }
}

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value || 0);
}

// =======================================
// UTILITÁRIOS
// =======================================

function setupEventListeners() {
    // Event listeners se necessário
}

function showNotification(message, type = 'info') {
    // Criar elemento de notificação
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
        max-width: 400px;
    `;

    // Cores por tipo
    const colors = {
        'success': { bg: '#4ade80', text: '#fff' },
        'error': { bg: '#ef4444', text: '#fff' },
        'warning': { bg: '#f59e0b', text: '#fff' },
        'info': { bg: 'rgba(59, 130, 246, 0.9)', text: '#fff' }
    };

    const color = colors[type] || colors['info'];
    notification.style.backgroundColor = color.bg;
    notification.style.color = color.text;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Remover após 3 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// =======================================
// ANIMAÇÕES CSS ADICIONAIS
// =======================================

// Adicionar estilos de animação dinamicamente
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .donor-card {
        animation: fadeInUp 0.5s ease-out;
    }

    .product-item {
        animation: fadeInUp 0.3s ease-out;
    }

    @media (max-width: 768px) {
        @keyframes slideIn {
            from {
                transform: translateY(100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateY(0);
                opacity: 1;
            }
            to {
                transform: translateY(100px);
                opacity: 0;
            }
        }
    }
`;
document.head.appendChild(style);

// =======================================
// EXPORTAR PARA USO GLOBAL
// =======================================

window.store = {
    redirectToCheckout,
    showNotification
};
