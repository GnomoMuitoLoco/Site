# 🔧 Exemplos de Uso - Gateways de Pagamento

## 1️⃣ Usando PaymentManager Diretamente

### Processar Pagamento PIX
```php
<?php
require_once 'config/database.php';
require_once 'PaymentManager.php';

$paymentManager = new PaymentManager($pdo);

// Processar pagamento PIX
$result = $paymentManager->processPayment(
    'pix',
    50.00,
    'MGT-Cash 1500',
    [
        'transaction_id' => 123,
        'player_nick' => 'PlayerNick',
        'server_id' => 1,
        'product_id' => 1
    ]
);

if ($result['success']) {
    echo "QR Code: " . $result['qr_code'];
    echo "PIX Key: " . $result['pix_key'];
} else {
    echo "Erro: " . $result['error'];
}
?>
```

### Processar Pagamento PayPal
```php
<?php
$paymentManager = new PaymentManager($pdo);

$result = $paymentManager->processPayment(
    'paypal',
    50.00,
    'MGT-Cash 1500',
    [
        'transaction_id' => 124,
        'player_nick' => 'PlayerNick',
        'return_url' => 'https://seudominio.com/checkout-success.html',
        'cancel_url' => 'https://seudominio.com/checkout-cancel.html'
    ]
);

if ($result['success']) {
    header('Location: ' . $result['approval_url']);
} else {
    echo "Erro: " . $result['error'];
}
?>
```

### Verificar Status de Pagamento
```php
<?php
$paymentManager = new PaymentManager($pdo);

// Verificar status de uma transação PIX
$status = $paymentManager->checkPaymentStatus('pix', 123);

echo "Status: " . $status; // 'pendente', 'aprovado', 'recusado', etc

// Ou para PayPal
$status = $paymentManager->checkPaymentStatus('paypal', 124);
?>
```

### Listar Gateways Disponíveis
```php
<?php
$paymentManager = new PaymentManager($pdo);

$gateways = $paymentManager->getAvailableGateways();

foreach ($gateways as $gateway) {
    echo $gateway['nome'] . " (" . $gateway['tipo'] . ")";
    if ($gateway['ativo']) echo " - ATIVO";
    echo "\n";
}

// Saída esperada:
// PayPal (paypal) - ATIVO
// Mercado Pago (mercadopago) - ATIVO
// PIX (pix) - ATIVO
?>
```

---

## 2️⃣ Usar Gateways Individualmente

### Só PayPal
```php
<?php
require_once 'PaymentGateway.php';
require_once 'gateways/PayPalGateway.php';

$config = [
    'api_key' => 'seu_client_id',
    'api_secret' => 'seu_secret',
    'producao' => false
];

$gateway = new PayPalGateway($config, $pdo);

// Processar pagamento
$result = $gateway->process(50.00, 'MGT-Cash 1500', [
    'player' => 'PlayerNick',
    'return_url' => 'https://seudominio.com/checkout-success.html'
]);

echo "Approval URL: " . $result['approval_url'];

// Depois de aprovação, capturar
$capture = $gateway->capturePayment($result['order_id']);
if ($capture['status'] === 'COMPLETED') {
    echo "Pagamento capturado com sucesso!";
}
?>
```

### Só Mercado Pago
```php
<?php
require_once 'PaymentGateway.php';
require_once 'gateways/MercadoPagoGateway.php';

$config = [
    'api_key' => 'seu_access_token',
    'public_key' => 'sua_public_key',
    'producao' => false
];

$gateway = new MercadoPagoGateway($config, $pdo);

// Processar pagamento
$result = $gateway->process(50.00, 'MGT-Cash 1500', [
    'player' => 'PlayerNick',
    'email' => 'player@example.com',
    'external_reference' => '123' // ID da transação
]);

echo "Init Point (checkout): " . $result['init_point'];

// Verificar status depois
$status = $gateway->getStatus('123');
echo "Status: " . $status; // 'aprovado', 'pendente', etc
?>
```

### Só PIX
```php
<?php
require_once 'PaymentGateway.php';
require_once 'gateways/PIXGateway.php';

$config = [
    'pix_key' => 'seu_email@exemplo.com',
    'beneficiary' => 'Seu Nome Completo',
    'producao' => false
];

$gateway = new PIXGateway($config, $pdo);

// Gerar QR code
$result = $gateway->process(50.00, 'MGT-Cash 1500', [
    'player' => 'PlayerNick'
]);

echo "QR Code (Base64 PNG): " . $result['qr_code'];
echo "PIX Key: " . $result['pix_key'];
echo "EMV Payload: " . $result['payload'];

// Validar chave PIX
$valid = $gateway->validatePixKey('email@exemplo.com');
echo $valid ? "Chave válida" : "Chave inválida";

// Calcular CRC16 manualmente
$crc = $gateway->calculateCRC16($result['payload']);
echo "CRC16: " . dechex($crc);
?>
```

---

## 3️⃣ Processando Webhooks

