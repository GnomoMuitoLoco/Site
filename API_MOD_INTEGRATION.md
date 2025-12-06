# API de Integração Mod ↔️ Site
## Servidor Magnatas - Sistema de E-commerce

---

## 📋 Visão Geral

Esta documentação descreve a API REST e o protocolo WebSocket para integração entre o mod Minecraft e o sistema de e-commerce do site. O mod deve implementar os endpoints REST e conectar-se ao WebSocket para receber eventos em tempo real.

### Arquitetura

```
┌─────────────────┐         ┌──────────────────┐         ┌─────────────────┐
│   Site/Cliente  │────────▶│  Backend PHP API │────────▶│  Mod Minecraft  │
│   (checkout)    │         │  (api_loja.php)  │  HTTP   │  (Forge/Fabric) │
└─────────────────┘         └──────────────────┘         └─────────────────┘
                                      │                            │
                                      │        WebSocket           │
                                      └────────────────────────────┘
                                        (Eventos em tempo real)
```

### Fluxo de Compra

1. **Cliente**: Seleciona produto → Checkout → Pagamento aprovado
2. **Backend**: Valida pagamento → Cria transação → Chama `POST /api/purchase` no mod
3. **Mod**: Verifica se jogador está online → Executa comando OU adiciona à fila
4. **Mod**: Retorna status da execução
5. **Backend**: Atualiza status da transação
6. **WebSocket**: Notifica quando jogador entra (processar fila)

### Fluxo real do site (store → checkout → backend → mod)

- **Front (store/checkout)**
  - `store.html` redireciona para `checkout.html?server=<identificador>` usando `redirectToCheckout()`.
  - `checkout.html` chama `backend/process-payment.php` com: `jogador_nick`, `servidor_id`, `quantidade_cash`, `metodo_pagamento`, `valor_total`, `cupom_codigo` (opcional).
- **Backend de pagamento** (`backend/process-payment.php`)
  - Cria linha em `mgt_transacoes` (TEST_MODE ativo por padrão) e retorna URLs simuladas para PayPal/MercadoPago/Pix ou confirma `gratis`.
  - Quando mover para produção: desativar `TEST_MODE`, integrar gateway real e, ao aprovar, chamar `POST /backend/api_loja.php?path=transactions/{id}/payment` com `{ status: "aprovado", transacao_id: <gateway_id>, dados: { ... } }` para acionar entrega.
- **API da Loja** (`backend/api_loja.php`)
  - `POST /backend/api_loja.php?path=transactions` cria transação (usado pelo fluxo novo de API).
  - `POST /backend/api_loja.php?path=transactions/{id}/payment` atualiza status e chama `processarEntrega()` (que conversa com o mod).
- **Mod Minecraft**
  - Expõe `POST /api/purchase` (abaixo) que o backend chama quando `status_pagamento` muda para `aprovado`.
  - Expõe `GET /api/status` para healthcheck/teste.

---

## 🔐 Autenticação

Todos os endpoints REST devem usar autenticação via **Bearer Token**.

### Header de Requisição
```http
Authorization: Bearer <API_KEY>
```

### Gerando API Keys

As API keys são gerenciadas no dashboard do site em **Loja → Servidores**. Cada servidor tem sua própria chave única.

**Formato da chave:** `mgt_<random_64_chars>`

**Exemplo:**
```
mgt_7f3a9c2e1b4d8f5a6c9e2d1b4a8f5c7e3a9b2d1f4c8e5a7b3d9f2e1c4a8b5f7e3a
```

### Validação no Mod

```java
public boolean validateRequest(String authHeader) {
    if (authHeader == null || !authHeader.startsWith("Bearer ")) {
        return false;
    }
    
    String token = authHeader.substring(7);
    return token.equals(this.configuredApiKey);
}
```

---

## 📡 REST API Endpoints

### 1. POST `/api/purchase`

Endpoint chamado pelo backend quando uma compra é aprovada. O mod deve executar o comando ou adicionar à fila.

#### Request

**URL:** `http://<SERVER_IP>:8080/api/purchase`

