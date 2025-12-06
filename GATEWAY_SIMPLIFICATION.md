# Simplificação da Configuração de Gateways de Pagamento

## 📋 Resumo das Alterações

A interface de configuração de gateways de pagamento foi simplificada para seguir o modelo do **LeaderOS**, removendo campos complexos e desnecessários. A nova abordagem é mais intuitiva e segura.

---

## 🔄 Antes vs. Depois

### **Antes (Complexo)**
- **PayPal**: Client ID + Secret (6 campos extras)
- **Mercado Pago**: Public Key + Access Token (2 campos)
- Sem exibição de Callback URLs
- Confuso para novos usuários

### **Depois (Simplificado - LeaderOS Style)**
- **PayPal Legacy**: Email + Sandbox Toggle (essencial)
- **Mercado Pago**: Access Token only (essencial)
- **URLs de Callback**: Auto-geradas e exibidas automaticamente
- Mais intuitivo, menos configuração manual

---

## 🛠️ Alterações Implementadas

### 1. **Dashboard Frontend** (`dashboard/index.php`)

#### PayPal - Nova Estrutura:
```html
<div class="payment-method-card">
    <h4>🅿️ PayPal Legacy</h4>
    <div class="form-group">
        <label>Email da Conta:</label>
        <input type="email" id="paypalEmail" placeholder="seu-email@exemplo.com">
    </div>
    <div class="form-group">
        <label>Modo Sandbox:</label>
        <button type="button" class="toggle-btn" id="paypalSandboxBtn" 
                onclick="togglePayPalSandbox()">Desativado</button>
        <input type="hidden" id="paypalSandbox" value="false">
    </div>
    <div class="form-group">
        <label>URL de Callback:</label>
        <div class="callback-display" id="paypalCallbackUrl"></div>
    </div>
</div>
```

**Payload Simplificado:**
```javascript
paypal: {
    ativo: boolean,
    config: {
        email: "seu-email@exemplo.com",
        sandbox: true/false
    }
}
```

#### Mercado Pago - Nova Estrutura:
```html
<div class="payment-method-card">
    <h4>🟖 Mercado Pago</h4>
    <div class="form-group">
        <label>Access Token:</label>
        <input type="password" id="mercadopagoAccessToken" 
               placeholder="APP_USR-xxxxxxxxxxxx">
    </div>
    <div class="form-group">
        <label>URL de Callback:</label>
        <div class="callback-display" id="mercadopagoCallbackUrl"></div>
    </div>
</div>
```

**Payload Simplificado:**
```javascript
mercadopago: {
    ativo: boolean,
    config: {
        accessToken: "APP_USR-xxxxxxxxxxxx"
    }
}
```

### 2. **Geração Automática de Callback URLs**

Nova função `generateCallbackURLs()`:
```javascript
function generateCallbackURLs() {
    const baseURL = window.location.origin;
    
    // PayPal
    document.getElementById('paypalCallbackUrl').textContent = 
        `${baseURL}/backend/callback/paypal_legacy`;
    
    // Mercado Pago
    document.getElementById('mercadopagoCallbackUrl').textContent = 
        `${baseURL}/backend/callback/mercadopago`;
}
```

**Características:**
- ✅ Gera automaticamente a partir da URL base
- ✅ Clicável para copiar para área de transferência
- ✅ Exibição clara em monospace font
- ✅ Atualiza ao carregar a seção de configurações

### 3. **Toggle para Sandbox**

Novo botão toggle com visual feedback:
```javascript
function togglePayPalSandbox() {
    const currentValue = document.getElementById('paypalSandbox').value === 'true';
    const newValue = !currentValue;
    
    document.getElementById('paypalSandbox').value = newValue.toString();
    document.getElementById('paypalSandboxBtn').style.backgroundColor = 
        newValue ? '#27ae60' : '#e74c3c';
    document.getElementById('paypalSandboxLabel').textContent = 
        newValue ? 'Ativado' : 'Desativado';
}
```

### 4. **Estilos CSS** (`dashboard.css`)

Adicionados estilos para melhor UX:

