# 🚀 Guia de Uso - Configuração Simplificada de Gateways

## 📌 Introdução

A configuração de gateways de pagamento foi simplificada para ser mais intuitiva, seguindo o modelo do LeaderOS. Agora você precisa apenas dos campos essenciais.

---

## 🅿️ PayPal Legacy

### O Que Preencher

| Campo | O que é | Exemplo |
|-------|---------|---------|
| **Email da Conta** | Email associado à sua conta PayPal | `seu-email@exemplo.com` |
| **Modo Sandbox** | Ativa/desativa testes | Verde = Teste, Vermelho = Produção |
| **URL de Callback** | Para webhooks do PayPal | Auto-gerada, copie e cole |

### Passo a Passo

1. **Acesse o Dashboard**
   - Vá para: Dashboard → Loja → Configurações

2. **Localize a Seção PayPal**
   - Procure por "🅿️ PayPal Legacy"

3. **Preencha o Email**
   ```
   Email da Conta: seu-email@meupaypal.com
   ```

4. **Configure o Modo**
   - **Para Testes**: Clique no botão até ficar **Verde (Ativado)**
   - **Para Produção**: Clique no botão até ficar **Vermelho (Desativado)**

5. **Copie a URL de Callback**
   ```
   Clique em: https://seu-dominio/backend/callback/paypal_legacy
   Isso copia a URL para usar no PayPal
   ```

6. **Ative o PayPal**
   - Marque: ✅ PayPal Ativo

7. **Salve**
   - Clique: 💾 Salvar PayPal

### Registrar no PayPal

1. Vá para: https://developer.paypal.com
2. Faça login com o email que configurou
3. Vá para: **Settings** (Configurações)
4. Procure: **Webhook Endpoint URL** ou **IPN URL**
5. Cole a URL que copiou:
   ```
   https://seu-dominio/backend/callback/paypal_legacy
   ```
6. Salve no PayPal

---

## 🟖 Mercado Pago

### O Que Preencher

| Campo | O que é | Exemplo |
|-------|---------|---------|
| **Access Token** | Token de acesso da sua integração | `APP_USR-123456789` |
| **URL de Callback** | Para webhooks do Mercado Pago | Auto-gerada, copie e cole |

### Passo a Passo

1. **Acesse o Dashboard**
   - Vá para: Dashboard → Loja → Configurações

2. **Localize a Seção Mercado Pago**
   - Procure por "🟖 Mercado Pago"

3. **Pegue seu Access Token**
   - Vá para: https://www.mercadopago.com.br/developers
   - Faça login
   - Vá para: **Credenciais** ou **Credentials**
   - Copie seu **Access Token** (versão de produção ou teste)

4. **Cole no Dashboard**
   ```
   Access Token: APP_USR-seu-token-aqui
   ```

5. **Copie a URL de Callback**
   ```
   Clique em: https://seu-dominio/backend/callback/mercadopago
   Isso copia a URL para usar no Mercado Pago
   ```

6. **Ative o Mercado Pago**
   - Marque: ✅ Mercado Pago Ativo

7. **Salve**
   - Clique: 💾 Salvar Mercado Pago

### Registrar no Mercado Pago

1. Vá para: https://www.mercadopago.com.br/developers/pt/dashboard
2. Vá para: **Integraciones** → **Webhooks**
3. Clique em: **Agregar Webhook**
4. Cole a URL:
   ```
   https://seu-dominio/backend/callback/mercadopago
   ```
5. Selecione os eventos que deseja monitorar:
   - ✅ payment.created
   - ✅ payment.updated
6. Salve

---

## 🔑 PIX (Mantido Como Estava)

### O Que Preencher

| Campo | O que é | Exemplo |
|-------|---------|---------|
| **Chave PIX** | Seu identificador PIX | `seu-email@exemplo.com` ou CPF/CNPJ |
| **Nome do Beneficiário** | Seu nome ou nome da empresa | `Servidor Magnatas` |