**Method:** `POST`

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer <API_KEY>
```

**Body:**
```json
{
  "transaction_id": 123,
  "player": "PlayerNick",
  "amount": 250,
  "command": "cash add PlayerNick 250",
  "timestamp": "2025-01-15T14:30:00Z"
}
```

**Parâmetros:**
| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `transaction_id` | integer | Sim | ID da transação no banco |
| `player` | string | Sim | Nick do jogador (3-16 chars) |
| `amount` | integer | Sim | Quantidade de MGT-Cash |
| `command` | string | Sim | Comando a ser executado |
| `timestamp` | string | Sim | Data/hora da compra (ISO 8601) |

#### Response (Sucesso - Player Online)

**Status:** `200 OK`

```json
{
  "success": true,
  "player_online": true,
  "executed": true,
  "message": "Comando executado com sucesso",
  "server_time": "2025-01-15T14:30:05Z"
}
```

#### Response (Player Offline - Adicionado à Fila)

**Status:** `200 OK`

```json
{
  "success": true,
  "player_online": false,
  "executed": false,
  "queued": true,
  "message": "Jogador offline, adicionado à fila",
  "queue_position": 3,
  "server_time": "2025-01-15T14:30:05Z"
}
```

#### Response (Erro)

**Status:** `400 Bad Request` / `500 Internal Server Error`

```json
{
  "success": false,
  "error": "Player not found",
  "message": "Jogador não existe no servidor",
  "server_time": "2025-01-15T14:30:05Z"
}
```

#### Implementação Sugerida (Java)

```java
@POST
@Path("/purchase")
@Consumes(MediaType.APPLICATION_JSON)
@Produces(MediaType.APPLICATION_JSON)
public Response processPurchase(
    @HeaderParam("Authorization") String authHeader,
    PurchaseRequest request
) {
    // Validar autenticação
    if (!validateRequest(authHeader)) {
        return Response.status(401)
            .entity(new ErrorResponse("Unauthorized"))
            .build();
    }
    
    // Buscar jogador
    ServerPlayer player = server.getPlayerList()
        .getPlayerByName(request.player);
    
    if (player == null) {
        // Jogador offline - adicionar à fila
        queueManager.addToQueue(request);
        
        return Response.ok(new PurchaseResponse(
            true,
            false,
            false,
            true,
            "Jogador offline, adicionado à fila",
            queueManager.getQueuePosition(request.player),
            Instant.now()
        )).build();
    }
    
    // Jogador online - executar comando
    try {
        server.getCommands().performCommand(
            server.createCommandSourceStack(),
            request.command
        );
        
        // Notificar jogador
        player.sendSystemMessage(Component.literal(
            "§a[Loja] Você recebeu " + request.amount + " MGT-Cash!"
        ));
        
        return Response.ok(new PurchaseResponse(
            true,
            true,
            true,
            false,
            "Comando executado com sucesso",
            0,
            Instant.now()
        )).build();
        
    } catch (Exception e) {
        return Response.status(500)
            .entity(new ErrorResponse("Erro ao executar comando: " + e.getMessage()))
            .build();
    }
}
```

---

### 2. GET `/api/status`

Endpoint para verificar status do servidor e conexão com a API.

#### Request

**URL:** `http://<SERVER_IP>:8080/api/status`

**Method:** `GET`

**Headers:**
```http
Authorization: Bearer <API_KEY>
```

#### Response

**Status:** `200 OK`

```json
{
  "online": true,
  "players_online": 15,
  "players_max": 100,
  "tps": 19.8,
  "queue_size": 3,
  "mod_version": "1.0.0",
  "minecraft_version": "1.20.1",
  "server_time": "2025-01-15T14:30:00Z",
  "uptime_seconds": 86400
}
```

#### Implementação Sugerida (Java)

```java
@GET
@Path("/status")
@Produces(MediaType.APPLICATION_JSON)
public Response getStatus(@HeaderParam("Authorization") String authHeader) {
    if (!validateRequest(authHeader)) {
        return Response.status(401).build();
    }
    
    return Response.ok(new StatusResponse(
        true,
        server.getPlayerCount(),
        server.getMaxPlayers(),
        server.getCurrentTPS(),
        queueManager.getQueueSize(),
        MOD_VERSION,
        MINECRAFT_VERSION,
        Instant.now(),
        ManagementFactory.getRuntimeMXBean().getUptime() / 1000
    )).build();
}
```

