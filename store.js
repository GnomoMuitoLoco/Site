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
// MODAL DE COMPRA
// =======================================

let currentPurchaseServer = null;

function openPurchaseModal(server) {
    currentPurchaseServer = server;
    
    const modal = document.getElementById('purchaseModal');
    modal.classList.add('active');
    
    // Atualizar o select com o servidor
    const serverSelect = document.getElementById('modalServerSelect');
    const serverNames = {
        'mgt': 'Servidor Magnatas',
        'atm10': 'All The Mods 10',
        'atm10tts': 'ATM10 To The Sky'
    };
    
    serverSelect.innerHTML = `<option value="${server}" selected>${serverNames[server]}</option>`;
    serverSelect.disabled = false;
    
    // Focar no input de nick
    setTimeout(() => {
        document.getElementById('modalNickInput').focus();
    }, 100);
    
    // Fechar com ESC
    document.addEventListener('keydown', closeModalOnEsc);
}

function closePurchaseModal() {
    const modal = document.getElementById('purchaseModal');
    modal.classList.remove('active');
    
    // Limpar formulário
    document.getElementById('modalNickInput').value = '';
    document.getElementById('modalQuantity').value = '0';
    document.getElementById('quantityDisplay').textContent = 'Selecione uma quantidade';
    
    // Remover classe active dos botões de quantidade
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Remover listener de ESC
    document.removeEventListener('keydown', closeModalOnEsc);
}

function closeModalOnEsc(e) {
    if (e.key === 'Escape') {
        closePurchaseModal();
    }
}

function selectQuantity(amount) {
    // Atualizar input hidden
    document.getElementById('modalQuantity').value = amount;
    
    // Atualizar display
    const quantityLabel = {
        100: '100 MGT-Cash',
        250: '250 MGT-Cash',
        700: '700 MGT-Cash',
        1500: '1.500 MGT-Cash'
    };
    
    document.getElementById('quantityDisplay').textContent = `Selecionado: ${quantityLabel[amount]}`;
    
    // Atualizar classe active dos botões
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.closest('.qty-btn').classList.add('active');
}

function proceedToCheckout() {
    const nick = document.getElementById('modalNickInput').value.trim();
    const quantity = parseInt(document.getElementById('modalQuantity').value);
    
    // Validações
    if (!nick) {
        showNotification('Por favor, digite seu nick!', 'error');
        return;
    }
    
    if (!/^[a-zA-Z0-9_]+$/.test(nick)) {
        showNotification('Nick contém caracteres inválidos!', 'error');
        return;
    }
    
    if (nick.length < 3 || nick.length > 16) {
        showNotification('Nick deve ter entre 3 e 16 caracteres!', 'error');
        return;
    }
    
    if (quantity === 0) {
        showNotification('Selecione uma quantidade!', 'error');
        return;
    }
    
    // Preparar dados para checkout
    const checkoutData = {
        nick: nick,
        server: currentPurchaseServer,
        quantity: quantity,
        timestamp: new Date().toISOString()
    };
    
    // Salvar na sessão
    sessionStorage.setItem('checkoutData', JSON.stringify(checkoutData));
    
    // Fechar modal
    closePurchaseModal();
    
    // Redirecionar para checkout
    showNotification(`Redirecionando para checkout de ${quantity} MGT-Cash...`, 'info');
    setTimeout(() => {
        window.location.href = '/checkout.html';
    }, 1500);
}

// =======================================
// CARREGAMENTO DE DADOS - DOADORES
// ======================================= */

function loadRecentDonorsData() {
    // Dados dos doadores (stub - será integrado com backend)
    const donors = [
        { rank: '🥇', name: 'GnomoMuitoLouco', date: 'Há 2 horas' },
        { rank: '🥈', name: 'PlayerMaster123', date: 'Há 4 horas' },
        { rank: '🥉', name: 'MineroCobalt', date: 'Há 6 horas' },
        { rank: '⭐', name: 'Construtor_RX', date: 'Há 8 horas' },
        { rank: '⭐', name: 'Explorer_Sky', date: 'Há 10 horas' },
        { rank: '⭐', name: 'PvPDragon99', date: 'Há 12 horas' }
    ];

    // Top doador
    const topDonor = donors[0];
    
    // Carregar avatar do top doador
    const topDonorAvatar = document.getElementById('topDonorAvatar');
    topDonorAvatar.src = `https://minotar.net/bust/${topDonor.name}/100.png`;
    topDonorAvatar.onerror = function() {
        this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzJhMmEyYSIvPjwvc3ZnPg==';
    };
    
    document.getElementById('topDonorName').textContent = topDonor.name;
    
    // Preencher tabela de doadores (sem posição)
    const tableBody = document.getElementById('donorsTableBody');
    tableBody.innerHTML = donors.map((donor, index) => `
        <tr>
            <td>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div class="donor-avatar-mini">
                        <img src="https://minotar.net/avatar/${donor.name}/40" alt="${donor.name}" class="avatar-img" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiBmaWxsPSIjMmEyYTJhIi8+PC9zdmc+'">
                    </div>
                    <span class="donor-name">${donor.name}</span>
                </div>
            </td>
            <td class="donor-date">${donor.date}</td>
        </tr>
    `).join('');
}

function loadCommunityGoalData() {
    // Apenas atualizar a porcentagem da barra de progresso
    const goalData = {
        percentage: 49
    };

    // Atualizar barra de progresso
    const progressFill = document.querySelector('.progress-fill');
    const progressPercentage = document.querySelector('.progress-percentage');
    
    if (progressFill && progressPercentage) {
        progressFill.style.width = `${goalData.percentage}%`;
        progressPercentage.textContent = `${goalData.percentage}% atingido`;
    }
}

// =======================================
// UTILITÁRIOS
// =======================================

function setupEventListeners() {
    // Fechar modal ao clicar fora
    const modal = document.getElementById('purchaseModal');
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closePurchaseModal();
        }
    });
    
    // Enter no campo de nick
    const nickInput = document.getElementById('modalNickInput');
    if (nickInput) {
        nickInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                proceedToCheckout();
            }
        });
    }
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
    openPurchaseModal,
    closePurchaseModal,
    selectQuantity,
    proceedToCheckout,
    showNotification
};
