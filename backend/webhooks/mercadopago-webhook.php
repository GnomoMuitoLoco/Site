<?php
/**
 * Webhook Mercado Pago
 * Recebe notificações de eventos de pagamento do Mercado Pago
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../PaymentManager.php';

header('Content-Type: application/json');

// Log da requisição
$logFile = __DIR__ . '/../logs/mercadopago_webhook_' . date('Y-m-d') . '.log';
$payload = file_get_contents('php://input');
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Webhook recebido\n", FILE_APPEND);

try {
    // Mercado Pago pode enviar como JSON ou como dados POST
    $data = json_decode($payload, true);
    
    if (!$data) {
        // Tentar como dados POST
        $data = $_POST;
    }

    if (!$data) {
        throw new Exception('Dados inválidos');
    }

    // Verificar tipo de notificação
    $type = $data['type'] ?? $data['action'] ?? null;
    $id = $data['id'] ?? null;

    if (!$type || !$id) {
        throw new Exception('Tipo ou ID não fornecido');
    }

    // Se for notificação de pagamento
    if ($type === 'payment') {
        handlePaymentNotification($data, $id, $pdo);
    }

    // Mercado Pago requer resposta 200 OK
    http_response_code(200);
    echo json_encode(['status' => 'received']);

} catch (Exception $e) {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Erro: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function handlePaymentNotification($data, $paymentId, $pdo) {
    // Buscar transação pelo external_reference
    $externalReference = $data['external_reference'] ?? null;

    if (!$externalReference) {
        throw new Exception('external_reference não fornecida');
    }

    // Buscar transação
    $stmt = $pdo->prepare('
        SELECT id, jogador_nick, servidor_id, produto_id, quantidade, valor_total 
        FROM mgt_transacoes 
        WHERE id = ? AND metodo_pagamento = ?
    ');
    $stmt->execute([$externalReference, 'mercadopago']);
    $transacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transacao) {
        throw new Exception('Transação não encontrada: ' . $externalReference);
    }

    // Mapear status do Mercado Pago
    $status = $data['status'] ?? null;
    $statusMap = [
        'approved' => 'aprovado',
        'pending' => 'processando',
        'authorized' => 'processando',
        'in_process' => 'processando',
        'in_mediation' => 'processando',
        'rejected' => 'recusado',
        'cancelled' => 'cancelado',
        'refunded' => 'reembolsado',
        'expired' => 'expirado'
    ];

    $novoStatus = $statusMap[$status] ?? 'processando';

    // Atualizar transação
    $updateStmt = $pdo->prepare('
        UPDATE mgt_transacoes 
        SET status = ?, transacao_externa_id = ?, pagamento_dados = ?
        WHERE id = ?
    ');
    $updateStmt->execute([
        $novoStatus,
        $paymentId,
        json_encode($data),
        $transacao['id']
    ]);

    // Se foi aprovado, processar entrega
    if ($novoStatus === 'aprovado') {
        processarEntrega($transacao, $pdo);
    }
}

function processarEntrega($transacao, $pdo) {
    // Registrar em log
    $logFile = __DIR__ . '/../logs/mercadopago_delivery_' . date('Y-m-d') . '.log';
    $message = date('Y-m-d H:i:s') . " - Entrega para: {$transacao['jogador_nick']}, Servidor: {$transacao['servidor_id']}, Produto: {$transacao['produto_id']}, Quantidade: {$transacao['quantidade']}\n";
    file_put_contents($logFile, $message, FILE_APPEND);
}