---

## 🔌 WebSocket Protocol

O WebSocket é usado para comunicação em tempo real, principalmente para notificar o backend quando jogadores entram no servidor (para processar fila de compras offline).

### Conexão

**URL:** `ws://<SERVER_IP>:8080/ws`

**Autenticação:** Enviar API key no primeiro frame após conexão

```json
{
  "type": "auth",
  "api_key": "mgt_7f3a9c2e1b4d8f5a6c9e2d1b4a8f5c7e3a9b2d1f4c8e5a7b3d9f2e1c4a8b5f7e3a"
}
```

### Eventos do Servidor → Cliente

#### 1. `player_join` - Jogador Entrou

```json
{
  "type": "player_join",
  "player": "PlayerNick",
  "uuid": "069a79f4-44e9-4726-a5be-fca90e38aaf5",
  "timestamp": "2025-01-15T14:30:00Z"
}
```

**Ação do Backend:** Verificar se há compras pendentes na fila para este jogador e chamar `POST /api/purchase` novamente.

#### 2. `player_leave` - Jogador Saiu

```json
{
  "type": "player_leave",
  "player": "PlayerNick",
  "uuid": "069a79f4-44e9-4726-a5be-fca90e38aaf5",
  "timestamp": "2025-01-15T15:00:00Z"
}
```

#### 3. `purchase_executed` - Compra Executada

Enviado quando uma compra é entregue (seja imediatamente ou da fila).

```json
{
  "type": "purchase_executed",
  "transaction_id": 123,
  "player": "PlayerNick",
  "amount": 250,
  "success": true,
  "timestamp": "2025-01-15T14:30:05Z"
}
```

**Ação do Backend:** Atualizar status da transação para `entregue`.

#### 4. `server_status` - Status Periódico (a cada 60s)

```json
{
  "type": "server_status",
  "online": true,
  "players_online": 15,
  "tps": 19.8,
  "timestamp": "2025-01-15T14:31:00Z"
}
```

### Implementação Sugerida (Java com Tyrus WebSocket)

```java
@ServerEndpoint("/ws")
public class ModWebSocketServer {
    
    private static final Set<Session> sessions = new HashSet<>();
    private boolean authenticated = false;
    
    @OnOpen
    public void onOpen(Session session) {
        ModLogger.info("Nova conexão WebSocket: " + session.getId());
    }
    
    @OnMessage
    public void onMessage(String message, Session session) {
        JsonObject json = JsonParser.parseString(message).getAsJsonObject();
        String type = json.get("type").getAsString();
        
        if (type.equals("auth")) {
            String apiKey = json.get("api_key").getAsString();
            if (validateApiKey(apiKey)) {
                authenticated = true;
                sessions.add(session);
                sendMessage(session, new JsonObject()
                    .addProperty("type", "auth_success")
                    .addProperty("message", "Autenticado com sucesso")
                );
            } else {
                try {
                    session.close(new CloseReason(
                        CloseReason.CloseCodes.CANNOT_ACCEPT,
                        "API key inválida"
                    ));
                } catch (IOException e) {
                    e.printStackTrace();
                }
            }
        }
    }
    
    @OnClose
    public void onClose(Session session) {
        sessions.remove(session);
        ModLogger.info("Conexão fechada: " + session.getId());
    }
    
    public static void broadcastPlayerJoin(ServerPlayer player) {
        JsonObject event = new JsonObject();
        event.addProperty("type", "player_join");
        event.addProperty("player", player.getName().getString());
        event.addProperty("uuid", player.getUUID().toString());
        event.addProperty("timestamp", Instant.now().toString());
        
        broadcast(event.toString());
    }
    
    public static void broadcastPurchaseExecuted(int transactionId, String player, int amount, boolean success) {
        JsonObject event = new JsonObject();
        event.addProperty("type", "purchase_executed");
        event.addProperty("transaction_id", transactionId);
        event.addProperty("player", player);
        event.addProperty("amount", amount);
        event.addProperty("success", success);
        event.addProperty("timestamp", Instant.now().toString());
        
        broadcast(event.toString());
    }
    
    private static void broadcast(String message) {
        for (Session session : sessions) {
            if (session.isOpen()) {
                sendMessage(session, message);
            }
        }
    }
}
```

