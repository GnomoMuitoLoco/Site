# 🛒 Sistema de E-commerce - Servidor Magnatas

Sistema completo de loja online integrado com servidores Minecraft via mod para entrega automática de produtos.

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Funcionalidades](#funcionalidades)
3. [Requisitos](#requisitos)
4. [Instalação](#instalação)
5. [Configuração](#configuração)
6. [Estrutura do Projeto](#estrutura-do-projeto)
7. [Uso](#uso)
8. [Integração com Mod](#integração-com-mod)
9. [Troubleshooting](#troubleshooting)

---

## 🎯 Visão Geral

Sistema de e-commerce para venda de MGT-Cash (moeda virtual) com:
- Frontend de checkout responsivo
- Backend PHP com REST API
- Integração com gateways de pagamento (PayPal, Mercado Pago, PIX)
- Comunicação REST + WebSocket com mod Minecraft
- Dashboard administrativo completo
- Sistema de cupons de desconto
- Meta da comunidade mensal
- Fila de entregas para jogadores offline

---

## ✨ Funcionalidades

### Frontend (Público)
- ✅ Página de loja com modal de compra
- ✅ Sistema de checkout com seleção de pagamento
- ✅ Aplicação de cupons de desconto
- ✅ Visualização de doadores recentes
- ✅ Progresso da meta da comunidade
- ✅ Design responsivo (desktop, tablet, mobile)

### Backend (API)
- ✅ Criar transações
- ✅ Processar pagamentos
- ✅ Validar cupons
- ✅ Comunicação com mod (REST)
- ✅ Listar transações com filtros
- ✅ Sistema de fila para entregas offline

### Dashboard (Admin)
- ✅ **Registros**: Histórico completo de transações
- ✅ **Meta da Comunidade**: Definir e acompanhar metas mensais
- ✅ **Servidores**: Gerenciar APIs do mod
- ✅ **Cupons**: Criar cupons de desconto
- ✅ **Configurações**: Métodos de pagamento e valores

### Integração com Mod
- ✅ REST API para executar comandos
- ✅ WebSocket para eventos em tempo real
- ✅ Sistema de fila offline
- ✅ Autenticação via API Key
- ✅ Documentação completa

---

## 🔧 Requisitos

### Servidor Web
- PHP 8.0+
- MySQL 5.7+ ou MariaDB 10.3+
- Extensões PHP:
  - `pdo_mysql`
  - `curl`
  - `json`
  - `mbstring`

### Servidor Minecraft
- Minecraft 1.20.1+
- Forge ou Fabric
- Mod de integração (a desenvolver)
- Porta 8080 disponível para API

### Opcionais
- Node.js 16+ (para WebSocket server standalone)
- Redis (para cache)

---

## 📥 Instalação

### 1. Clone/Extrair o Projeto

```bash
cd /var/www/html
# ou C:\xampp\htdocs no Windows
```

### 2. Criar Banco de Dados

```bash
mysql -u root -p
```

```sql
CREATE DATABASE magnatas_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'magnatas_user'@'localhost' IDENTIFIED BY 'SUA_SENHA_SEGURA';
GRANT ALL PRIVILEGES ON magnatas_db.* TO 'magnatas_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Importar Schema

```bash
mysql -u magnatas_user -p magnatas_db < database/schema_loja.sql
```

### 4. Configurar Conexão

Edite `/config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'magnatas_db');
define('DB_USER', 'magnatas_user');
define('DB_PASS', 'SUA_SENHA_SEGURA');
```

### 5. Configurar Permissões (Linux)

```bash
chmod 755 /var/www/html/backend
chmod 755 /var/www/html/database
chmod 644 /var/www/html/config/config.php
```

---

## ⚙️ Configuração

### 1. Configurar Servidores

Acesse: `https://seusite.com/dashboard/index.php`

1. Login no dashboard
2. Navegue para **Loja → Servidores**
3. Para cada servidor:
   - Nome: "Servidor Principal"
   - Identificador: "mgt" (slug único)
   - Endereço IP: "play.magnatas.com"
   - URL da API: "http://IP_DO_SERVIDOR:8080/api"
   - API Key: Gerar chave única (formato: `mgt_<64_chars>`)
   - WebSocket URL: "ws://IP_DO_SERVIDOR:8080/ws"
   - Status: Ativo

**Exemplo de API Key:**
```
mgt_7f3a9c2e1b4d8f5a6c9e2d1b4a8f5c7e3a9b2d1f4c8e5a7b3d9f2e1c4a8b5f7e3a
```

### 2. Configurar Métodos de Pagamento

**Loja → Configurações → Métodos de Pagamento**

#### PayPal
```
Client ID: SEU_CLIENT_ID_PAYPAL
Secret: SEU_SECRET_PAYPAL
Ativo: ✓
```

#### Mercado Pago
```
Public Key: SEU_PUBLIC_KEY
Access Token: SEU_ACCESS_TOKEN
Ativo: ✓
```

#### PIX
```
Chave PIX: email@exemplo.com
Nome do Beneficiário: Servidor Magnatas
Ativo: ✓
```

### 3. Configurar Valores

**Loja → Configurações → Valores e Sistema**

```
Valor do MGT-Cash: 0.05 (R$ 0,05 por cash)
Máximo de Tentativas de Entrega: 3
```

**Cálculo automático:**
- 100 Cash = R$ 5,00
- 250 Cash = R$ 10,00
- 700 Cash = R$ 25,00
- 1500 Cash = R$ 50,00

### 4. Definir Meta Mensal

**Loja → Meta da Comunidade**

```
Mês/Ano: 2025-01
Valor da Meta: 1000.00
```

---

## 📁 Estrutura do Projeto

```
Site/
├── index.html                  # Página inicial
├── store.html                  # Página da loja
├── checkout.html               # Checkout (NOVO)
├── equipe.html                 # Nossa equipe
├── regras.html                 # Regras do servidor
├── styles.css                  # Estilos globais
├── store.css                   # Estilos da loja
├── store.js                    # Lógica da loja
├── images/                     # Imagens do site
│   ├── Banner.png
│   └── Servidor.png
├── backend/
│   ├── api_loja.php           # API REST (NOVO)
│   └── logout.php
├── dashboard/
│   ├── index.php              # Dashboard (ATUALIZADO)
│   ├── dashboard.css          # Estilos (ATUALIZADO)
│   └── login.php
├── config/
│   └── config.php             # Configuração do banco
├── database/
│   └── schema_loja.sql        # Schema do banco (NOVO)
└── API_MOD_INTEGRATION.md     # Documentação da API (NOVO)
```

---

## 🚀 Uso

### Para Clientes

1. Acesse: `https://seusite.com/store.html`
2. Clique em **"Comprar Cash"**
3. Digite seu nickname do Minecraft (3-16 caracteres)
4. Selecione o servidor
5. Escolha a quantidade (100, 250, 700 ou 1500)
6. Clique em **"Ir para Checkout"**
7. (Opcional) Aplique um cupom de desconto
8. Selecione o método de pagamento
9. Clique em **"Pagar Agora"**
10. Complete o pagamento no gateway

### Para Administradores

#### Criar Cupom

1. Dashboard → **Loja → Cupons**
2. Preencher formulário:
   - Código: PROMO10
   - Tipo: Percentual ou Fixo
   - Valor: 10 (%)
   - Valor Mínimo: 20.00 (R$)
   - Uso Máximo: 100 vezes
   - Uso por Usuário: 1 vez
   - Validade: 31/12/2025
3. **Criar Cupom**

#### Visualizar Transações

1. Dashboard → **Loja → Registros**
2. Filtrar por:
   - Status de Pagamento
   - Status de Entrega
3. Ver detalhes de cada pedido
4. Retentar entregas falhas

#### Gerenciar Servidores

1. Dashboard → **Loja → Servidores**
2. Ver lista de servidores
3. **Editar** servidor existente
4. **Testar** conexão com API

---

## 🔗 Integração com Mod

### 1. Desenvolvimento do Mod

Siga a documentação completa em: **`API_MOD_INTEGRATION.md`**

### 2. Endpoints Necessários

O mod deve implementar:

- **POST `/api/purchase`** - Receber comandos de compra
- **GET `/api/status`** - Informar status do servidor
- **WebSocket `/ws`** - Enviar eventos em tempo real

### 3. Fluxo de Entrega

```
[Pagamento Aprovado] 
    ↓
[Backend chama POST /api/purchase]
    ↓
[Mod verifica se jogador está online]
    ↓ (online)              ↓ (offline)
[Executa comando]     [Adiciona à fila]
    ↓                       ↓
[Retorna sucesso]     [Aguarda player_join]
    ↓                       ↓
[Atualiza DB]         [WebSocket notifica backend]
                            ↓
                      [Backend chama POST /api/purchase novamente]
```

### 4. Configuração no Mod

Criar arquivo `config/mgt_integration.toml`:

```toml
[api]
enabled = true
port = 8080
api_key = "mgt_7f3a9c2e1b4d8f5a6c9e2d1b4a8f5c7e3a9b2d1f4c8e5a7b3d9f2e1c4a8b5f7e3a"

[websocket]
enabled = true
port = 8080

[queue]
max_per_player = 50
max_attempts = 3
```

### 5. Teste de Integração

```bash
# Testar status
curl http://localhost:8080/api/status \
  -H "Authorization: Bearer mgt_test_key"

# Testar compra
curl -X POST http://localhost:8080/api/purchase \
  -H "Authorization: Bearer mgt_test_key" \
  -H "Content-Type: application/json" \
  -d '{
    "transaction_id": 999,
    "player": "TestPlayer",
    "amount": 100,
    "command": "cash add TestPlayer 100",
    "timestamp": "2025-01-15T14:30:00Z"
  }'
```

---

## 🐛 Troubleshooting

### Erro: "Não foi possível conectar ao banco de dados"

**Solução:**
1. Verifique credenciais em `/config/config.php`
2. Confirme que o banco existe: `SHOW DATABASES;`
3. Teste conexão: `mysql -u magnatas_user -p magnatas_db`

### Erro: "Endpoint não encontrado" na API

**Solução:**
1. Verifique se o arquivo `/backend/api_loja.php` existe
2. Teste acesso direto: `https://seusite.com/backend/api_loja.php?path=status`
3. Verifique permissões: `chmod 644 /backend/api_loja.php`

### Erro: "Mod não responde"

**Solução:**
1. Verifique se o mod está instalado e ativo
2. Confirme que a porta 8080 está aberta: `netstat -tuln | grep 8080`
3. Teste com curl conforme seção de testes
4. Verifique logs do mod

### Entregas não processadas

**Solução:**
1. Dashboard → **Loja → Registros**
2. Filtrar por "Status Entrega: Falha"
3. Clicar em 🔄 para retentar
4. Se o jogador estava offline, ele receberá ao entrar
5. Verificar fila no mod: `/mgtqueue list <player>`

### Cupons não aplicados

**Solução:**
1. Verificar se cupom está ativo
2. Confirmar validade não expirou
3. Checar se usuário já usou (limite por usuário)
4. Verificar valor mínimo de compra
5. Dashboard → **Loja → Cupons** → Ver uso

---

## 📊 Monitoramento

### Logs do Sistema

```bash
# Logs do Apache/Nginx
tail -f /var/log/apache2/error.log

# Logs do PHP
tail -f /var/log/php/error.log

# Logs do MySQL
tail -f /var/log/mysql/error.log
```

### Queries Úteis

```sql
-- Total arrecadado hoje
SELECT SUM(valor_total) FROM mgt_transacoes 
WHERE DATE(criado_em) = CURDATE() 
AND status_pagamento = 'aprovado';

-- Entregas pendentes
SELECT COUNT(*) FROM mgt_transacoes 
WHERE status_pagamento = 'aprovado' 
AND status_entrega IN ('aguardando', 'fila');

-- Top 10 compradores
SELECT jogador_nick, COUNT(*) as compras, SUM(valor_total) as total 
FROM mgt_transacoes 
WHERE status_pagamento = 'aprovado'
GROUP BY jogador_nick 
ORDER BY total DESC 
LIMIT 10;

-- Cupons mais usados
SELECT c.codigo, c.tipo, COUNT(cu.id) as usos
FROM mgt_cupons c
LEFT JOIN mgt_cupom_uso cu ON c.id = cu.cupom_id
GROUP BY c.id
ORDER BY usos DESC;
```

---

## 🔒 Segurança

### Checklist de Produção

- [ ] Alterar senha padrão do banco de dados
- [ ] Gerar novas API keys únicas para cada servidor
- [ ] Habilitar HTTPS (Let's Encrypt)
- [ ] Configurar firewall (portas 80, 443, 8080)
- [ ] Backups automáticos do banco de dados
- [ ] Rate limiting na API
- [ ] Logs de auditoria ativos
- [ ] Validação de entrada em todos os endpoints
- [ ] Sanitização de comandos do mod
- [ ] 2FA no dashboard (recomendado)

---

## 📞 Suporte

- **Discord:** discord.gg/magnatas
- **E-mail:** suporte@magnatas.com
- **Site:** magnatas.com

---

## 📝 Licença

© 2025 Servidor Magnatas. Todos os direitos reservados.

---

**Desenvolvido com ❤️ para a comunidade Magnatas**
