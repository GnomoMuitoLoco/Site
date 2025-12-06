<?php
/**
 * Endpoint para verificar status de pagamento PIX
 * POST /api/check-pix-status
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/PaymentManager.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['transaction_id'])) {
        throw new Exception('transaction_id é obrigatório');
    }

    $transactionId = $input['transaction_id'];

    // Buscar transação no banco
    $stmt = $pdo->prepare('
        SELECT 
            t.id,
            t.status,
            t.metodo_pagamento,
            t.valor,
            p.id as product_id,
            p.nome,
            c.id_jogador,
            c.nick_jogador
        FROM mgt_transacoes t
        JOIN mgt_carrinhos c ON t.id_carrinho = c.id
        JOIN mgt_produtos p ON c.id_produto = p.id
        WHERE t.id = ? AND t.metodo_pagamento = ?
    ');
    
    $stmt->execute([$transactionId, 'pix']);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Transação não encontrada'
        ]);
        exit;
    }

    // Se já foi aprovada, retornar sucesso
    if ($transaction['status'] === 'aprovado') {
        echo json_encode([
            'success' => true,
            'status' => 'approved',
            'order_id' => $transactionId,
            'product' => $transaction['product_id'],
            'player' => $transaction['nick_jogador'],
            'amount' => 'R$ ' . number_format($transaction['valor'], 2, ',', '.')
        ]);
        exit;
    }

    // Verificar status via PaymentManager
    $paymentManager = new PaymentManager($pdo);
    
    $status = $paymentManager->checkPaymentStatus('pix', $transactionId);
    
    if ($status === 'aprovado') {
        // Atualizar transação como aprovada
        $updateStmt = $pdo->prepare('
            UPDATE mgt_transacoes 
            SET status = ?, data_atualizacao = NOW()
            WHERE id = ?
        ');
        $updateStmt->execute(['aprovado', $transactionId]);

        // Chamar webhook para processar entrega
        callDeliveryWebhook($transactionId, $transaction);

        echo json_encode([
            'success' => true,
            'status' => 'approved',
            'order_id' => $transactionId,
            'product' => $transaction['product_id'],
            'player' => $transaction['nick_jogador'],
            'amount' => 'R$ ' . number_format($transaction['valor'], 2, ',', '.')
        ]);
    } else {
        // Ainda pendente
        echo json_encode([
            'success' => true,
            'status' => 'pending',
            'transaction_id' => $transactionId
        ]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function callDeliveryWebhook($transactionId, $transaction) {
    // Este webhook será chamado quando o pagamento PIX for confirmado
    // Aqui seria feita a entrega real do item ao jogador via mod
    
    // Por enquanto, apenas registramos em log
    $logFile = __DIR__ . '/logs/pix_approved_' . date('Y-m-d') . '.log';
    $message = date('Y-m-d H:i:s') . " - Transação PIX aprovada: $transactionId, Jogador: " . $transaction['nick_jogador'] . "\n";
    file_put_contents($logFile, $message, FILE_APPEND);
}
