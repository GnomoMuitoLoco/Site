# 🎮 MGT-Store - Guia de Teste e Integração em Produção

## ✅ Implementação Concluída

### 1. **Webhook de Pagamento** (`webhook-payment.php`)
- ✅ Processa aprovações de pagamento de PayPal, Mercado Pago e PIX
- ✅ Dispara automaticamente a entrega no mod via API
- ✅ Suporta múltiplos métodos de pagamento

### 2. **API de Status de Pagamento** (`payment-status.php`)
- ✅ GET para consultar status de transação
- ✅ POST para simular aprovação de pagamento (teste)
- ✅ Dispara entrega automática ao aprovar

### 3. **Carregamento de Dados Reais** (`store.js`)
- ✅ Carrega doadores de `GET /backend/api_loja.php?path=transactions`
- ✅ Carrega meta da comunidade com valores reais
- ✅ Sem mais dados fictícios ("Carregando..." infinito)

### 4. **Servidores Dinâmicos** (`store.html`, `checkout.html`)
- ✅ Dropdown de servidores carregado do banco via API
- ✅ Checkout lê preço do MGT-Cash da configuração
- ✅ Sem hardcoding de IDs ou valores

### 5. **Sistema de Transações Produção-Ready** (`process-payment.php`)
- ✅ TEST_MODE = false (produção)
- ✅ Validações reais (nick, servidor, quantidade)
- ✅ Lê configurações do banco (mgt_cash_valor)
- ✅ Colunas alinhadas com schema (status_pagamento, criado_em)
- ✅ Cupom com lógica correta (percentual/fixo)

---

## 🧪 Testando a Integração

### Fase 1: Setup Inicial

```bash
# 1. No Dashboard, cadastre um servidor:
# - Nome: "Servidor Teste"
# - Identificador: "teste"
# - API URL: http://localhost:3000 (ou seu mod)
# - API Key: seu-token-secreto
# - Status: Ativo

# 2. Configure o MGT-Cash (se não existir):
INSERT INTO mgt_configuracoes (chave, valor) 
VALUES ('mgt_cash_valor', '0.01');

# 3. Crie um método de pagamento para teste (gratis):
INSERT INTO mgt_metodos_pagamento (nome, identificador, ativo, configuracao)
VALUES ('Teste Grátis', 'gratis', 1, '{}');
```

### Fase 2: Teste de Compra

1. **Acesse a Loja** (store.html)
   - Verifique se o dropdown de servidores está preenchido
   - Verifique se a meta da comunidade mostra valores reais

2. **Selecione um Servidor e Vá ao Checkout**
   - Verifique se o servidor selecionado carregou corretamente
   - Verifique se o preço unitário mostra 0.01 (ou seu valor configurado)

3. **Preencha o Formulário de Compra**
   ```
   Nick: seu_nick_teste
   Quantidade: 100 (mínimo)
   Método: Teste Grátis (gratis)
   ```

4. **Clique em "Pagar Agora"**
   - Sistema deve criar transação no banco
   - Status deve ser "pendente"

### Fase 3: Teste do Webhook (Simular Aprovação)

Opção A: **Via cURL** (recomendado para teste)
```bash
# Simular aprovação de pagamento
curl -X POST http://localhost/backend/webhook-payment.php?method=gratis \
  -H "Content-Type: application/json" \
  -d '{
    "transaction_id": 1,
    "status": "approved"
  }'
```

Opção B: **Via payment-status.php**
```bash
# Consultar status
curl http://localhost/backend/payment-status.php?transaction_id=1

# Simular aprovação
curl -X POST http://localhost/backend/payment-status.php?action=update&transaction_id=1 \
  -H "Content-Type: application/json" \
  -d '{"status": "aprovado"}'
```

### Fase 4: Validar Entrega no Mod

1. **Verifique o Log**
   ```bash
   tail -f /var/log/php-errors.log
   # Deve mostrar: "Transação #1 aprovada e enviada para mod"
   ```

2. **Verifique o Banco**
   ```sql
   SELECT * FROM mgt_transacoes WHERE id = 1;
   -- status_pagamento deve ser 'aprovado'
   -- status_entrega deve ser 'enviado' ou 'entregue'
   ```

3. **Verifique o Mod**
   - Se seu mod tiver logs, verifique se recebeu POST em `/api/purchase`
   - Verifique se o comando foi executado ou enfileirado

---

## 🔧 Configuração para Gateways Reais