### Webhook PayPal Manual
```php
<?php
$webhookPayload = json_decode(file_get_contents('php://input'), true);

// Exemplo de payload:
$webhookPayload = [
    'event_type' => 'CHECKOUT.ORDER.COMPLETED',
    'resource' => [
        'id' => 'order_id_123',
        'status' => 'COMPLETED',
        'purchase_units' => [
            [
                'amount' => ['value' => '50.00'],
                'custom_id' => 'transaction_id_123'
            ]
        ]
    ]
];

// Processar via gateway
$gateway = new PayPalGateway($config, $pdo);
$result = $gateway->handleWebhook($webhookPayload);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
```

### Webhook Mercado Pago Manual
```php
<?php
$webhookPayload = json_decode(file_get_contents('php://input'), true);

// Exemplo:
$webhookPayload = [
    'type' => 'payment',
    'id' => 'payment_id_456',
    'status' => 'approved',
    'external_reference' => '124',
    'transaction_amount' => 50.00
];

// Processar via gateway
$gateway = new MercadoPagoGateway($config, $pdo);
$result = $gateway->handleWebhook($webhookPayload);
?>
```

### Webhook PIX Manual
```php
<?php
$webhookPayload = json_decode(file_get_contents('php://input'), true);

// Exemplo:
$webhookPayload = [
    'transaction_id' => '125',
    'status' => 'PAID',
    'amount' => 50.00,
    'pix_key' => 'email@exemplo.com',
    'timestamp' => date('Y-m-d H:i:s')
];

// Processar via gateway
$gateway = new PIXGateway($config, $pdo);
$result = $gateway->handleWebhook($webhookPayload);
?>
```

---

## 4️⃣ JavaScript Frontend

### Chamar Endpoint de Pagamento
```javascript
// checkout.html
async function processPayment() {
    const method = document.querySelector('.payment-method.selected').dataset.method;
    
    const response = await fetch('/backend/process-payment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            jogador_nick: 'PlayerNick',
            jogador_email: 'email@exemplo.com',
            servidor_id: 1,
            produto_id: 1,
            metodo_pagamento: method,
            amount: 50.00,
            description: 'MGT-Cash 1500'
        })
    });

    const data = await response.json();

    if (data.success) {
        if (method === 'paypal') {
            window.location.href = data.data.approval_url;
        } else if (method === 'mercadopago') {
            window.location.href = data.data.init_point;
        } else if (method === 'pix') {
            window.location.href = `checkout-pix-waiting.html?transaction_id=${data.data.transaction_id}&qr_code=${encodeURIComponent(data.data.qr_code)}&pix_key=${data.data.pix_key}`;
        }
    } else {
        alert('Erro: ' + data.error);
    }
}
```

### Polling PIX Status
```javascript
// checkout-pix-waiting.html
async function checkPaymentStatus() {
    const transactionId = new URLSearchParams(window.location.search).get('transaction_id');
    
    const response = await fetch('/backend/check-pix-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ transaction_id: transactionId })
    });

    const data = await response.json();

    if (data.success && data.status === 'approved') {
        // Pagamento aprovado, redirecionar
        window.location.href = `checkout-success.html?order=${data.order_id}&product=${data.product}&player=${data.player}&amount=${encodeURIComponent(data.amount)}`;
    }
}

// Verificar a cada 5 segundos
setInterval(checkPaymentStatus, 5000);
```

---

## 5️⃣ Notificações WebSocket para Mod

### Notificar Jogador Online
```php
<?php
require_once 'ModWebSocketClient.php';

$wsClient = new ModWebSocketClient();

try {
    // Conectar ao mod
    $wsClient->connect('ws://localhost:8080', 'seu_api_key');

    // Notificar entrega de item
    $wsClient->notifyPurchaseDelivered(
        123,                    // transaction_id
        'PlayerNick',          // player_nick
        1500                   // amount (MGT-Cash)
    );

    $wsClient->disconnect();
} catch (Exception $e) {
    echo "Erro ao conectar com mod: " . $e->getMessage();
}
?>
```

### Notificar Jogador Que Entrou
```php
<?php
$wsClient = new ModWebSocketClient();

try {
    $wsClient->connect('ws://localhost:8080', 'seu_api_key');

    // Quando jogador entra, verificar se tem entregas pendentes
    $wsClient->notifyPlayerJoin(
        'PlayerNick',
        'uuid-do-jogador-12345'
    );

    // Mod pode responder com lista de entregas pendentes
    $response = $wsClient->receiveMessage();
    
    $wsClient->disconnect();
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
```

---

## 6️⃣ Queries SQL Úteis

### Listar Todas as Transações
```sql
SELECT 
    id,
    pedido_numero,
    jogador_nick,
    metodo_pagamento,
    status,
    valor_total,
    data_criacao
FROM mgt_transacoes
ORDER BY data_criacao DESC
LIMIT 20;
```

### Transações Aprovadas por Gateway
```sql
SELECT 
    metodo_pagamento,
    COUNT(*) as total_transacoes,
    SUM(valor_total) as total_vendas,
    AVG(valor_total) as valor_medio
FROM mgt_transacoes
WHERE status = 'aprovado'
  AND data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY metodo_pagamento;
```

