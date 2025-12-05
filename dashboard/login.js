/**
 * Login JavaScript
 * Arquivo: dashboard/login.js
 */

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const loginMessage = document.getElementById('login-message');

    // Gerar CSRF Token (simulado - em produção, vem do servidor)
    const csrfToken = generateCSRFToken();
    document.querySelector('input[name="csrf_token"]').value = csrfToken;

    // Validação em tempo real
    usernameInput.addEventListener('blur', function() {
        const error = document.getElementById('username-error');
        if (this.value.trim() === '') {
            error.textContent = 'Usuário é obrigatório';
            error.classList.add('show');
        } else {
            error.classList.remove('show');
        }
    });

    passwordInput.addEventListener('blur', function() {
        const error = document.getElementById('password-error');
        if (this.value === '') {
            error.textContent = 'Senha é obrigatória';
            error.classList.add('show');
        } else if (this.value.length < 3) {
            error.textContent = 'Senha muito curta';
            error.classList.add('show');
        } else {
            error.classList.remove('show');
        }
    });

    // Submissão do formulário
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validação final
        let valid = true;
        const username = usernameInput.value.trim();
        const password = passwordInput.value;

        if (!username) {
            showError('Usuário é obrigatório');
            valid = false;
        }

        if (!password) {
            showError('Senha é obrigatória');
            valid = false;
        }

        if (!valid) return;

        // Desabilitar botão durante envio
        const submitBtn = loginForm.querySelector('.btn-login');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Entrando...';

        // Simular envio (em produção, o formulário será enviado normalmente)
        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            
            // Permitir submissão normal
            loginForm.submit();
        }, 500);
    });

    // Mostrar erro
    function showError(message) {
        loginMessage.textContent = message;
        loginMessage.classList.add('show', 'error');
        loginMessage.classList.remove('success');
    }

    // Mostrar sucesso
    function showSuccess(message) {
        loginMessage.textContent = message;
        loginMessage.classList.add('show', 'success');
        loginMessage.classList.remove('error');
    }

    // Verificar se há mensagem de erro da sessão
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('error')) {
        showError(decodeURIComponent(urlParams.get('error')));
    }
});

/**
 * Gerar CSRF Token (simulado)
 */
function generateCSRFToken() {
    // Em produção, isso vem do servidor
    return Math.random().toString(36).substr(2) + Date.now().toString(36);
}

/**
 * Efeito de digitação no title
 */
(function() {
    const text = 'Servidor Magnatas - Entrando...';
    let index = 0;
    const originalTitle = document.title;

    function typeTitle() {
        if (index < text.length) {
            document.title = text.substr(0, index + 1);
            index++;
            setTimeout(typeTitle, 50);
        } else {
            setTimeout(resetTitle, 2000);
        }
    }

    function resetTitle() {
        document.title = originalTitle;
    }

    // Iniciar animação ao focar no campo de senha
    document.getElementById('password').addEventListener('focus', function() {
        if (index === 0) {
            // typeTitle();
        }
    });
})();