### Observações
- PIX não usa webhooks, é mais simples
- A chave pode ser: email, CPF, CNPJ ou chave aleatória
- Preencha e ative se quiser aceitar pagamentos via PIX

---

## 💡 Dicas Importantes

### ⚠️ Modo Sandbox (PayPal)
- **Verde (Ativado)** = Modo de TESTES
  - Use para testar pagamentos
  - Não cobra dinheiro real
  - Ideal para desenvolvimento

- **Vermelho (Desativado)** = Modo de PRODUÇÃO
  - Usa ambiente real
  - Cobra dinheiro de verdade
  - Use apenas quando tudo estiver testado

### 🔄 Como Testar Antes de Produção

1. **Ative Sandbox** (verde)
2. **Vá para o site** e teste um pagamento
3. **Aprove** no dashboard do PayPal/Mercado Pago (usando contas de teste)
4. **Verifique** se o mod foi entregue automaticamente
5. Se tudo ok, **desative Sandbox** (vermelho) para produção

### 🔐 Segurança

- ✅ Seus tokens são salvos no banco de dados do servidor
- ✅ Nunca são expostos no frontend
- ✅ São usados apenas para processar pagamentos no backend
- ✅ Altere se achar que foi comprometido

### 📋 URLs de Callback

As URLs são **geradas automaticamente** com base no seu domínio:

```
https://seu-dominio/backend/callback/paypal_legacy
https://seu-dominio/backend/callback/mercadopago
```

**Não precisa configurar manualmente**, basta copiar!

---

## ❓ Dúvidas Frequentes

### P: Onde pego meu Access Token do Mercado Pago?
**R:** 
1. Acesse: https://www.mercadopago.com.br/developers/pt/dashboard
2. Vá para: **Credenciais** (lado esquerdo)
3. Copie o **Access Token de produção** ou **Access Token de teste**

### P: Qual é meu email do PayPal?
**R:** É o email que você usa para fazer login no PayPal. Se não lembra, vá para https://www.paypal.com e clique em "Forgot password?" (Esqueci minha senha).

### P: Posso usar sandbox do Mercado Pago?
**R:** Sim! Use o **Access Token de teste** em vez do de produção. Ele funcionará com contas de teste do MP.

### P: Os pagamentos serão aprovados automaticamente?
**R:** Não. O webhook apenas **notifica** sobre a aprovação do gateway. Você pode processar manualmente se configurar.

### P: Preciso fazer algo no meu site?
**R:** Não! A integração é feita no backend. O site continua funcionando normalmente, recebendo os webhooks nos URLs.

### P: Posso ter dois gateways ativos ao mesmo tempo?
**R:** Sim! Configure PayPal, Mercado Pago e PIX, todos ativados. O cliente escolhe qual usar.

---

## 🚀 Fluxo Completo

```
1. Você configura no Dashboard
   ↓
2. Cliente vai ao site e compra algo
   ↓
3. Cliente escolhe o gateway (PayPal, MP ou PIX)
   ↓
4. Cliente é redirecionado para o gateway escolhido
   ↓
5. Cliente aprova o pagamento lá
   ↓
6. Gateway envia webhook para: /backend/callback/[gateway]
   ↓
7. Sistema recebe webhook e aprova pagamento
   ↓
8. Mod é entregue automaticamente ao cliente
   ↓
9. Transação aparece em: Dashboard → Loja → Registros
```

---

## 📞 Suporte

Se algo não funcionar:

1. **Verifique se salvou** as configurações (clique em Salvar)
2. **Recarregue a página** (F5)
3. **Verifique a URL de callback** - deve ser seu domínio, não localhost
4. **Teste em sandbox** antes de produção
5. **Verifique os logs** se houver erro

---

**Última Atualização**: 2025  
**Versão**: 1.0 - Simplificação LeaderOS Style