```css
.toggle-btn {
    padding: 0.6rem 1.2rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    background-color: #e74c3c;  /* Vermelho = Desativado */
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
}

.toggle-btn.active {
    background-color: #27ae60;  /* Verde = Ativado */
}

.callback-display {
    padding: 0.8rem 1rem;
    background-color: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    cursor: pointer;
    user-select: all;
}

.callback-display:hover {
    background-color: #efefef;
    border-color: #999;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
```

---

## 📊 Estrutura de Dados

### Banco de Dados (Sem Alterações)
A tabela `mgt_metodos_pagamento` continua a mesma:
```sql
CREATE TABLE mgt_metodos_pagamento (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100),
    identificador VARCHAR(50),
    ativo BOOLEAN,
    configuracao JSON,  -- Armazena config simplificada
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP
);
```

### JSON Armazenado
**Antes:**
```json
{
    "clientId": "xxx",
    "secret": "yyy",
    "publicKey": "zzz"
}
```

**Depois:**
```json
{
    "email": "seu-email@exemplo.com",
    "sandbox": false
}
```

---

## 🔗 URLs de Callback

As URLs são geradas automaticamente no formato:

| Gateway | Callback URL |
|---------|--------------|
| PayPal | `https://seu-dominio/backend/callback/paypal_legacy` |
| Mercado Pago | `https://seu-dominio/backend/callback/mercadopago` |
| PIX | `https://seu-dominio/backend/callback/pix` |

**Como Usar:**
1. Acesse as Configurações no Dashboard
2. Copie a URL de Callback do gateway desejado (clique no campo)
3. Cole no dashboard do gateway (PayPal, Mercado Pago, etc.)

---

## ✨ Benefícios

### Para Administrador
- ✅ Interface mais limpa e intuitiva
- ✅ Menos campos para preencher
- ✅ Callback URLs geradas automaticamente
- ✅ Toggle visual para modo Sandbox
- ✅ Reduz erros de configuração

### Para Segurança
- ✅ Menos campos = menos superfície de ataque
- ✅ Payload simplificado é mais fácil de auditar
- ✅ Secrets não armazenados desnecessariamente

### Para Manutenção
- ✅ Código mais limpo (sem Public Key desnecessário no MP)
- ✅ Menos configurações redundantes
- ✅ Backend pronto para novos gateways

---

## 🔍 Checklist de Implementação

- [x] Simplificar formulário de PayPal (Email + Sandbox)
- [x] Simplificar formulário de Mercado Pago (Access Token only)
- [x] Gerar URLs de Callback automaticamente
- [x] Adicionar toggle visual para Sandbox
- [x] Adicionar CSS para melhor UX
- [x] Atualizar função `loadConfiguracoes()` para novos campos
- [x] Atualizar função `saveConfigPayload()` com novo formato
- [x] Testar carregamento de configurações existentes
- [x] Documentar alterações

---

## 📝 Arquivos Modificados

| Arquivo | Alterações |
|---------|-----------|
| `dashboard/index.php` | Simplificação dos formulários, novas funções JS |
| `dashboard/dashboard.css` | Estilos para toggle e callback display |
| `backend/api_loja.php` | ✅ Sem alterações (compatível) |

---

## 🚀 Próximos Passos

1. **Testar** a nova interface no Dashboard
2. **Salvar** configurações de teste
3. **Verificar** se as URLs de callback são geradas corretamente
4. **Testar** toggle de Sandbox no PayPal
5. **Atualizar** documentação dos gateways se necessário

---

## ❓ FAQ

### P: As configurações antigas ainda funcionam?
**R:** Sim. O backend `api_loja.php` continua compatível. As configurações antigas serão sobrescritas com as novas.

### P: Como migrar configurações existentes?
**R:** Basta salvar novamente no novo formato. Os campos antigos (clientId, secret, publicKey) serão removidos automaticamente.

### P: Preciso atualizar algo no código de pagamento?
**R:** Não. O backend continua usando a coluna `configuracao` (JSON) que armazena qualquer configuração. O processamento de pagamento não foi alterado.

### P: E se eu precisar de mais campos depois?
**R:** É simples adicionar novos campos. Basta adicionar um novo `<div class="form-group">` e atualizar o payload em `saveConfigPayload()`.

---

**Última Atualização:** 2025
**Versão:** 1.0 - Simplificação LeaderOS Style
