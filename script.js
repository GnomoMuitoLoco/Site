// ========================================
// NAVBAR MOBILE TOGGLE
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (navToggle) {
        navToggle.addEventListener('click', function() {
            navToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
    }

    // Fechar menu ao clicar em um link
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            navToggle.classList.remove('active');
            navMenu.classList.remove('active');
        });
    });
});

// ========================================
// NAVBAR SCROLL EFFECT
// ========================================

window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// ========================================
// HERO PARALLAX EFFECT
// ========================================

window.addEventListener('scroll', function() {
    const hero = document.querySelector('.hero');
    if (!hero) return;
    
    const scrollPosition = window.pageYOffset;
    const heroHeight = hero.offsetHeight;
    
    // Aplicar efeito parallax apenas enquanto a hero section está visível
    if (scrollPosition < heroHeight) {
        const parallaxSpeed = 0.5;
        const yPos = scrollPosition * parallaxSpeed;
        
        // Atualizar a posição do background
        const heroBackground = hero.querySelector('::before');
        if (heroBackground) {
            hero.style.setProperty('--parallax-y', `${yPos}px`);
        }
        
        // Aplicar transformação via CSS custom property
        document.documentElement.style.setProperty('--hero-parallax', `translateY(${yPos}px)`);
    }
});

// ========================================
// SMOOTH SCROLL FOR ANCHOR LINKS
// ========================================

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href !== '') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});

// ========================================
// COPY SERVER IP TO CLIPBOARD
// ========================================

const ipAddress = document.querySelector('.ip-address');
if (ipAddress) {
    ipAddress.style.cursor = 'pointer';
    ipAddress.title = 'Clique para copiar';
    
    ipAddress.addEventListener('click', function() {
        const ip = this.textContent;
        
        // Criar elemento temporário para copiar
        const tempInput = document.createElement('input');
        tempInput.value = ip;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        
        // Feedback visual
        const originalText = this.textContent;
        this.textContent = 'IP Copiado!';
        this.style.color = '#00ff00';
        
        setTimeout(() => {
            this.textContent = originalText;
            this.style.color = '';
        }, 2000);
    });
}

// ========================================
// ANIMATE ELEMENTS ON SCROLL
// ========================================

const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Animar cards de features, stats, etc
const animateElements = document.querySelectorAll('.feature-card, .stat-item, .resource-item, .social-card, .event-card, .faq-item');
animateElements.forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(30px)';
    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(el);
});

// ========================================
// ANIMATE NUMBERS (STATS SECTION)
// ========================================

function animateNumber(element, target) {
    let current = 0;
    const increment = target / 50;
    const isPercent = target === 99.9;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        
        if (isPercent) {
            element.textContent = current.toFixed(1) + '%';
        } else if (target >= 1000) {
            element.textContent = Math.floor(current).toLocaleString('pt-BR') + '+';
        } else {
            element.textContent = Math.floor(current) + '+';
        }
    }, 30);
}

const statNumbers = document.querySelectorAll('.stat-number');
const statsSection = document.querySelector('.stats');

if (statsSection) {
    const statsObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.classList.contains('animated')) {
                entry.target.classList.add('animated');
                
                statNumbers.forEach(stat => {
                    const text = stat.textContent;
                    if (text.includes('%')) {
                        animateNumber(stat, 99.9);
                    } else if (text.includes('5000')) {
                        animateNumber(stat, 5000);
                    } else if (text.includes('50')) {
                        animateNumber(stat, 50);
                    } else if (text === '24/7') {
                        // Não animar o 24/7
                    }
                });
            }
        });
    }, { threshold: 0.3 });
    
    statsObserver.observe(statsSection);
}

// ========================================
// FORM VALIDATION AND SUBMISSION
// ========================================

const contactForm = document.querySelector('.contact-form');
if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const subject = document.getElementById('subject').value;
        const message = document.getElementById('message').value;
        
        // Validação simples
        if (!name || !email || !subject || !message) {
            alert('Por favor, preencha todos os campos!');
            return;
        }
        
        // Simular envio
        const submitBtn = contactForm.querySelector('.btn');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Enviando...';
        submitBtn.disabled = true;
        
        setTimeout(() => {
            submitBtn.textContent = 'Mensagem Enviada!';
            submitBtn.style.backgroundColor = '#00ff00';
            
            setTimeout(() => {
                contactForm.reset();
                submitBtn.textContent = originalText;
                submitBtn.style.backgroundColor = '';
                submitBtn.disabled = false;
            }, 3000);
        }, 1500);
    });
}

// ========================================
// BUTTON RIPPLE EFFECT
// ========================================

document.querySelectorAll('.btn').forEach(button => {
    button.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');
        
        this.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
});

// CSS para o efeito ripple (será adicionado dinamicamente)
const style = document.createElement('style');
style.textContent = `
    .btn {
        position: relative;
        overflow: hidden;
    }
    
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// ========================================
// LOADING ANIMATION
// ========================================

window.addEventListener('load', function() {
    document.body.style.opacity = '0';
    setTimeout(() => {
        document.body.style.transition = 'opacity 0.3s ease';
        document.body.style.opacity = '1';
    }, 100);
});

// ========================================
// DISCORD DATA FOR STATS SECTION
// ========================================

// Carregar dados do Discord ao iniciar (novo: atualizar stat na página principal)
async function loadDiscordDataForStats() {
    const discordId = '616378717547266058';
    const membersStat = document.getElementById('discord-members-stat');
    
    if (!membersStat) return;

    try {
        const response = await fetch(`https://discord.com/api/guilds/${discordId}/widget.json`);
        
        if (response.ok) {
            const data = await response.json();
            const memberCount = data.members?.length || 0;
            // Usar um número aproximado baseado no widget
            membersStat.textContent = (memberCount * 10).toLocaleString('pt-BR') + '+';
        } else {
            throw new Error('Widget not available');
        }
    } catch (error) {
        membersStat.textContent = '5000+';
    }
}

