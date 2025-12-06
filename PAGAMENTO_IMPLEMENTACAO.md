# Implementação de Gateways de Pagamento - Resumo Técnico

## 📋 Arquivos Criados

### Backend - Gateways de Pagamento

#### 1. **backend/PaymentGateway.php** (Classe Abstrata - 150 linhas)
**Propósito:** Define interface comum para todos os gateways

**Métodos:**
- `validateConfig()` - Valida configuração do gateway
- `process($amount, $description, $metadata)` - Processa pagamento
- `getStatus($transactionId)` - Verifica status
- `handleWebhook($data)` - Processa webhook
- `makeRequest($method, $url, $data, $headers)` - Cliente HTTP
- `log($message)` - Logging com timestamp
- `generateSignature($data, $secret)` - HMAC-SHA256

#### 2. **backend/gateways/PayPalGateway.php** (280 linhas)
**Fluxo:**
1. Obter token de acesso via OAuth 2.0
2. Criar ordem com URLs de retorno
3. Retornar link de aprovação
4. Capturar pagamento após aprovação
5. Processar webhooks

**Métodos Principais:**
- `process()` - Cria ordem e retorna approval_url
- `getAccessToken()` - OAuth 2.0 token
- `capturePayment()` - Captura ordem aprovada
- `getStatus()` - Verifica status da ordem
- `handleWebhook()` - Processa eventos PayPal

**Configuração Necessária:**
```json
{
  "api_key": "seu_client_id",
  "api_secret": "seu_secret",
  "producao": false
}
```

#### 3. **backend/gateways/MercadoPagoGateway.php** (250 linhas)
**Fluxo:**
1. Criar preferência de pagamento
2. Retornar init_point para redirecionamento
3. Rastrear via external_reference
4. Processar webhooks de status

**Métodos Principais:**
- `process()` - Cria preferência, retorna init_point
- `getStatus()` - Busca por external_reference
- `handleWebhook()` - Processa notificações

**Configuração Necessária:**
```json
{
  "api_key": "seu_access_token",
  "public_key": "sua_public_key",
  "producao": false
}
```

#### 4. **backend/gateways/PIXGateway.php** (350 linhas)
**Características:**
- Geração de payload EMV (Maestro standard)
- Cálculo de CRC16 (RFC 3961)
- Geração de QR Code via Google Charts
- Validação de chave PIX

**Métodos Principais:**
- `process()` - Gera payload + QR code
- `generatePixPayload()` - EMV payload
- `calculateCRC16()` - Checksum
- `generateQRCode()` - QR via Google Charts
- `validatePixKey()` - Valida formato

**Tipos de Chave PIX Suportados:**
- Email
- Telefone (11 dígitos)
- CPF (11 dígitos)
- CNPJ (14 dígitos)
- UUID

**Configuração Necessária:**
```json
{
  "pix_key": "seu_email@exemplo.com",
  "beneficiary": "Nome Completo",
  "producao": false
}
```

#### 5. **backend/PaymentManager.php** (200 linhas)
**Propósito:** Orquestrador central de gateways

**Funcionalidades:**
- Inicializa gateways do banco de dados
- Roteia requisições para gateway apropriado
- Gerencia status de transações
- Processa webhooks
- Atualiza banco de dados

**Métodos:**
- `processPayment($method, $amount, $description, $metadata)`
- `checkPaymentStatus($method, $transactionId)`
- `handleWebhook($method, $payload)`
- `capturePayment($transactionId, $amount)`
- `getAvailableGateways()`
- `initializeGateways()`

**Fluxo de Integração:**
```
checkout.html → process-payment.php → PaymentManager → Gateway específico → Resposta
```

#### 6. **backend/ModWebSocketClient.php** (280 linhas)
**Propósito:** Comunicação real-time com mod do Minecraft

**Implementação:**
- RFC 6455 WebSocket completo
- Frame creation com masking
- Autenticação via API key
- Gerenciamento de conexão

**Métodos:**
- `connect()` - Estabelece conexão
- `sendMessage($data)` - Envia mensagem JSON
- `notifyPlayerJoin($playerName, $uuid)` - Notifica entrada
- `notifyPurchaseDelivered($transactionId, $playerName, $amount)` - Notifica entrega
- `disconnect()` - Fecha conexão limpa

