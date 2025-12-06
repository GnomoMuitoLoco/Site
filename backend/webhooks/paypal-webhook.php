<?php
/**
 * Webhook PayPal
 * Recebe notificações de eventos de pagamento do PayPal
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../PaymentManager.php';

header('Content-Type: application/json');

// Log da requisição
$logFile = __DIR__ . '/../logs/paypal_webhook_' . date('Y-m-d') . '.log';
$payload = file_get_contents('php://input');
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Webhook recebido: " . substr($payload, 0, 200) . "...\n", FILE_APPEND);

try {
    $data = json_decode($payload, true);

    if (!$data) {
        throw new Exception('JSON inválido');
    }

    // PayPal envia o event_type
    $eventType = $data['event_type'] ?? '';

    if ($eventType === 'CHECKOUT.ORDER.APPROVED') {
        // Pagamento aprovado
        handleApprovedPayment($data, $pdo);
    } else if ($eventType === 'CHECKOUT.ORDER.COMPLETED') {
        // Pagamento completado/capturado
        handleCompletedPayment($data, $pdo);
    } else if ($eventType === 'CHECKOUT.ORDER.VOIDED') {
        // Pagamento cancelado
        handleVoidedPayment($data, $pdo);
    }

    // PayPal requer resposta 200 OK
    http_response_code(200);
    echo json_encode(['status' => 'received']);

} catch (Exception $e) {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Erro: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleApprovedPayment($data, $pdo) {
    $orderData = $data['resource'] ?? [];
    $orderId = $orderData['id'] ?? null;

    if (!$orderId) {
        throw new Exception('Order ID não encontrado');
    }

    // Buscar transação associada ao OrderId do PayPal
    $stmt = $pdo->prepare('
        SELECT id FROM mgt_transacoes 
        WHERE transacao_externa_id = ? AND metodo_pagamento = ?
    ');
    $stmt->execute([$orderId, 'paypal']);
    $transacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($transacao) {
        // Atualizar status para processando
        $updateStmt = $pdo->prepare('
            UPDATE mgt_transacoes 
            SET status = ?, pagamento_dados = ?
            WHERE id = ?
        ');
        $updateStmt->execute([
            'processando',
            json_encode($orderData),
            $transacao['id']
        ]);
    }
}

function handleCompletedPayment($data, $pdo) {
    $orderData = $data['resource'] ?? [];
    $orderId = $orderData['id'] ?? null;

    if (!$orderId) {
        throw new Exception('Order ID não encontrado');
    }

    // Buscar transação
    $stmt = $pdo->prepare('
        SELECT id, jogador_nick, servidor_id, produto_id, quantidade, valor_total 
        FROM mgt_transacoes 
        WHERE transacao_externa_id = ? AND metodo_pagamento = ?
    ');
    $stmt->execute([$orderId, 'paypal']);
    $transacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($transacao) {
        // Atualizar status para aprovado
        $updateStmt = $pdo->prepare('
            UPDATE mgt_transacoes 
            SET status = ?, pagamento_dados = ?, data_atualizacao = NOW()
            WHERE id = ?
        ');
        $updateStmt->execute([
            'aprovado',
            json_encode($orderData),
            $transacao['id']
        ]);

        // Processar entrega
        processarEntrega($transacao, $pdo);
    }
}

function handleVoidedPayment($data, $pdo) {
    $orderData = $data['resource'] ?? [];
    $orderId = $orderData['id'] ?? null;

    if (!$orderId) {
        throw new Exception('Order ID não encontrado');
    }

    // Atualizar status para cancelado
    $stmt = $pdo->prepare('
        UPDATE mgt_transacoes 
        SET status = ?, pagamento_dados = ?
        WHERE transacao_externa_id = ? AND metodo_pagamento = ?
    ');
    $stmt->execute([
        'cancelado',
        json_encode($orderData),
        $orderId,
        'paypal'
    ]);
}

function processarEntrega($transacao, $pdo) {
    // Aqui seria chamado o webhook para entregar o item ao jogador
    // Por enquanto, apenas registramos em log
    
    $logFile = __DIR__ . '/../logs/paypal_delivery_' . date('Y-m-d') . '.log';
    $message = date('Y-m-d H:i:s') . " - Entrega para: {$transacao['jogador_nick']}, Servidor: {$transacao['servidor_id']}, Produto: {$transacao['produto_id']}, Quantidade: {$transacao['quantidade']}\n";
    file_put_contents($logFile, $message, FILE_APPEND);
}
