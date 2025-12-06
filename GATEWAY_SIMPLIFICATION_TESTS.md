# Teste de Validação - Simplificação de Gateways

## ✅ Checklist de Testes

### 1. Interface Frontend

- [ ] Acessar Dashboard → Loja → Configurações
- [ ] Verificar que os campos antigos (Client ID, Secret, Public Key) desapareceram
- [ ] Verificar que novos campos aparecem:
  - PayPal: Email + Sandbox Toggle
  - Mercado Pago: Access Token apenas
  - PIX: Mantido igual (Chave + Beneficiário)

### 2. URLs de Callback

- [ ] Verificar que as URLs aparecem automaticamente:
  - PayPal: `https://[seu-dominio]/backend/callback/paypal_legacy`
  - Mercado Pago: `https://[seu-dominio]/backend/callback/mercadopago`
- [ ] Clicar na URL - deve copiar para área de transferência
- [ ] Verificar visual (monospace font, background cinzento)

### 3. Toggle de Sandbox

- [ ] PayPal: Botão "Desativado" (vermelho)
- [ ] Clicar no botão - muda para "Ativado" (verde)
- [ ] Clicar novamente - volta para "Desativado" (vermelho)
- [ ] Verificar que o valor hidden `paypalSandbox` muda entre "true" e "false"

### 4. Salvar Configurações

- [ ] Preencher Email do PayPal: `seu-email@exemplo.com`
- [ ] Ativar/desativar Sandbox
- [ ] Marcar checkbox "PayPal Ativo"
- [ ] Clicar "Salvar PayPal"
- [ ] Verificar mensagem de sucesso
- [ ] Recarregar página
- [ ] Verificar que dados foram salvos (Email, Sandbox toggle aparecem preenchidos)

### 5. Mercado Pago

- [ ] Preencher Access Token: `APP_USR-teste123`
- [ ] Marcar checkbox "Mercado Pago Ativo"
- [ ] Clicar "Salvar Mercado Pago"
- [ ] Recarregar página
- [ ] Verificar que Access Token foi salvo
- [ ] Verificar que Public Key NÃO aparece

### 6. Payload de Dados

Abrir Console do Navegador (F12) e verificar:

```javascript
// O payload deve ser:
{
    "general": {...},
    "paymentMethods": {
        "paypal": {
            "ativo": true,
            "config": {
                "email": "seu-email@exemplo.com",
                "sandbox": false
            }
        },
        "mercadopago": {
            "ativo": true,
            "config": {
                "accessToken": "APP_USR-xxxx"
            }
        }
    }
}
```

### 7. Banco de Dados

Executar SQL para verificar o que foi salvo:

```sql
SELECT identificador, ativo, configuracao FROM mgt_metodos_pagamento;
```

Resultado esperado:
```
| identificador | ativo | configuracao |
|---------------|-------|------|
| paypal | 1 | {"email":"seu-email@exemplo.com","sandbox":false} |
| mercadopago | 1 | {"accessToken":"APP_USR-xxxx"} |
| pix | 0 | {"chave":"","beneficiario":""} |
```

### 8. Compatibilidade com Backend

- [ ] Verificar que o arquivo `backend/api_loja.php` não precisa de alterações
- [ ] Testar que `getConfigs()` retorna os dados salvos corretamente
- [ ] Testar que `saveConfigs()` aceita o novo payload sem erros

### 9. CSS e Estilos

- [ ] Toggle button tem cores corretas (vermelho/verde)
- [ ] Callback URL tem visual distinto (monospace, fundo cinzento)
- [ ] Hover effects funcionam (botões e URLs)
- [ ] Responsividade em mobile (se aplicável)

---

## 📋 Casos de Teste Específicos

### Caso 1: Primeiro Acesso
1. Novo usuário acessa Configurações
2. URLs de callback devem aparecer mesmo sem salvar nada
3. Campos devem estar vazios
4. Sandbox deve mostrar "Desativado"

### Caso 2: Salvar e Recarregar
1. Preencher PayPal com email real
2. Ativar Sandbox
3. Salvar
4. Recarregar página com F5
5. Verificar que email e sandbox persistem

### Caso 3: Desativar e Reativar
1. Marcar "PayPal Ativo"
2. Salvar
3. Desmarcar "PayPal Ativo"
4. Salvar
5. Recarregar
6. Verificar que está desmarcado

### Caso 4: Limpar Campos
1. Preencher Email do PayPal
2. Limpar (apagar tudo)
3. Salvar
4. Recarregar
5. Verificar que ficou vazio

---

## 🔗 URLs de Integração

Depois de salvar as configurações, integrar com os gateways:

### PayPal
1. Ir para `https://developer.paypal.com`
2. Usar o email salvo
3. Colar a URL de callback em: Settings → Webhook Endpoint URL

### Mercado Pago
1. Ir para `https://www.mercadopago.com.br/developers`
2. Usar o Access Token
3. Configurar webhook apontando para a Callback URL

---

## ⚠️ Problemas Conhecidos

| Problema | Solução |
|----------|---------|
| URLs de callback vazias | Garantir que `window.location.origin` funciona (HTTPS requerido em produção) |
| Dados não salvam | Verificar se API endpoint `/backend/api_loja.php?path=config` existe e aceita POST |
| Sandbox toggle não funciona | Verificar console para erros JavaScript |
| Estilo callback display errado | Verificar se `dashboard.css` foi carregado e as regras `.callback-display` existem |

---

## ✨ Resultado Esperado

Após todos os testes passarem:

✅ Dashboard mostra interface simplificada
✅ URLs de callback geradas automaticamente
✅ Toggle de Sandbox com visual claro
✅ Dados salvos e persistem após reload
✅ Backend aceita novo formato sem erros
✅ Banco de dados armazena corretamente

---

**Status**: 🟢 Pronto para Testes
**Última Atualização**: 2025
