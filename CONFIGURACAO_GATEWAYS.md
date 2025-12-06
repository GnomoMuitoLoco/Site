# Guia de Configuração de Gateways de Pagamento

## 🚀 Primeiros Passos

### 1. PayPal Setup

#### Obter Credenciais
1. Acesse https://www.paypal.com/signin
2. Vá para Ferramentas → Minhas credenciais REST
3. Copie **Client ID** e **Secret**

#### Configurar Webhook
1. Em Minhas credenciais, clique em **Gerenciar webhooks**
2. URL do webhook: `https://seudominio.com/backend/webhooks/paypal-webhook.php`
3. Selecione eventos:
   - `CHECKOUT.ORDER.APPROVED`
   - `CHECKOUT.ORDER.COMPLETED`
   - `CHECKOUT.ORDER.VOIDED`

#### Configurar no Banco
```sql
INSERT INTO mgt_metodos_pagamento (tipo, nome, config, ativo, producao) VALUES (
    'paypal',
    'PayPal',
    '{"api_key":"seu_client_id","api_secret":"seu_secret","producao":false}',
    TRUE,
    FALSE
);
```

#### Testar com Sandbox
- PayPal sandbox: https://sandbox.paypal.com
- Contas de teste: https://www.paypal.com/signin/credentials

---

### 2. Mercado Pago Setup

#### Obter Credenciais
1. Acesse https://www.mercadopago.com/mla/account/credentials
2. Copie **Access Token** e **Public Key**

#### Configurar Webhook
1. Em Configurações → Webhooks
2. URL: `https://seudominio.com/backend/webhooks/mercadopago-webhook.php`
3. Eventos:
   - `payment.created`
   - `payment.updated`

#### Configurar no Banco
```sql
INSERT INTO mgt_metodos_pagamento (tipo, nome, config, ativo, producao) VALUES (
    'mercadopago',
    'Mercado Pago',
    '{"api_key":"seu_access_token","public_key":"sua_public_key","producao":false}',
    TRUE,
    FALSE
);
```

#### Testar
- Sandbox de testes: https://www.mercadopago.com.br/testnewcard
- Cartão de teste: 4111111111111111 (exp: 11/25, CVC: 123)

---

### 3. PIX Setup

#### Configurar com Seu Banco
1. Acesse painel do seu banco
2. Gere uma chave PIX (email, telefone, CPF ou UUID)
3. Configure webhook do banco para:
   ```
   https://seudominio.com/backend/webhooks/pix-webhook.php
   ```

#### Configurar no Banco (DB)
```sql
INSERT INTO mgt_metodos_pagamento (tipo, nome, config, ativo, producao) VALUES (
    'pix',
    'PIX',
    '{"pix_key":"seu_email@exemplo.com","beneficiary":"Seu Nome Completo"}',
    TRUE,
    FALSE
);
```

#### Tipos de Chave Suportados
- Email: `usuario@exemplo.com`
- Telefone: `11999999999`
- CPF: `12345678900`
- CNPJ: `12345678000100`
- UUID: Gere via `php -r 'echo bin2hex(random_bytes(16));'`

#### Testar
- Use o próprio PIX para enviar dinheiro
- Ou simule via webhook manual com curl:
```bash
curl -X POST https://seudominio.com/backend/webhooks/pix-webhook.php \
  -H "Content-Type: application/json" \
  -d '{
    "transaction_id": 123,
    "status": "PAID",
    "amount": 50.00
  }'
```

---

## 🔧 Variáveis de Ambiente

Crie um arquivo `.env` ou adicione ao seu `config.php`:

```php
<?php
// PayPal
define('PAYPAL_CLIENT_ID', 'seu_client_id');
define('PAYPAL_SECRET', 'seu_secret');
define('PAYPAL_MODE', 'sandbox'); // 'sandbox' ou 'production'

// Mercado Pago
define('MERCADOPAGO_TOKEN', 'seu_access_token');
define('MERCADOPAGO_PUBLIC_KEY', 'sua_public_key');
define('MERCADOPAGO_MODE', 'sandbox');

// PIX
define('PIX_KEY', 'seu_email@exemplo.com');
define('PIX_BENEFICIARY', 'Seu Nome Completo');

// Webhooks
define('WEBHOOK_SECRET_PAYPAL', 'sua_chave_secreta');
define('WEBHOOK_SECRET_MERCADOPAGO', 'sua_chave_secreta');
define('WEBHOOK_SECRET_PIX', 'sua_chave_secreta');

// URLs
define('SITE_URL', 'https://seudominio.com');
define('RETURN_URL', SITE_URL . '/checkout-success.html');
define('CANCEL_URL', SITE_URL . '/checkout-cancel.html');
```

---

## 📝 Estrutura de Pastas Recomendada

```
/backend
├── config/
│   ├── database.php
│   └── config.php
├── gateways/
│   ├── PayPalGateway.php
│   ├── MercadoPagoGateway.php
│   └── PIXGateway.php
├── webhooks/
│   ├── paypal-webhook.php
│   ├── mercadopago-webhook.php
│   └── pix-webhook.php
├── logs/
│   ├── paypal_webhook_YYYY-MM-DD.log
│   ├── mercadopago_webhook_YYYY-MM-DD.log
│   └── pix_webhook_YYYY-MM-DD.log
├── PaymentGateway.php
├── PaymentManager.php
├── ModWebSocketClient.php
├── process-payment.php
└── check-pix-status.php

/frontend
├── checkout.html
├── checkout-success.html
├── checkout-cancel.html
└── checkout-pix-waiting.html
```