---

## 📦 Sistema de Fila Offline

Quando um jogador faz uma compra mas está offline, o mod deve armazenar a compra em uma fila e processar quando o jogador entrar.

### Estrutura de Dados Sugerida

```java
public class QueuedPurchase {
    private int transactionId;
    private String player;
    private int amount;
    private String command;
    private Instant queuedAt;
    private int attempts;
    
    // getters, setters, constructors
}

public class PurchaseQueueManager {
    private Map<String, List<QueuedPurchase>> queue = new HashMap<>();
    
    public void addToQueue(PurchaseRequest request) {
        queue.computeIfAbsent(request.player, k -> new ArrayList<>())
            .add(new QueuedPurchase(
                request.transactionId,
                request.player,
                request.amount,
                request.command,
                Instant.now(),
                0
            ));
        
        saveQueueToFile();
    }
    
    public void processQueue(String playerName) {
        List<QueuedPurchase> playerQueue = queue.get(playerName);
        if (playerQueue == null || playerQueue.isEmpty()) {
            return;
        }
        
        ServerPlayer player = server.getPlayerList().getPlayerByName(playerName);
        if (player == null) {
            return;
        }
        
        List<QueuedPurchase> failed = new ArrayList<>();
        
        for (QueuedPurchase purchase : playerQueue) {
            try {
                server.getCommands().performCommand(
                    server.createCommandSourceStack(),
                    purchase.getCommand()
                );
                
                player.sendSystemMessage(Component.literal(
                    "§a[Loja] Você recebeu " + purchase.getAmount() + " MGT-Cash da compra enquanto estava offline!"
                ));
                
                // Notificar backend via WebSocket
                ModWebSocketServer.broadcastPurchaseExecuted(
                    purchase.getTransactionId(),
                    playerName,
                    purchase.getAmount(),
                    true
                );
                
            } catch (Exception e) {
                purchase.setAttempts(purchase.getAttempts() + 1);
                if (purchase.getAttempts() < 3) {
                    failed.add(purchase);
                }
                ModLogger.error("Erro ao processar compra " + purchase.getTransactionId() + ": " + e.getMessage());
            }
        }
        
        if (failed.isEmpty()) {
            queue.remove(playerName);
        } else {
            queue.put(playerName, failed);
        }
        
        saveQueueToFile();
    }
    
    private void saveQueueToFile() {
        // Salvar em JSON para persistir entre reinicializações
        File queueFile = new File(MOD_CONFIG_DIR, "purchase_queue.json");
        try (FileWriter writer = new FileWriter(queueFile)) {
            Gson gson = new GsonBuilder().setPrettyPrinting().create();
            gson.toJson(queue, writer);
        } catch (IOException e) {
            ModLogger.error("Erro ao salvar fila: " + e.getMessage());
        }
    }
}
```

### Event Listener para Player Join

```java
@SubscribeEvent
public static void onPlayerJoin(PlayerEvent.PlayerLoggedInEvent event) {
    if (event.getEntity() instanceof ServerPlayer player) {
        String playerName = player.getName().getString();
        
        // Processar fila de compras
        ModAPI.getQueueManager().processQueue(playerName);
        
        // Notificar backend via WebSocket
        ModWebSocketServer.broadcastPlayerJoin(player);
    }
}
```

---

## 🛡️ Segurança

### Boas Práticas

1. **Validação de Entrada**
   - Sempre validar nicks (regex: `^[a-zA-Z0-9_]{3,16}$`)
   - Validar comandos antes de executar
   - Verificar limites de amount (mín: 1, máx: 999999)

2. **Rate Limiting**
   - Limitar requisições por IP (ex: 60 req/min)
   - Implementar cooldown entre comandos do mesmo jogador

3. **Logging**
   - Registrar todas as requisições com timestamp
   - Salvar comandos executados para auditoria
   - Monitorar tentativas de autenticação falhas

