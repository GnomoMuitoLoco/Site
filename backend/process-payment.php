<?php
/**
 * Endpoint para processar pagamentos via PaymentManager
 * POST /api/process-payment
 * 
 * Integra com banco de dados para criar transação e, após aprovação, 
 * dispara entrega automática no mod via API REST.
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
load_module('MGT-Database', 'Database.php');

use MGT\Database\Database;

header('Content-Type: application/json');

// MODO DE TESTE - Defina como true para simular pagamentos sem gateway real
// DESATIVAR em produção!
define('TEST_MODE', false);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    // Normalizar nomes de campos (aceitar ambos formatos)
    if (isset($input['nick_jogador'])) {
        $input['jogador_nick'] = $input['nick_jogador'];
    }
    if (isset($input['valor_total'])) {
        $input['amount'] = $input['valor_total'];
    }
    if (isset($input['quantidade_cash'])) {
        $input['quantidade'] = $input['quantidade_cash'];
    }
    
    // Validar dados obrigatórios
    $required = ['jogador_nick', 'servidor_id', 'metodo_pagamento', 'amount'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Campo obrigatório: $field");
        }
    }

    // Validar nick do jogador (3-16 caracteres alfanuméricos + underscore)
    if (!preg_match('/^[a-zA-Z0-9_]{3,16}$/', $input['jogador_nick'])) {
        throw new Exception("Nick inválido. Use apenas letras, números e underscore (3-16 caracteres)");
    }

    // Validar quantidade mínima
    $quantidade = intval($input['quantidade'] ?? 1);
    if ($quantidade < 1) {
        throw new Exception("Quantidade deve ser maior que 0");
    }

    // Validar servidor existe
    $db = Database::getInstance();
    $servidor = $db->fetchOne(
        "SELECT id, nome, api_url, api_key FROM mgt_servidores WHERE id = ? AND ativo = TRUE",
        [$input['servidor_id']]
    );
    
    if (!$servidor) {
        throw new Exception("Servidor não encontrado ou inativo");
    }

    // Gerar número do pedido sequencial
    $pedidoNumero = null;
    try {
        $stmt = $db->query("CALL gerar_numero_pedido(@numero)");
        $result = $db->fetchOne("SELECT @numero as numero");
        $pedidoNumero = $result['numero'] ?? 'PED-' . date('YmdHis');
    } catch (Exception $e) {
        // Fallback se procedure não existir
        $pedidoNumero = 'PED-' . date('YmdHis') . rand(1000, 9999);
    }

    // Obter valor unitário do MGT-Cash da configuração
    $configMGTValor = $db->fetchOne("SELECT valor FROM mgt_configuracoes WHERE chave = 'mgt_cash_valor'");
    $unitPrice = $configMGTValor ? floatval($configMGTValor['valor']) : 0.01; // Fallback: R$ 0,01

    // Calcular valor bruto
    $valorBruto = $quantidade * $unitPrice;
    
    // Aplicar cupom se fornecido
    $cupomId = null;
    $desconto = 0.00;
    if (!empty($input['cupom_codigo'])) {
        $cupom = $db->fetchOne(
            "SELECT id, tipo, valor, valor_minimo FROM mgt_cupons 
             WHERE codigo = ? AND ativo = TRUE 
             AND (valido_ate IS NULL OR valido_ate > NOW())
             AND (uso_maximo IS NULL OR uso_atual < uso_maximo)",
            [strtoupper($input['cupom_codigo'])]
        );
        
        if ($cupom && $valorBruto >= floatval($cupom['valor_minimo'] ?? 0)) {
            $cupomId = $cupom['id'];
            if ($cupom['tipo'] === 'percentual') {
                $desconto = $valorBruto * (floatval($cupom['valor']) / 100);
            } else {
                $desconto = floatval($cupom['valor']);
            }
        }
    }

    $valorTotal = $valorBruto - $desconto;

    // Criar transação no banco com status inicial 'pendente'
    $transactionId = $db->insert(
        "INSERT INTO mgt_transacoes (
            pedido_numero,
            jogador_nick,
            jogador_email,
            servidor_id,
            produto_id,
            quantidade,
            valor_bruto,
            cupom_id,
            desconto,
            valor_total,
            metodo_pagamento,
            status_pagamento,
            ip_comprador,
            user_agent,
            criado_em
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
        [
            $pedidoNumero,
            $input['jogador_nick'],
            $input['jogador_email'] ?? null,
            $input['servidor_id'],
            $input['produto_id'] ?? 1, // Produto padrão: MGT-Cash
            $quantidade,
            $valorBruto,
            $cupomId,
            $desconto,
            $valorTotal,
            $input['metodo_pagamento'],
            'pendente',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]
    );

    // MODO DE TESTE - Simular resposta de pagamento (para desenvolvimento/testes locais)
    if (TEST_MODE) {
        $response = [
            'success' => true,
            'transaction_id' => $transactionId,
            'pedido_numero' => $pedidoNumero,
            'test_mode' => true,
            'data' => []
        ];

        // Simular respostas específicas de cada gateway
        if ($input['metodo_pagamento'] === 'paypal') {
            $response['data']['approval_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/payment-test.html?method=paypal&id=' . $transactionId;
        } else if ($input['metodo_pagamento'] === 'mercadopago') {
            $response['data']['init_point'] = 'http://' . $_SERVER['HTTP_HOST'] . '/payment-test.html?method=mercadopago&id=' . $transactionId;
        } else if ($input['metodo_pagamento'] === 'pix') {
            $response['data']['qr_code'] = 'TESTE_QR_CODE_' . $transactionId;
            $response['data']['pix_key'] = 'test@magnatas.com';
            $response['data']['transaction_id'] = $transactionId;
        }

        echo json_encode($response);
        exit;
    }

    // MODO PRODUÇÃO - Processar pagamento real via PaymentManager
    load_module('MGT-Payment', 'PaymentManager.php');
    use MGT\Payment\PaymentManager;

    $paymentManager = new PaymentManager($db);
    
    $result = $paymentManager->processPayment(
        $input['metodo_pagamento'],
        $valorTotal,
        "MGT-Cash x{$quantidade} para {$input['jogador_nick']}",
        [
            'transaction_id' => $transactionId,
            'player_nick' => $input['jogador_nick'],
            'server_id' => $input['servidor_id'],
            'product_id' => $input['produto_id'] ?? 1,
            'quantity' => $quantidade,
            'return_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/checkout-success.html?order=' . $pedidoNumero,
            'cancel_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/checkout-cancel.html?order=' . $pedidoNumero
        ]
    );

    if (!$result['success']) {
        // Marcar transação como recusada
        $db->query(
            "UPDATE mgt_transacoes SET status_pagamento = 'recusado', atualizado_em = NOW() WHERE id = ?",
            [$transactionId]
        );
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Erro ao processar pagamento'
        ]);
        exit;
    }

    // Retornar dados específicos do gateway
    $response = [
        'success' => true,
        'transaction_id' => $transactionId,
        'pedido_numero' => $pedidoNumero,
        'data' => []
    ];

    // Adicionar campos específicos baseado no gateway
    if ($input['metodo_pagamento'] === 'paypal') {
        $response['data']['approval_url'] = $result['approval_url'] ?? '';
    } else if ($input['metodo_pagamento'] === 'mercadopago') {
        $response['data']['init_point'] = $result['init_point'] ?? '';
    } else if ($input['metodo_pagamento'] === 'pix') {
        $response['data']['qr_code'] = $result['qr_code'] ?? '';
        $response['data']['pix_key'] = $result['pix_key'] ?? '';
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
