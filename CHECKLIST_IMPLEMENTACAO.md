# ✅ Checklist de Implantação - Sistema de Pagamentos

## 📋 Pré-Implementação

### Preparação do Ambiente
- [ ] PHP 7.4+ instalado e configurado
- [ ] MySQL 5.7+ ou superior
- [ ] Extensões PHP habilitadas:
  - [ ] `php-curl` (para requisições HTTP)
  - [ ] `php-json` (para JSON)
  - [ ] `php-pdo` (para banco de dados)
  - [ ] `php-sockets` (para WebSocket)

### Planejamento
- [ ] Identificar qual gateway principal usar
- [ ] Solicitar credenciais para cada gateway
- [ ] Documentar URLs de webhook para cada serviço
- [ ] Definir valores de timeout e retry
- [ ] Planejar estratégia de testes

---

## 🔧 Configuração Técnica

### Banco de Dados
- [ ] Tabela `mgt_transacoes` criada
- [ ] Tabela `mgt_metodos_pagamento` criada
- [ ] Tabela `mgt_produtos` com dados
- [ ] Tabela `mgt_servidores` com dados
- [ ] Índices criados para performance

### Estrutura de Pastas
- [ ] `/backend` criada
- [ ] `/backend/gateways` criada
- [ ] `/backend/webhooks` criada
- [ ] `/backend/logs` criada (com permissão de escrita)
- [ ] `/backend/config` criada

### Arquivos Backend
- [ ] `PaymentGateway.php` transferido
- [ ] `PaymentManager.php` transferido
- [ ] `ModWebSocketClient.php` transferido
- [ ] `process-payment.php` transferido
- [ ] `check-pix-status.php` transferido
- [ ] `gateways/PayPalGateway.php` transferido
- [ ] `gateways/MercadoPagoGateway.php` transferido
- [ ] `gateways/PIXGateway.php` transferido
- [ ] `webhooks/paypal-webhook.php` transferido
- [ ] `webhooks/mercadopago-webhook.php` transferido
- [ ] `webhooks/pix-webhook.php` transferido

### Arquivos Frontend
- [ ] `checkout.html` atualizado
- [ ] `checkout-success.html` criado
- [ ] `checkout-cancel.html` criado
- [ ] `checkout-pix-waiting.html` criado
- [ ] `styles.css` contém estilos necessários

### Documentação
- [ ] `PAGAMENTO_IMPLEMENTACAO.md` salvo
- [ ] `CONFIGURACAO_GATEWAYS.md` salvo
- [ ] `EXEMPLOS_USO.md` salvo
- [ ] `PAGAMENTO_STATUS.md` salvo
- [ ] `README_PAGAMENTOS.md` salvo

---

## 🔑 Configuração de Gateways

### PayPal Setup
- [ ] Conta PayPal criada (Sandbox e Production)
- [ ] Client ID obtido
- [ ] Secret obtido
- [ ] Webhook URL registrado em Minhas credenciais
- [ ] Eventos selecionados:
  - [ ] CHECKOUT.ORDER.APPROVED
  - [ ] CHECKOUT.ORDER.COMPLETED
  - [ ] CHECKOUT.ORDER.VOIDED
- [ ] Testado em sandbox com cartão de teste
- [ ] Credenciais inseridas em `mgt_metodos_pagamento`

### Mercado Pago Setup
- [ ] Conta Mercado Pago criada (Sandbox e Production)
- [ ] Access Token obtido
- [ ] Public Key obtido
- [ ] Webhook URL registrado em Configurações
- [ ] Eventos selecionados:
  - [ ] payment.created
  - [ ] payment.updated
- [ ] Testado com cartão de teste: `4111111111111111`
- [ ] Credenciais inseridas em `mgt_metodos_pagamento`

### PIX Setup
- [ ] Conta bancária com suporte a PIX
- [ ] Chave PIX gerada (email/phone/CPF/CNPJ/UUID)
- [ ] Webhook do banco configurado
- [ ] Testado com pagamento real ou simulado
- [ ] Credenciais inseridas em `mgt_metodos_pagamento`

### Variáveis de Ambiente
- [ ] `.env` criado com:
  - [ ] PAYPAL_CLIENT_ID
  - [ ] PAYPAL_SECRET
  - [ ] MERCADOPAGO_TOKEN
  - [ ] MERCADOPAGO_PUBLIC_KEY
  - [ ] PIX_KEY
  - [ ] PIX_BENEFICIARY
  - [ ] WEBHOOK_SECRET_* (para cada gateway)
- [ ] `.env` adicionado ao `.gitignore`

---

## 🧪 Testes Funcionais