### Backend - Endpoints API

#### 7. **backend/process-payment.php** (POST /api/process-payment)
**Fluxo:**
1. Valida dados do checkout
2. Cria transação no banco
3. Chama PaymentManager
4. Retorna URL/dados específicos do gateway

**Entrada:**
```json
{
  "jogador_nick": "PlayerNick",
  "jogador_email": "email@exemplo.com",
  "servidor_id": 1,
  "produto_id": 1,
  "metodo_pagamento": "pix",
  "amount": 50.00,
  "description": "MGT-Cash 1500"
}
```

**Saída (PIX):**
```json
{
  "success": true,
  "transaction_id": 123,
  "data": {
    "qr_code": "data:image/png;base64,...",
    "pix_key": "email@exemplo.com",
    "transaction_id": 123
  }
}
```

#### 8. **backend/check-pix-status.php** (POST /api/check-pix-status)
**Propósito:** Polling para status de pagamento PIX

**Entrada:**
```json
{
  "transaction_id": 123
}
```

**Saída:**
```json
{
  "success": true,
  "status": "approved",
  "order_id": 123,
  "product": 1,
  "player": "PlayerNick",
  "amount": "R$ 50,00"
}
```

### Backend - Webhooks

#### 9. **backend/webhooks/paypal-webhook.php**
**Eventos Processados:**
- `CHECKOUT.ORDER.APPROVED` - Pagamento aprovado
- `CHECKOUT.ORDER.COMPLETED` - Pagamento completado/capturado
- `CHECKOUT.ORDER.VOIDED` - Pagamento cancelado

#### 10. **backend/webhooks/mercadopago-webhook.php**
**Mapping de Status:**
- `approved` → `aprovado`
- `pending` → `processando`
- `rejected` → `recusado`
- `cancelled` → `cancelado`

#### 11. **backend/webhooks/pix-webhook.php**
**Flexível para diferentes provedores:**
- Suporta diferentes formatos de webhook
- Validação de assinatura do banco
- Logging detalhado

### Frontend - Páginas

#### 12. **checkout.html** (Atualizado)
**Integração:**
- Chama `process-payment.php` via AJAX
- Redireciona para gateway apropriado:
  - PayPal: `approval_url`
  - Mercado Pago: `init_point`
  - PIX: `checkout-pix-waiting.html`

#### 13. **checkout-success.html** (300 linhas)
**Recursos:**
- Exibe informações do pedido
- Timeline com status da entrega
- Auto-atualiza após 3 segundos
- Animações suaves
- Responsivo (mobile-friendly)

**Parâmetros de URL:**
```
?order=PED-000001&product=1&player=PlayerNick&amount=R$%2050,00
```

#### 14. **checkout-cancel.html** (Novo)
**Exibe:**
- Mensagem de pagamento cancelado
- Motivo do cancelamento
- Opções: Tentar Novamente, Voltar à Loja
- Link para suporte Discord

**Parâmetros:**
```
?reason=Transacao+cancelada&status=Cancelado
```

#### 15. **checkout-pix-waiting.html** (Novo - 400 linhas)
**Funcionalidades:**
- Exibe QR Code PIX
- Mostra chave PIX para cópia/cola
- Polling automático a cada 5 segundos
- Timeout de 30 minutos
- Timer visual com cores dinâmicas
- Botão para verificar manualmente

**JavaScript:**
- `checkPaymentStatus()` - Verifica via check-pix-status.php
- `copyPixKey()` - Copia chave para clipboard
- `startTimeout()` - Gerencia tempo de expiração
- Auto-redirecionamento para sucesso/erro

## 🔄 Fluxo Completo de Pagamento

### PayPal
```
1. checkout.html → process-payment.php
2. PaymentManager → PayPalGateway
3. PayPalGateway cria ordem, retorna approval_url
4. Cliente redirecionado para PayPal
5. PayPal → paypal-webhook.php (APPROVED event)
6. Transação atualizada, item entregue
7. Webhook PagePal → checkout-success.html
```

### Mercado Pago
```
1. checkout.html → process-payment.php
2. PaymentManager → MercadoPagoGateway
3. MercadoPagoGateway cria preferência, retorna init_point
4. Cliente redirecionado para Mercado Pago
5. Mercado Pago → mercadopago-webhook.php (status change)
6. Transação atualizada, item entregue
7. Webhook → checkout-success.html (auto-redirect)
```