---

## 🧪 Teste Completo (Passo a Passo)

### 1. Testar PIX (Mais simples)

**Simulando webhook:**
```bash
curl -X POST http://localhost/backend/webhooks/pix-webhook.php \
  -H "Content-Type: application/json" \
  -d '{
    "transaction_id": "123",
    "status": "PAID",
    "amount": 50.00,
    "pix_key": "seu_email@exemplo.com"
  }'
```

**Resultado esperado:**
- Transação no banco atualizada para `aprovado`
- Log em `/backend/logs/pix_webhook_YYYY-MM-DD.log`
- Página de sucesso exibe informações corretas

### 2. Testar PayPal

**Sandbox Payment:**
1. Acesse seu PayPal sandbox
2. Crie conta de comprador de teste
3. Complete fluxo de aprovação
4. Webhook é acionado automaticamente

**Verificar webhook:**
```bash
# No PayPal dashboard → Minhas credenciais → Gerenciar webhooks
# Você verá histórico de webhooks enviados
```

### 3. Testar Mercado Pago

**Cartões de Teste:**
```
Cartão: 4111 1111 1111 1111
Exp: 11/25
CVC: 123
Titular: APRO
```

**Simular webhook:**
```bash
curl -X POST http://localhost/backend/webhooks/mercadopago-webhook.php \
  -H "Content-Type: application/json" \
  -d '{
    "type": "payment",
    "id": "123456789",
    "status": "approved",
    "external_reference": "123",
    "amount": 50.00
  }'
```

---

## 🐛 Troubleshooting

### PayPal - "Invalid API signature"
- **Causa:** Client ID ou Secret incorretos
- **Solução:** Verificar credenciais no PayPal dashboard

### Mercado Pago - "Invalid access token"
- **Causa:** Access token expirado ou incorreto
- **Solução:** Regenerar em conta.mercadopago.com

### PIX - "Webhook não recebe confirmação"
- **Causa:** Banco não sabe para onde enviar
- **Solução:** 
  1. Verificar URL do webhook no painel do banco
  2. Confirmar que domínio é acessível externamente
  3. Testar com ngrok se for localhost: `ngrok http 80`

### "Transação não encontrada"
- **Causa:** transaction_id não bate com banco
- **Solução:** Verificar se o ID foi salvo corretamente em `process-payment.php`

### QR Code PIX não aparece
- **Causa:** Erro ao gerar payload
- **Solução:** 
  1. Verificar logs em `/backend/logs/`
  2. Testar PIXGateway::generatePixPayload() isoladamente
  3. Validar chave PIX com PIXGateway::validatePixKey()

---

## 📊 Monitoramento

### Verificar Logs
```bash
# PayPal
tail -f /backend/logs/paypal_webhook_2025-01-15.log

# Mercado Pago
tail -f /backend/logs/mercadopago_webhook_2025-01-15.log

# PIX
tail -f /backend/logs/pix_webhook_2025-01-15.log
```

### Monitorar Transações
```sql
-- Últimas transações
SELECT * FROM mgt_transacoes 
ORDER BY data_criacao DESC 
LIMIT 10;

-- Transações com erro
SELECT * FROM mgt_transacoes 
WHERE status NOT IN ('aprovado', 'reembolsado')
ORDER BY data_criacao DESC;

-- Total de vendas por gateway
SELECT 
  metodo_pagamento,
  COUNT(*) as total,
  SUM(valor_total) as vendas
FROM mgt_transacoes
WHERE status = 'aprovado'
GROUP BY metodo_pagamento;
```

---

## 🔐 Segurança em Produção

### Checklist
- [ ] HTTPS obrigatório (gerar SSL com Let's Encrypt)
- [ ] Chaves de API armazenadas em `.env` (nunca em git)
- [ ] Validar webhook signatures
- [ ] Rate limiting em `/api/process-payment`
- [ ] Logs salvos fora da raiz web
- [ ] Database backups automáticos
- [ ] Monitoramento de webhooks (alertas se falhar)
- [ ] Testes de carga (simular 100+ transações/minuto)

### Exemplo de Rate Limiting (PHP)
```php
// Antes de processar pagamento
$ip = $_SERVER['REMOTE_ADDR'];
$key = "payment_$ip";

// Máximo 10 tentativas por minuto
if (rateLimit($key, 10, 60)) {
    http_response_code(429);
    echo json_encode(['error' => 'Muitas requisições']);
    exit;
}
```

---

## 📞 Suporte

**Documentações Oficiais:**
- PayPal: https://developer.paypal.com/docs/checkout/
- Mercado Pago: https://developers.mercadopago.com/
- PIX (Banco Central): https://www.bcb.gov.br/pix

**Contato do servidor:**
- Discord: discord.gg/magnatas
- Email: suporte@magnatas.com

---

**Versão:** 1.0.0
**Última atualização:** 2025-01-15