4. **Proteção de API Key**
   - Armazenar em arquivo de configuração fora do código
   - Usar HTTPS em produção
   - Rotacionar keys periodicamente

## ✅ Checklist de Integração com o Site

- Desativar `TEST_MODE` em `backend/process-payment.php` e alinhar colunas com o schema (`status_pagamento`, `criado_em`).
- Após aprovação no gateway, chamar `POST /backend/api_loja.php?path=transactions/{id}/payment` para disparar entrega no mod.
- Garantir que `PaymentManager` leia `mgt_metodos_pagamento.identificador` (não `tipo`) e que a tabela `mgt_pagamentos` exista se for logar.
- Carregar servidores dinamicamente via API (não hard-coded no checkout) e ocultar selector quando não houver servidores.
- Usar `mgt_cash_valor` da config para precificar MGT-Cash; remover método `gratis` em produção.
- Em `store.js`, substituir a ação fictícia `listar_transacoes` por `GET /backend/api_loja.php?path=transactions&status_pagamento=aprovado` para povoar doações recentes/top doador.
- Implementar validação real de cupom chamando a API em vez de mock.

### Exemplo de Configuração (config.toml)

```toml
[api]
enabled = true
port = 8080
api_key = "mgt_7f3a9c2e1b4d8f5a6c9e2d1b4a8f5c7e3a9b2d1f4c8e5a7b3d9f2e1c4a8b5f7e3a"

[websocket]
enabled = true
port = 8080
max_connections = 10

[queue]
max_per_player = 50
max_attempts = 3
save_interval_seconds = 300

[security]
rate_limit_per_minute = 60
command_cooldown_seconds = 1
log_all_requests = true
```

---

## 🧪 Testes

### Teste com cURL

#### 1. Testar Status

```bash
curl -X GET http://localhost:8080/api/status \
  -H "Authorization: Bearer mgt_test_key"
```

#### 2. Testar Compra (Player Online)

```bash
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

#### 3. Testar WebSocket (Node.js)

```javascript
const WebSocket = require('ws');

const ws = new WebSocket('ws://localhost:8080/ws');

ws.on('open', () => {
  // Autenticar
  ws.send(JSON.stringify({
    type: 'auth',
    api_key: 'mgt_test_key'
  }));
});

ws.on('message', (data) => {
  const message = JSON.parse(data);
  console.log('Evento recebido:', message);
  
  if (message.type === 'player_join') {
    console.log(`Jogador ${message.player} entrou!`);
    // Processar fila...
  }
});
```

### Conta de Teste

Para facilitar o desenvolvimento, criar um jogador de teste:

- **Nick:** `TestBot`
- **UUID:** `00000000-0000-0000-0000-000000000000`
- **Permissões:** Operator

---

## 📚 Dependências Sugeridas

### Gradle (build.gradle)

```gradle
dependencies {
    // Servidor HTTP embutido
    implementation 'org.glassfish.tyrus.bundles:tyrus-standalone-client:2.1.3'
    
    // Jersey para REST API
    implementation 'org.glassfish.jersey.core:jersey-server:3.1.3'
    implementation 'org.glassfish.jersey.containers:jersey-container-jdk-http:3.1.3'
    implementation 'org.glassfish.jersey.media:jersey-media-json-jackson:3.1.3'
    
    // JSON
    implementation 'com.google.code.gson:gson:2.10.1'
    
    // Logging
    implementation 'org.slf4j:slf4j-api:2.0.9'
}
```

---

## 📞 Suporte

Em caso de dúvidas ou problemas com a integração:

- **Discord:** discord.gg/magnatas
- **E-mail:** suporte@magnatas.com
- **Repositório:** github.com/magnatas/mod-integration

---

## 📄 Changelog

### v1.0.0 (2025-01-15)
- Release inicial da API
- Endpoints REST: `/api/purchase`, `/api/status`
- WebSocket com eventos: `player_join`, `purchase_executed`
- Sistema de fila offline
- Autenticação via Bearer Token

---

**Desenvolvido com ❤️ pelo time Servidor Magnatas**