// Carregar dados do Discord ao iniciar
if (document.getElementById('discord-members-stat')) {
    loadDiscordDataForStats();
}

// ========================================
// SERVER STATUS CHECK (NEW INTEGRATED LAYOUT)
// ========================================

async function checkServerStatus(serverIP, elementId) {
    const statusCard = document.getElementById(elementId);
    if (!statusCard) return;

    const indicator = statusCard.querySelector('.server-indicator');
    const infoElement = statusCard.querySelector('.server-status-info');

    try {
        // Tentando múltiplas APIs como fallback
        const apis = [
            `https://api.mcsrvstat.us/3/${serverIP}`,
            `https://api.mcstatus.io/v2/status/java/${serverIP}`
        ];

        let data = null;
        
        // Tenta a primeira API (mcsrvstat)
        try {
            const response = await fetch(apis[0]);
            if (response.ok) {
                data = await response.json();
                
                if (data.online) {
                    indicator.className = 'server-indicator online';
                    const players = data.players?.online || 0;
                    const maxPlayers = data.players?.max || 0;
                    infoElement.textContent = `${players}/${maxPlayers} jogadores online`;
                } else {
                    throw new Error('Server offline');
                }
            }
        } catch (e) {
            // Fallback para a segunda API
            const response = await fetch(apis[1]);
            if (response.ok) {
                data = await response.json();
                
                if (data.online) {
                    indicator.className = 'server-indicator online';
                    const players = data.players?.online || 0;
                    const maxPlayers = data.players?.max || 0;
                    infoElement.textContent = `${players}/${maxPlayers} jogadores online`;
                } else {
                    throw new Error('Server offline');
                }
            }
        }
    } catch (error) {
        indicator.className = 'server-indicator offline';
        infoElement.textContent = 'Servidor offline ou em manutenção';
    }
}

// ========================================
// SERVER STATUS CHECK (SHOWCASE VERSION)
// ========================================

async function checkShowcaseServerStatus(serverIP, indicatorId, statusTextId) {
    const indicator = document.getElementById(indicatorId);
    const statusText = document.getElementById(statusTextId);
    
    if (!indicator || !statusText) return;

    try {
        const response = await fetch(`https://api.mcsrvstat.us/3/${serverIP}`);
        if (response.ok) {
            const data = await response.json();
            
            if (data.online) {
                indicator.className = 'server-indicator online';
                const players = data.players?.online || 0;
                const maxPlayers = data.players?.max || 0;
                statusText.textContent = `${players}/${maxPlayers} jogadores online`;
            } else {
                throw new Error('Server offline');
            }
        }
    } catch (error) {
        indicator.className = 'server-indicator offline';
        statusText.textContent = 'Offline ou em manutenção';
    }
}

// Verificar status dos servidores ao carregar a página
if (document.getElementById('status-atm10')) {
    checkServerStatus('atm10.servidormagnatas.com.br', 'status-atm10');
    checkServerStatus('atm10tts.servidormagnatas.com.br', 'status-atm10tts');
    checkServerStatus('mgt.servidormagnatas.com.br', 'status-mgt');

    // Atualizar status a cada 60 segundos
    setInterval(() => {
        checkServerStatus('atm10.servidormagnatas.com.br', 'status-atm10');
        checkServerStatus('atm10tts.servidormagnatas.com.br', 'status-atm10tts');
        checkServerStatus('mgt.servidormagnatas.com.br', 'status-mgt');
    }, 60000);
}

// Verificar status dos servidores showcase
if (document.getElementById('indicator-mgt')) {
    checkShowcaseServerStatus('mgt.servidormagnatas.com.br', 'indicator-mgt', 'status-text-mgt');
    checkShowcaseServerStatus('atm10.servidormagnatas.com.br', 'indicator-atm10', 'status-text-atm10');
    checkShowcaseServerStatus('atm10tts.servidormagnatas.com.br', 'indicator-atm10tts', 'status-text-atm10tts');

    // Atualizar status a cada 60 segundos
    setInterval(() => {
        checkShowcaseServerStatus('mgt.servidormagnatas.com.br', 'indicator-mgt', 'status-text-mgt');
        checkShowcaseServerStatus('atm10.servidormagnatas.com.br', 'indicator-atm10', 'status-text-atm10');
        checkShowcaseServerStatus('atm10tts.servidormagnatas.com.br', 'indicator-atm10tts', 'status-text-atm10tts');
    }, 60000);
}

// ========================================
// COPY IP FUNCTION
// ========================================

function copyIP(ip) {
    // Criar elemento temporário para copiar
    const tempInput = document.createElement('input');
    tempInput.value = ip;
    document.body.appendChild(tempInput);
    tempInput.select();
    
    try {
        document.execCommand('copy');
        
        // Feedback visual
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = '✓ IP Copiado!';
        button.style.backgroundColor = '#00ff00';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.backgroundColor = '';
        }, 2000);
    } catch (err) {
        alert('Erro ao copiar IP. Por favor, copie manualmente: ' + ip);
    }
    
    document.body.removeChild(tempInput);
}

// Disponibilizar função globalmente
window.copyIP = copyIP;

console.log('🎮 Servidor Magnatas - Desenvolvido com ❤️');