### PayPal
1. Obtenha credentials no [PayPal Developer](https://developer.paypal.com)
2. Configure no Dashboard → Métodos de Pagamento → PayPal
3. O webhook será enviado para: `http://seu-dominio/backend/webhook-payment.php?method=paypal`

### Mercado Pago
1. Crie app em [Mercado Pago](https://www.mercadopago.com.br/developers/pt)
2. Configure webhook em: `http://seu-dominio/backend/webhook-payment.php?method=mercadopago`
3. Selecione evento: `payment.created`

### PIX
1. Configure chave PIX estática no Dashboard
2. Webhook: `http://seu-dominio/backend/webhook-payment.php?method=pix`
3. Sistema de confirmação depende de seu processador

---

## 🐛 Troubleshooting

### Problema: "Nenhum servidor cadastrado"
**Solução:**
```php
// Verifique no banco
SELECT * FROM mgt_servidores WHERE ativo = TRUE;

// Se vazio, adicione um servidor no Dashboard
```

### Problema: Preço mostra 0.00
**Solução:**
```php
// Verifique configuração
SELECT * FROM mgt_configuracoes WHERE chave = 'mgt_cash_valor';

// Se não existir, adicione:
INSERT INTO mgt_configuracoes (chave, valor) VALUES ('mgt_cash_valor', '0.01');
```

### Problema: Transação criada mas não entregue ao mod
**Solução:**
1. Verifique se servidor tem API URL válido:
   ```sql
   SELECT api_url, api_key FROM mgt_servidores WHERE id = 1;
   ```

2. Teste conexão manualmente:
   ```bash
   curl -X POST http://seu-mod-url/api/purchase \
     -H "Authorization: Bearer seu-token" \
     -H "Content-Type: application/json" \
     -d '{"player": "steve", "amount": 100}'
   ```

3. Verifique logs do PHP:
   ```bash
   tail -f /var/log/php-errors.log | grep "erro\|ERROR\|Error"
   ```

### Problema: "Nick inválido"
**Motivo:** Nick não segue padrão Minecraft
**Solução:** Use nick com 3-16 caracteres, apenas letras, números e underscore

---

## 📊 Estrutura de Resposta do Mod

O sistema espera que o mod responda em POST `/api/purchase`:

```json
{
  "success": true,
  "executed": true,  // ou false se enfileirado
  "message": "Comando enfileirado para execução",
  "command": "cash add steve 100"
}
```

Exemplo de implementação (pseudocódigo):
```python
@app.post("/api/purchase")
def process_purchase(request):
    token = request.headers.get("Authorization", "").replace("Bearer ", "")
    
    if not validate_token(token):
        return {"success": false, "error": "Token inválido"}
    
    data = request.json
    player = data.get("player")
    amount = data.get("amount")
    command = data.get("command")
    
    # Validar player online
    if is_player_online(player):
        execute_command(command)
        return {"success": true, "executed": true}
    else:
        # Enfileirar para depois
        queue_command(player, command)
        return {"success": true, "executed": false}
```

---

## 📝 Checklist de Produção

- [ ] TEST_MODE = false em `process-payment.php`
- [ ] Servidores cadastrados no Dashboard com API URL/Key válidos
- [ ] MGT-Cash price configurado em `mgt_configuracoes`
- [ ] Métodos de pagamento configurados com credentials reais
- [ ] Webhooks dos gateways apontando para `/backend/webhook-payment.php`
- [ ] SSL/HTTPS ativado (obrigatório para gateways)
- [ ] Logs do PHP habilitados para debugging
- [ ] Banco de dados com backup regular
- [ ] Teste de ponta a ponta: compra → pagamento → entrega

---

## 🚀 Fluxo Completo (Produção)

```
1. Cliente acessa store.html
   ↓
2. Seleciona servidor (carregado dinamicamente)
   ↓
3. Vai para checkout.html com servidor na URL
   ↓
4. Preenche dados e clica "Pagar"
   ↓
5. POST para /backend/process-payment.php
   - Valida dados
   - Cria transação (status: pendente)
   - Inicia pagamento com gateway
   ↓
6. Gateway envia cliente para página de pagamento
   ↓
7. Cliente aprova pagamento no gateway
   ↓
8. Gateway POST webhook para /backend/webhook-payment.php
   - Atualiza status: aprovado
   - Chama /api/purchase no mod
   ↓
9. Mod executa/enfileira comando
   ↓
10. Transação finalizada (status: entregue ou enviado)
    Cliente recebe itens no jogo
```

---

## 📞 Suporte

Se encontrar problemas:
1. Verifique logs: `/var/log/php-errors.log`
2. Teste endpoints individualmente com cURL
3. Valide dados no banco: `SELECT * FROM mgt_transacoes`
4. Revise configurações no Dashboard