### Transações Pendentes
```sql
SELECT *
FROM mgt_transacoes
WHERE status IN ('pendente', 'processando')
  AND data_criacao >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY data_criacao DESC;
```

### Deletar Teste (Cuidado!)
```sql
DELETE FROM mgt_transacoes WHERE id = 123;
```

---

## 7️⃣ Testes com CURL

### Processar Pagamento PIX
```bash
curl -X POST http://localhost/backend/process-payment.php \
  -H "Content-Type: application/json" \
  -d '{
    "jogador_nick": "PlayerTest",
    "jogador_email": "test@example.com",
    "servidor_id": 1,
    "produto_id": 1,
    "metodo_pagamento": "pix",
    "amount": 50.00,
    "description": "MGT-Cash 1500"
  }'
```

### Simular Webhook PIX
```bash
curl -X POST http://localhost/backend/webhooks/pix-webhook.php \
  -H "Content-Type: application/json" \
  -d '{
    "transaction_id": "123",
    "status": "PAID",
    "amount": 50.00,
    "timestamp": "2025-01-15T10:30:00Z"
  }'
```

### Verificar Status PIX
```bash
curl -X POST http://localhost/backend/check-pix-status.php \
  -H "Content-Type: application/json" \
  -d '{
    "transaction_id": "123"
  }'
```

---

## 8️⃣ Logging e Debugging

### Ver Logs
```bash
# Últimas entradas de PIX
tail -f backend/logs/pix_webhook_2025-01-15.log

# PayPal
tail -f backend/logs/paypal_webhook_2025-01-15.log

# Mercado Pago
tail -f backend/logs/mercadopago_webhook_2025-01-15.log
```

### Debug PaymentManager
```php
<?php
// Em PaymentManager.php, adicionar logging detalhado:
private function log($message) {
    $file = __DIR__ . '/logs/payment_manager_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($file, "[$timestamp] $message\n", FILE_APPEND);
    
    // Também imprimir em desenvolvimento
    if (!defined('PRODUCTION') || !PRODUCTION) {
        echo "[$timestamp] $message\n";
    }
}

$this->log("Inicializando gateway: paypal");
$this->log("Processando pagamento: R$ 50,00");
$this->log("Resultado: sucesso");
?>
```

---

## 9️⃣ Variáveis de Ambiente

### .env (Criar na raiz do projeto)
```
# PayPal
PAYPAL_CLIENT_ID=sandbox_client_id_xxx
PAYPAL_SECRET=sandbox_secret_xxx
PAYPAL_MODE=sandbox

# Mercado Pago
MERCADOPAGO_TOKEN=sandbox_token_xxx
MERCADOPAGO_PUBLIC_KEY=sandbox_public_key_xxx
MERCADOPAGO_MODE=sandbox

# PIX
PIX_KEY=seu_email@exemplo.com
PIX_BENEFICIARY=Seu Nome Completo

# App
SITE_URL=http://localhost
PRODUCTION=false
```

### Carregar em config.php
```php
<?php
// Load .env
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

define('PAYPAL_CLIENT_ID', getenv('PAYPAL_CLIENT_ID'));
define('PAYPAL_SECRET', getenv('PAYPAL_SECRET'));
// ... etc
?>
```

---

## 🔟 Resolvendo Problemas Comuns

### PIX QR Code não aparece
```php
<?php
$gateway = new PIXGateway($config, $pdo);

// Testar geração de payload
$payload = $gateway->generatePixPayload('50.00', 'seu_pix_key', 'Seu Nome');
echo "Payload: $payload\n";

// Testar CRC16
$crc = $gateway->calculateCRC16($payload);
echo "CRC16: " . dechex($crc) . "\n";

// Testar validação de chave
$valid = $gateway->validatePixKey('seu_email@exemplo.com');
echo "Chave válida: " . ($valid ? 'sim' : 'não') . "\n";

// Gerar QR manualmente
$qrUrl = $gateway->generateQRCode($payload);
echo "QR URL: $qrUrl\n";
?>
```

### PayPal "Invalid signature"
```php
<?php
// Verificar credenciais
echo "Client ID: " . substr($_ENV['PAYPAL_CLIENT_ID'], 0, 10) . "...\n";
echo "Secret: " . (strlen($_ENV['PAYPAL_SECRET']) > 0 ? 'OK' : 'FALTANDO') . "\n";

// Testar token
$gateway = new PayPalGateway($config, $pdo);
try {
    $token = $gateway->getAccessToken();
    echo "Token obtido com sucesso\n";
} catch (Exception $e) {
    echo "Erro ao obter token: " . $e->getMessage() . "\n";
}
?>
```

### Webhook não recebe
```bash
# Testar se URL é acessível
curl -I https://seudominio.com/backend/webhooks/pix-webhook.php

# Verificar logs
tail -f backend/logs/pix_webhook_*.log

# Se tiver ngrok, expor localmente
ngrok http 80
# Configurar webhook em: https://seu-ngrok-url.ngrok.io/backend/webhooks/pix-webhook.php
```

---

**Versão:** 1.0.0
**Última atualização:** 2025-01-15
