<?php
/**
 * Webhook Handler - Processa aprovações de pagamento de gateways
 * Dispara entrega automática no mod quando pagamento é aprovado
 * 
 * POST /backend/webhook-payment.php
 * 
 * Suporta:
 * - PayPal: verifica approval_url → aprovado
 * - Mercado Pago: webhook com status de transação
 * - PIX: confirmação de recebimento
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
load_module('MGT-Database', 'Database.php');

use MGT\Database\Database;

header('Content-Type: application/json');

try {
    $method = $_GET['method'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true) ?: $_GET;
    
    if (!$method) {
        throw new Exception("Parâmetro 'method' obrigatório");
    }

    $db = Database::getInstance();

    switch ($method) {
        case 'paypal':
            handlePayPalWebhook($db, $input);
            break;
        
        case 'mercadopago':
            handleMercadoPagoWebhook($db, $input);
            break;
        
        case 'pix':
            handlePIXWebhook($db, $input);
            break;
        
        default:
            throw new Exception("Método '$method' não suportado");
    }

    echo json_encode(['success' => true, 'message' => 'Webhook processado']);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Processar webhook PayPal
 * Espera: transaction_id ou paypal_transaction_id com status approved
 */
function handlePayPalWebhook($db, $input) {
    $transactionId = $input['transaction_id'] ?? $input['paypal_transaction_id'] ?? null;
    $status = $input['status'] ?? '';
    
    if (!$transactionId) {
        throw new Exception("transaction_id obrigatório para PayPal");
    }
    
    if ($status !== 'approved') {
        return; // Ignorar outros status
    }

    approveAndDeliverTransaction($db, $transactionId, 'paypal', $input['paypal_transaction_id'] ?? null);
}

/**
 * Processar webhook Mercado Pago
 * Espera: resource ou data.id com action payment
 */
function handleMercadoPagoWebhook($db, $input) {
    // Estrutura Mercado Pago pode variar, implementar conforme documentação deles
    $mpTransactionId = $input['data']['id'] ?? $input['resource'] ?? null;
    $action = $input['action'] ?? '';
    
    if (!$mpTransactionId || $action !== 'payment.created') {
        return; // Ignorar ou processar conforme necessário
    }

    // Recuperar transaction_id do banco
    $transaction = $db->fetchOne(
        "SELECT id FROM mgt_transacoes WHERE transacao_id = ? LIMIT 1",
        [$mpTransactionId]
    );
    
    if ($transaction) {
        approveAndDeliverTransaction($db, $transaction['id'], 'mercadopago', $mpTransactionId);
    }
}

/**
 * Processar webhook PIX
 * Espera: transaction_id com confirmação de pagamento
 */
function handlePIXWebhook($db, $input) {
    $transactionId = $input['transaction_id'] ?? null;
    $status = $input['status'] ?? '';
    
    if (!$transactionId) {
        throw new Exception("transaction_id obrigatório para PIX");
    }
    
    if ($status !== 'paid' && $status !== 'completed') {
        return; // Ignorar status não finalizados
    }

    approveAndDeliverTransaction($db, $transactionId, 'pix', $input['pix_id'] ?? null);
}

/**
 * Aprovar transação e disparar entrega no mod
 */
function approveAndDeliverTransaction($db, $transactionId, $method, $gatewayTransactionId = null) {
    // Buscar transação
    $transaction = $db->fetchOne(
        "SELECT * FROM mgt_transacoes WHERE id = ? LIMIT 1",
        [$transactionId]
    );
    
    if (!$transaction) {
        throw new Exception("Transação #$transactionId não encontrada");
    }

    // Verificar se já foi processada
    if ($transaction['status_pagamento'] === 'aprovado' || $transaction['status_pagamento'] === 'entregue') {
        return; // Já foi processada
    }

    // Atualizar status para aprovado
    $db->query(
        "UPDATE mgt_transacoes 
         SET status_pagamento = 'aprovado', 
             transacao_id = ?, 
             atualizado_em = NOW()
         WHERE id = ?",
        [$gatewayTransactionId, $transactionId]
    );

    // Buscar servidor para obter API
    $servidor = $db->fetchOne(
        "SELECT id, api_url, api_key FROM mgt_servidores WHERE id = ? AND ativo = TRUE",
        [$transaction['servidor_id']]
    );
    
    if (!$servidor) {
        // Log de erro: servidor não encontrado
        error_log("Servidor #{$transaction['servidor_id']} não encontrado para transação #$transactionId");
        return;
    }

    // Buscar produto para obter comando
    $produto = $db->fetchOne(
        "SELECT comando_execucao FROM mgt_produtos WHERE id = ? AND ativo = TRUE",
        [$transaction['produto_id']]
    );
    
    if (!$produto || !$produto['comando_execucao']) {
        // Comando padrão
        $comando = "cash add {player} {amount}";
    } else {
        $comando = $produto['comando_execucao'];
    }

    // Preparar comando com valores da transação
    $comandoFinal = str_replace(
        ['{player}', '{amount}'],
        [$transaction['jogador_nick'], $transaction['quantidade']],
        $comando
    );

    // Disparar POST para o mod
    $payload = [
        'transaction_id' => $transactionId,
        'player' => $transaction['jogador_nick'],
        'amount' => (int)$transaction['quantidade'],
        'command' => $comandoFinal,
        'timestamp' => date('c')
    ];

    $response = callModAPI(
        $servidor['api_url'] . '/api/purchase',
        $servidor['api_key'],
        $payload
    );

    if (!$response['success']) {
        // Mod retornou erro, mas a transação foi aprovada
        // Marcar como enviado (o mod pode processar depois ou via fila)
        $db->query(
            "UPDATE mgt_transacoes 
             SET status_entrega = 'enviado', 
                 atualizado_em = NOW()
             WHERE id = ?",
            [$transactionId]
        );
        
        error_log("Erro ao enviar para mod: " . json_encode($response));
        return;
    }

    // Sucesso: atualizar status de entrega conforme resposta do mod
    $statusEntrega = $response['executed'] ? 'entregue' : 'aguardando';
    
    $db->query(
        "UPDATE mgt_transacoes 
         SET status_entrega = ?, 
             atualizado_em = NOW()
         WHERE id = ?",
        [$statusEntrega, $transactionId]
    );

    // Log de sucesso
    error_log("Transação #$transactionId aprovada e enviada para mod. Status entrega: $statusEntrega");
}

/**
 * Chamar API do mod
 */
function callModAPI($url, $apiKey, $payload) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false, // Para testes locais
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => $error];
    }
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => "HTTP $httpCode"];
    }
    
    $result = json_decode($response, true);
    return $result ?? ['success' => false, 'error' => 'Resposta inválida'];
}
