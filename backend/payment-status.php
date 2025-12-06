<?php
/**
 * Endpoint para verificar/atualizar status de pagamento
 * GET /backend/payment-status.php?transaction_id=123
 * POST /backend/payment-status.php?action=update&transaction_id=123&status=aprovado
 * 
 * Para uso em teste (simular aprovação de pagamento)
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
load_module('MGT-Database', 'Database.php');

use MGT\Database\Database;

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? 'get';
    $transactionId = $_GET['transaction_id'] ?? null;
    
    if (!$transactionId) {
        throw new Exception("Parâmetro 'transaction_id' obrigatório");
    }

    $db = Database::getInstance();

    if ($action === 'get') {
        // GET - Retornar status atual da transação
        $transaction = $db->fetchOne(
            "SELECT id, pedido_numero, jogador_nick, valor_total, metodo_pagamento, 
                    status_pagamento, status_entrega, criado_em, atualizado_em
             FROM mgt_transacoes WHERE id = ? LIMIT 1",
            [$transactionId]
        );

        if (!$transaction) {
            throw new Exception("Transação #$transactionId não encontrada");
        }

        echo json_encode([
            'success' => true,
            'transaction' => $transaction
        ]);

    } else if ($action === 'update') {
        // POST - Atualizar status e disparar webhook
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Método não permitido para ação 'update'");
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $status = $input['status'] ?? $_GET['status'] ?? null;

        if (!$status) {
            throw new Exception("Parâmetro 'status' obrigatório");
        }

        // Validar status permitidos
        if (!in_array($status, ['pendente', 'aprovado', 'recusado', 'expirado'])) {
            throw new Exception("Status '$status' inválido");
        }

        // Buscar transação
        $transaction = $db->fetchOne(
            "SELECT * FROM mgt_transacoes WHERE id = ? LIMIT 1",
            [$transactionId]
        );

        if (!$transaction) {
            throw new Exception("Transação #$transactionId não encontrada");
        }

        // Atualizar status
        $db->query(
            "UPDATE mgt_transacoes 
             SET status_pagamento = ?, atualizado_em = NOW()
             WHERE id = ?",
            [$status, $transactionId]
        );

        // Se aprovado, disparar entrega
        if ($status === 'aprovado') {
            dispatchDelivery($db, $transaction);
        }

        echo json_encode([
            'success' => true,
            'message' => "Transação #$transactionId atualizada para status '$status'",
            'transaction_id' => $transactionId
        ]);

    } else {
        throw new Exception("Ação '$action' não suportada");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Disparar entrega no mod
 */
function dispatchDelivery($db, $transaction) {
    // Buscar servidor
    $servidor = $db->fetchOne(
        "SELECT id, api_url, api_key FROM mgt_servidores WHERE id = ? AND ativo = TRUE",
        [$transaction['servidor_id']]
    );
    
    if (!$servidor) {
        error_log("Servidor #{$transaction['servidor_id']} não encontrado para transação #{$transaction['id']}");
        return;
    }

    // Buscar produto (comando padrão se não existir)
    $produto = $db->fetchOne(
        "SELECT comando_execucao FROM mgt_produtos WHERE id = ? AND ativo = TRUE",
        [$transaction['produto_id']]
    );
    
    $comando = $produto['comando_execucao'] ?? "cash add {player} {amount}";

    // Preparar comando com valores
    $comandoFinal = str_replace(
        ['{player}', '{amount}'],
        [$transaction['jogador_nick'], $transaction['quantidade']],
        $comando
    );

    // Disparar POST para mod
    $payload = [
        'transaction_id' => $transaction['id'],
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
        // Log de erro, mas não falha o update de status
        error_log("Erro ao enviar para mod: " . json_encode($response));
        return;
    }

    // Atualizar status de entrega
    $statusEntrega = $response['executed'] ? 'entregue' : 'enviado';
    
    $db->query(
        "UPDATE mgt_transacoes 
         SET status_entrega = ?, atualizado_em = NOW()
         WHERE id = ?",
        [$statusEntrega, $transaction['id']]
    );

    error_log("Transação #{$transaction['id']} entregue ao mod. Status: $statusEntrega");
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
        CURLOPT_SSL_VERIFYPEER => false,
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
?>