### Teste PayPal (Sandbox)
- [ ] Endpoint `/api/process-payment` retorna `approval_url`
- [ ] URL de aprovação abre sem erros
- [ ] Após aprovação, webhook é recebido
- [ ] Status em `mgt_transacoes` atualizado para `aprovado`
- [ ] Logs em `backend/logs/paypal_webhook_*.log` mostram evento

### Teste Mercado Pago (Sandbox)
- [ ] Endpoint `/api/process-payment` retorna `init_point`
- [ ] URL de checkout abre
- [ ] Após pagamento, webhook é recebido
- [ ] Status em `mgt_transacoes` atualizado para `aprovado`
- [ ] Logs mostram mapping correto de status

### Teste PIX
- [ ] Endpoint `/api/process-payment` retorna `qr_code` e `pix_key`
- [ ] QR Code é exibido corretamente em `checkout-pix-waiting.html`
- [ ] Chave PIX pode ser copiada (clipboard)
- [ ] Polling simula pagamento (manual com webhook)
- [ ] Status atualizado para `aprovado`
- [ ] Página redireciona para `checkout-success.html`

### Teste de Sucesso
- [ ] Página `checkout-success.html` exibe informações corretas
- [ ] Timeline é animada
- [ ] Status muda de "Aguardando" para "Entregue" após 3 segundos
- [ ] Botões funcionam corretamente

### Teste de Erro
- [ ] Página `checkout-cancel.html` exibe mensagem apropriada
- [ ] Motivo do cancelamento é exibido
- [ ] Botões "Tentar Novamente" e "Voltar à Loja" funcionam

---

## 🔐 Testes de Segurança

### Input Validation
- [ ] Rejeita jogador_nick vazio
- [ ] Rejeita servidor_id inválido
- [ ] Rejeita produto_id inexistente
- [ ] Rejeita quantidade negativa
- [ ] Rejeita metodo_pagamento desconhecido

### SQL Injection Prevention
- [ ] Todas as queries usam prepared statements
- [ ] Tenta inserir `'; DROP TABLE mgt_transacoes; --` em nick
- [ ] Transação não é deletada
- [ ] Log mostra tentativa

### Webhook Security
- [ ] Webhook sem autenticação retorna erro
- [ ] Webhook com dados inválidos é rejeitado
- [ ] Assinatura incorreta é rejeitada
- [ ] Logs registram tentativas maliciosas

### Rate Limiting
- [ ] 10 requisições de mesmo IP em 1 minuto são permitidas
- [ ] 11ª requisição retorna erro 429
- [ ] Após 1 minuto, limite reseta

---

## 📊 Testes de Carga

### Performance
- [ ] 10 transações simultâneas processadas sem erro
- [ ] 50 transações em 1 minuto processadas
- [ ] Resposta `/api/process-payment` < 500ms
- [ ] Webhook processado em < 100ms
- [ ] Polling PIX não sobrecarrega servidor

### Database
- [ ] Queries executam em < 50ms
- [ ] Índices criados em `mgt_transacoes`
- [ ] Sem deadlocks em transações concorrentes
- [ ] Backups funcionando (se configurado)

---

## 📝 Testes de Integração

### Fluxo Completo PayPal
1. [ ] Usuário acessa `checkout.html`
2. [ ] Seleciona PayPal
3. [ ] Clica "Pagar Agora"
4. [ ] Redirecionado para PayPal
5. [ ] Aprova pagamento
6. [ ] Redirecionado para sucesso
7. [ ] Webhook processado
8. [ ] Item entregue ao jogador

### Fluxo Completo Mercado Pago
1. [ ] Usuário acessa `checkout.html`
2. [ ] Seleciona Mercado Pago
3. [ ] Clica "Pagar Agora"
4. [ ] Redirecionado para Mercado Pago
5. [ ] Insere dados do cartão
6. [ ] Pagamento processado
7. [ ] Webhook recebido
8. [ ] Redirecionado para sucesso

### Fluxo Completo PIX
1. [ ] Usuário acessa `checkout.html`
2. [ ] Seleciona PIX
3. [ ] Clica "Pagar Agora"
4. [ ] Redirecionado para `checkout-pix-waiting.html`
5. [ ] QR Code e chave exibidos
6. [ ] Copia chave (botão funciona)
7. [ ] Simula pagamento (webhook manual)
8. [ ] Polling detecta aprovação
9. [ ] Redirecionado para sucesso

---

## 📊 Monitoramento

### Logging
- [ ] `/backend/logs/` contém logs de webhooks
- [ ] Logs têm timestamps
- [ ] Logs registram sucesso e erros
- [ ] Tamanho de logs monitorado (não crescer indefinidamente)