### PIX
```
1. checkout.html → process-payment.php
2. PaymentManager → PIXGateway
3. PIXGateway gera payload + QR code
4. Cliente redirecionado para checkout-pix-waiting.html
5. JavaScript faz polling via check-pix-status.php
6. Quando banco confirma → pix-webhook.php
7. Transação atualizada, item entregue
8. Polling detecta aprovação → checkout-success.html
```

## 📊 Estrutura do Banco de Dados

### Tabelas Utilizadas
- `mgt_transacoes` - Registro de todas as transações
- `mgt_metodos_pagamento` - Configuração dos gateways
- `mgt_produtos` - Produtos disponíveis
- `mgt_servidores` - Servidores Minecraft

### Campos Adicionados em mgt_transacoes
```sql
status VARCHAR(20) - pendente, processando, aprovado, recusado, etc
transacao_externa_id VARCHAR(100) - ID do gateway (PayPal order_id, etc)
pagamento_dados JSON - Dados completos da resposta do gateway
data_atualizacao TIMESTAMP - Quando foi atualizado
```

## 🔐 Segurança

### Validações Implementadas
- Validação de dados de entrada em todos os endpoints
- Verificação de assinatura de webhook (PIX)
- HMAC-SHA256 para signing
- SQL injection prevention (prepared statements)
- Rate limiting recomendado em produção

### Configurações Recomendadas
- HTTPS obrigatório em produção
- Armazenar chaves de API em variáveis de ambiente
- Logging de todas as transações
- Monitoramento de webhooks

## 📝 Configuração de Gateways

### No Banco de Dados
```sql
INSERT INTO mgt_metodos_pagamento (
    tipo, 
    nome, 
    config,
    ativo,
    producao
) VALUES (
    'pix',
    'PIX',
    '{"pix_key":"seu_email@exemplo.com","beneficiary":"Seu Nome"}',
    TRUE,
    FALSE
);
```

### Variáveis de Ambiente Recomendadas
```
PAYPAL_CLIENT_ID=seu_client_id
PAYPAL_SECRET=seu_secret
MERCADOPAGO_TOKEN=seu_token
MERCADOPAGO_PUBLIC_KEY=sua_public_key
PIX_KEY=seu_email@exemplo.com
PIX_BENEFICIARY=Seu Nome
WEBHOOK_SECRET_PAYPAL=seu_secret
WEBHOOK_SECRET_MERCADOPAGO=seu_secret
WEBHOOK_SECRET_PIX=seu_secret
```

## 🧪 Testes Recomendados

### PayPal
- [ ] Criar ordem
- [ ] Obter approval URL
- [ ] Capturar pagamento
- [ ] Processar webhook APPROVED
- [ ] Processar webhook COMPLETED

### Mercado Pago
- [ ] Criar preferência
- [ ] Obter init_point
- [ ] Processar webhook de pagamento
- [ ] Mapear status corretamente

### PIX
- [ ] Gerar payload válido
- [ ] Calcular CRC16 correto
- [ ] Gerar QR code
- [ ] Validar chave PIX
- [ ] Polling de status
- [ ] Timeout após 30 minutos

## 📋 Próximos Passos Recomendados

1. **Integração de Entrega**
   - Implementar ModWebSocketClient para notificar mod
   - Criar fila de entregas para jogadores offline

2. **Email Notifications**
   - Criar EmailNotifier class
   - Templates: Confirmação, Aprovação, Entrega

3. **Admin Dashboard**
   - Listar transações com filtros
   - Gerenciar reembolsos
   - Visualizar logs de webhook

4. **Testes de Carga**
   - Validar performance com múltiplos pagamentos
   - Testar timeouts e retries

5. **Documentação**
   - Guia de setup para admin
   - Troubleshooting de webhooks
   - Configuração de cada gateway

## 📞 Contato de Suporte

Para dúvidas sobre implementação:
- Discord: discord.gg/magnatas
- Issues: GitHub repository
- Email: support@magnatas.com

---

**Última Atualização:** 2025-01-15
**Versão:** 1.0.0
**Status:** Pronto para produção (com testes)
