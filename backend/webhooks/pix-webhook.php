<?php
/**
 * Webhook PIX
 * Recebe notificações de confirmação de pagamento PIX
 * Integração com banco (aqui é um exemplo genérico)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../PaymentManager.php';

header('Content-Type: application/json');

// Log da requisição
$logFile = __DIR__ . '/../logs/pix_webhook_' . date('Y-m-d') . '.log';
$payload = file_get_contents('php://input');
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Webhook PIX recebido\n", FILE_APPEND);

try {
    $data = json_decode($payload, true);

    if (!$data) {
        throw new Exception('JSON inválido');
    }

    // Verificar autenticidade (implementar verificação de assinatura do seu banco)
    if (!verifyWebhookSignature($data)) {
        throw new Exception('Assinatura inválida');
    }

    // Processar transação PIX
    $transactionId = $data['transaction_id'] ?? null;
    $status = $data['status'] ?? null;
    $amount = $data['amount'] ?? null;

    if (!$transactionId || !$status) {
        throw new Exception('Dados incompletos');
    }

    // Buscar transação
    $stmt = $pdo->prepare('
        SELECT id, jogador_nick, servidor_id, produto_id, quantidade, valor_total 
        FROM mgt_transacoes 
        WHERE id = ? AND metodo_pagamento = ?
    ');
    $stmt->execute([$transactionId, 'pix']);
    $transacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transacao) {
        throw new Exception('Transação PIX não encontrada: ' . $transactionId);
    }

    // Validar valor
    if (abs($amount - $transacao['valor_total']) > 0.01) {
        throw new Exception('Valor não corresponde: ' . $amount . ' vs ' . $transacao['valor_total']);
    }

    // Mapear status (depende de como seu banco envia)
    $statusMap = [
        'PAID' => 'aprovado',
        'COMPLETED' => 'aprovado',
        'SUCCESS' => 'aprovado',
        'PENDING' => 'processando',
        'PROCESSING' => 'processando',
        'FAILED' => 'recusado',
        'CANCELLED' => 'cancelado',
        'EXPIRED' => 'expirado'
    ];

    $novoStatus = $statusMap[strtoupper($status)] ?? 'processando';

    // Atualizar transação
    $updateStmt = $pdo->prepare('
        UPDATE mgt_transacoes 
        SET status = ?, transacao_externa_id = ?, pagamento_dados = ?, data_atualizacao = NOW()
        WHERE id = ?
    ');
    $updateStmt->execute([
        $novoStatus,
        $transactionId,
        json_encode($data),
        $transacao['id']
    ]);

    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Status atualizado para: $novoStatus\n", FILE_APPEND);

    // Se foi aprovado, processar entrega
    if ($novoStatus === 'aprovado') {
        processarEntrega($transacao, $pdo);
    }

    // Responder ao webhook
    http_response_code(200);
    echo json_encode(['status' => 'received', 'transaction_id' => $transactionId]);

} catch (Exception $e) {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Erro: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Verificar assinatura do webhook (implementar conforme seu banco)
 * Exemplo: HMAC-SHA256
 */
function verifyWebhookSignature($data) {
    // TODO: Implementar verificação com sua chave secreta do banco
    // Geralmente o banco envia um header com a assinatura
    
    $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? null;
    if (!$signature) {
        // Se não houver assinatura, validar baseado em outras informações
        return true; // Por enquanto, aceitar todos
    }

    // Implementar lógica de verificação
    // $expectedSignature = hash_hmac('sha256', json_encode($data), BANK_SECRET);
    // return hash_equals($signature, $expectedSignature);
    
    return true;
}

function processarEntrega($transacao, $pdo) {
    // Registrar em log
    $logFile = __DIR__ . '/../logs/pix_delivery_' . date('Y-m-d') . '.log';
    $message = date('Y-m-d H:i:s') . " - Entrega PIX para: {$transacao['jogador_nick']}, Servidor: {$transacao['servidor_id']}, Produto: {$transacao['produto_id']}, Quantidade: {$transacao['quantidade']}\n";
    file_put_contents($logFile, $message, FILE_APPEND);
}