### Database Monitoring
- [ ] Query lenta log habilitado
- [ ] Consulta: `SELECT * FROM mgt_transacoes` rápida
- [ ] Backup automático configurado
- [ ] Espaço em disco monitorado

### Application Monitoring
- [ ] Erros PHP logados
- [ ] Erros JavaScript logados (console)
- [ ] Webhooks nãoentregues alertam
- [ ] Taxa de erro monitora

---

## 🚀 Deployment para Produção

### Preparação
- [ ] Todos os testes passando
- [ ] Documentação atualizada
- [ ] Credenciais em produção obtidas
- [ ] Servidor de produção preparado

### Segurança em Produção
- [ ] HTTPS obrigatório
- [ ] Certificado SSL válido
- [ ] Headers de segurança configurados:
  - [ ] X-Content-Type-Options: nosniff
  - [ ] X-Frame-Options: DENY
  - [ ] Content-Security-Policy
- [ ] CORS configurado corretamente
- [ ] Rate limiting habilitado

### Configuração
- [ ] `.env` configurado com credenciais de produção
- [ ] Modo produção ativado
- [ ] Logs em local seguro (fora da web)
- [ ] Backups automáticos configurados
- [ ] Monitoramento ativado

### Migração
- [ ] Banco de dados migrado
- [ ] Arquivos transferidos
- [ ] Permissões de arquivo corretas
- [ ] Webhooks apontam para produção
- [ ] DNS propagado (se domínio novo)

### Pós-Deployment
- [ ] Teste de transação em produção
- [ ] Webhook recebido e processado
- [ ] Logs mostram sucesso
- [ ] Status atualizado corretamente
- [ ] Alerta/notificação do admin

---

## 📚 Documentação

### Para Desenvolvedores
- [ ] README_PAGAMENTOS.md lido e entendido
- [ ] PAGAMENTO_IMPLEMENTACAO.md estudado
- [ ] Código comentado compreendido
- [ ] Fluxos mapeados visualmente
- [ ] Documentação adicional criada conforme necessário

### Para Admin
- [ ] CONFIGURACAO_GATEWAYS.md entendido
- [ ] Dashboard de transações explicado
- [ ] Processo de reembolso documentado
- [ ] Troubleshooting guide criado
- [ ] Contato de suporte documentado

### Para Usuários
- [ ] FAQ sobre pagamento criado
- [ ] Métodos de pagamento explicados
- [ ] Processo de checkout documentado
- [ ] Contato de suporte divulgado

---

## 🐛 Troubleshooting Checklist

### Se PayPal não funciona
- [ ] Client ID e Secret corretos?
- [ ] Sandbox vs Production configurado corretamente?
- [ ] URL de webhook registrada?
- [ ] Firewall permite conexão?
- [ ] Log mostra erro específico?

### Se Mercado Pago não funciona
- [ ] Access Token ainda válido?
- [ ] Public Key corresponde ao token?
- [ ] Sandbox vs Production configurado?
- [ ] External reference é único?
- [ ] Webhook recebe notificações?

### Se PIX não funciona
- [ ] Chave PIX válida?
- [ ] Payload EMV gerado corretamente?
- [ ] CRC16 calculado corretamente?
- [ ] QR Code exibido?
- [ ] Webhook do banco recebe dados?

### Se Webhooks não funcionam
- [ ] URL acessível externamente?
- [ ] Firewall permite conexão?
- [ ] Arquivo de webhook existe?
- [ ] Permissão de escrita em `/logs`?
- [ ] Log mostra o quê?

---

## 📞 Contatos de Suporte

### Gateways
- **PayPal:** https://developer.paypal.com/contact/
- **Mercado Pago:** https://developers.mercadopago.com/support
- **Seu Banco (PIX):** Número fornecido no painel

### Comunidade
- **Discord:** discord.gg/magnatas
- **GitHub Issues:** [repository]/issues
- **Email:** suporte@magnatas.com

---

## ✅ Assinatura de Conclusão

```
Implementação completada em: ________________ (data)

Desenvolvedor responsável: ________________

Testes executados por: ________________

Aprovado para produção: ________________

Notas adicionais:
_________________________________________________________________

_________________________________________________________________
```

---

## 📈 Métricas de Sucesso

Após implementação, você deve ter:

- ✅ **0 erros** em testes automatizados
- ✅ **100% uptime** do sistema
- ✅ **< 500ms** tempo de resposta do checkout
- ✅ **< 100ms** processamento de webhook
- ✅ **0 transações perdidas**
- ✅ **Todos os pagamentos** processados corretamente
- ✅ **Documentação completa** para futuras manutenções

---

**Este checklist foi criado em:** 2025-01-15
**Versão:** 1.0.0
**Categoria:** Implementação de Pagamentos
