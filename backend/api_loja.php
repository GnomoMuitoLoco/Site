<?php
/**
 * API de Loja - Processamento de Pagamentos e Transações
 * Servidor Magnatas
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
load_module('MGT-Database', 'Database.php');

use MGT\Database\Database;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

class StoreAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Criar nova transação/pedido
     */
    public function createTransaction($data) {
        try {
            // Validar dados obrigatórios
            $required = ['jogador_nick', 'servidor_id', 'produto_id', 'quantidade'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->error("Campo obrigatório: $field", 400);
                }
            }
            
            // Validar nick (3-16 caracteres, alfanumérico + underscore)
            if (!preg_match('/^[a-zA-Z0-9_]{3,16}$/', $data['jogador_nick'])) {
                return $this->error("Nick inválido", 400);
            }
            
            // Buscar produto
            $produto = $this->db->fetchOne("
                SELECT * FROM mgt_produtos 
                WHERE id = ? AND ativo = TRUE
            ", [$data['produto_id']]);
            
            if (!$produto) {
                return $this->error("Produto não encontrado", 404);
            }
            
            // Buscar servidor
            $servidor = $this->db->fetchOne("
                SELECT * FROM mgt_servidores 
                WHERE id = ? AND ativo = TRUE
            ", [$data['servidor_id']]);
            
            if (!$servidor) {
                return $this->error("Servidor não encontrado", 404);
            }
            
            // Calcular valores
            $quantidade = intval($data['quantidade']);
            $valorBruto = $produto['preco'] * $quantidade;
            $desconto = 0.00;
            $cupomId = null;
            
            // Aplicar cupom se fornecido
            if (!empty($data['cupom_codigo'])) {
                $cupom = $this->validarCupom($data['cupom_codigo'], $data['jogador_nick'], $valorBruto);
                if ($cupom) {
                    $cupomId = $cupom['id'];
                    if ($cupom['tipo'] === 'percentual') {
                        $desconto = $valorBruto * ($cupom['valor'] / 100);
                    } else {
                        $desconto = $cupom['valor'];
                    }
                }
            }
            
            $valorTotal = $valorBruto - $desconto;
            
            // Gerar número do pedido
            $stmt = $this->db->query("CALL gerar_numero_pedido(@numero)");
            $result = $this->db->fetchOne("SELECT @numero as numero");
            $pedidoNumero = $result['numero'];
            
            // Inserir transação
            $transacaoId = $this->db->insert("
                INSERT INTO mgt_transacoes (
                    pedido_numero, jogador_nick, jogador_email, servidor_id, produto_id,
                    quantidade, valor_bruto, cupom_id, desconto, valor_total,
                    metodo_pagamento, ip_comprador, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $pedidoNumero,
                $data['jogador_nick'],
                $data['jogador_email'] ?? null,
                $data['servidor_id'],
                $data['produto_id'],
                $quantidade,
                $valorBruto,
                $cupomId,
                $desconto,
                $valorTotal,
                $data['metodo_pagamento'] ?? 'pendente',
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            // Registrar uso do cupom
            if ($cupomId) {
                $this->db->insert("
                    INSERT INTO mgt_cupom_uso (cupom_id, transacao_id, jogador_nick)
                    VALUES (?, ?, ?)
                ", [$cupomId, $transacaoId, $data['jogador_nick']]);
                
                $this->db->query("
                    UPDATE mgt_cupons SET uso_atual = uso_atual + 1 WHERE id = ?
                ", [$cupomId]);
            }
            
            return $this->success([
                'transacao_id' => $transacaoId,
                'pedido_numero' => $pedidoNumero,
                'valor_total' => $valorTotal,
                'servidor' => $servidor['identificador'],
                'produto' => $produto['nome']
            ]);
            
        } catch (Exception $e) {
            return $this->error("Erro ao criar transação: " . $e->getMessage(), 500);
        }
    }
    
    /**
     * Atualizar status de pagamento
     */
    public function updatePaymentStatus($transacaoId, $data) {
        try {
            $transacao = $this->db->fetchOne("
                SELECT * FROM mgt_transacoes WHERE id = ?
            ", [$transacaoId]);
            
            if (!$transacao) {
                return $this->error("Transação não encontrada", 404);
            }
            
            $this->db->query("
                UPDATE mgt_transacoes 
                SET status_pagamento = ?,
                    transacao_id = ?,
                    pagamento_dados = ?,
                    atualizado_em = NOW()
                WHERE id = ?
            ", [
                $data['status'] ?? 'processando',
                $data['transacao_id'] ?? null,
                json_encode($data['dados'] ?? []),
                $transacaoId
            ]);
            
            // Se pagamento aprovado, tentar entregar
            if ($data['status'] === 'aprovado') {
                $this->processarEntrega($transacaoId);
            }
            
            return $this->success(['message' => 'Status atualizado']);
            
        } catch (Exception $e) {
            return $this->error("Erro ao atualizar pagamento: " . $e->getMessage(), 500);
        }
    }
    
    /**
     * Processar entrega do produto via mod
     */
    private function processarEntrega($transacaoId) {
        try {
            $transacao = $this->db->fetchOne("
                SELECT t.*, s.api_url, s.api_key, p.comando_execucao, p.quantidade
                FROM mgt_transacoes t
                INNER JOIN mgt_servidores s ON t.servidor_id = s.id
                INNER JOIN mgt_produtos p ON t.produto_id = p.id
                WHERE t.id = ?
            ", [$transacaoId]);
            
            if (!$transacao) {
                return false;
            }
            
            // Preparar comando
            $comando = str_replace(
                ['{player}', '{amount}'],
                [$transacao['jogador_nick'], $transacao['quantidade'] * $transacao['quantidade']],
                $transacao['comando_execucao']
            );
            
            // Enviar requisição para o mod
            $response = $this->sendToMod(
                $transacao['api_url'] . '/purchase',
                $transacao['api_key'],
                [
                    'player' => $transacao['jogador_nick'],
                    'amount' => $transacao['quantidade'] * $transacao['quantidade'],
                    'command' => $comando,
                    'transaction_id' => $transacao['id']
                ]
            );
            
            if ($response['success']) {
                // Entrega bem-sucedida
                $this->db->query("
                    UPDATE mgt_transacoes 
                    SET status_entrega = 'entregue',
                        entregue_em = NOW(),
                        atualizado_em = NOW()
                    WHERE id = ?
                ", [$transacaoId]);
                
                return true;
            } else {
                // Falha na entrega - colocar na fila
                $tentativas = intval($transacao['tentativas_entrega']) + 1;
                $maxTentativas = $this->getConfig('max_tentativas_entrega', 3);
                
                if ($tentativas >= $maxTentativas) {
                    // Máximo de tentativas atingido - colocar na fila
                    $this->db->query("
                        UPDATE mgt_transacoes 
                        SET status_entrega = 'fila',
                            tentativas_entrega = ?,
                            erro_entrega = ?,
                            atualizado_em = NOW()
                        WHERE id = ?
                    ", [$tentativas, $response['error'] ?? 'Jogador offline', $transacaoId]);
                } else {
                    // Tentar novamente
                    $this->db->query("
                        UPDATE mgt_transacoes 
                        SET status_entrega = 'enviado',
                            tentativas_entrega = ?,
                            erro_entrega = ?,
                            atualizado_em = NOW()
                        WHERE id = ?
                    ", [$tentativas, $response['error'] ?? 'Tentativa ' . $tentativas, $transacaoId]);
                }
                
                return false;
            }
            
        } catch (Exception $e) {
            // Registrar erro
            $this->db->query("
                UPDATE mgt_transacoes 
                SET status_entrega = 'falha',
                    erro_entrega = ?,
                    atualizado_em = NOW()
                WHERE id = ?
            ", [$e->getMessage(), $transacaoId]);
            
            return false;
        }
    }
    
    /**
     * Enviar requisição HTTP para o mod
     */
    private function sendToMod($url, $apiKey, $data) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 10
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
    
    /**
     * Validar cupom de desconto
     */
    private function validarCupom($codigo, $jogador, $valorCompra) {
        $cupom = $this->db->fetchOne("
            SELECT * FROM vw_cupons_ativos WHERE codigo = ?
        ", [$codigo]);
        
        if (!$cupom) {
            return null;
        }
        
        // Verificar valor mínimo
        if ($valorCompra < $cupom['valor_minimo']) {
            return null;
        }
        
        // Verificar uso por usuário
        $usosJogador = $this->db->fetchOne("
            SELECT COUNT(*) as total 
            FROM mgt_cupom_uso 
            WHERE cupom_id = ? AND jogador_nick = ?
        ", [$cupom['id'], $jogador]);
        
        if ($usosJogador['total'] >= $cupom['usa_por_usuario']) {
            return null;
        }
        
        return $cupom;
    }
    
    /**
     * Listar transações
     */
    public function listTransactions($filtros = []) {
        $where = [];
        $params = [];

        if (!empty($filtros['status_pagamento'])) {
            $where[] = "status_pagamento = ?";
            $params[] = $filtros['status_pagamento'];
        }

        if (!empty($filtros['status_entrega'])) {
            $where[] = "status_entrega = ?";
            $params[] = $filtros['status_entrega'];
        }

        if (!empty($filtros['servidor_id'])) {
            $where[] = "servidor_id = ?";
            $params[] = $filtros['servidor_id'];
        }

        $whereSQL = empty($where) ? "" : "WHERE " . implode(" AND ", $where);

        $transacoes = $this->db->fetchAll("
            SELECT 
                id,
                pedido_numero,
                jogador_nick,
                valor_total,
                metodo_pagamento,
                status_pagamento,
                criado_em
            FROM mgt_transacoes
            $whereSQL
            ORDER BY criado_em DESC
            LIMIT 200
        ", $params);

        $dados = array_map(function($t) {
            $t['valor_total'] = (float)$t['valor_total'];
            $t['status_pagamento_label'] = $this->mapStatusPagamento($t['status_pagamento']);
            return $t;
        }, $transacoes ?? []);

        return $this->success($dados);
    }

    /**
     * Obter snapshot de configurações da loja e métodos de pagamento
     */
    public function getConfigs() {
        $general = [
            'mgt_cash_valor' => $this->getConfig('mgt_cash_valor', '0.00'),
            'max_tentativas_entrega' => $this->getConfig('max_tentativas_entrega', '3'),
        ];

        $metodos = $this->db->fetchAll("SELECT identificador, ativo, configuracao FROM mgt_metodos_pagamento");
        $paymentMethods = [];
        foreach ($metodos as $m) {
            $config = json_decode($m['configuracao'] ?? '{}', true) ?: [];
            $paymentMethods[$m['identificador']] = [
                'ativo' => (bool)$m['ativo'],
                'config' => $config,
            ];
        }

        return $this->success([
            'general' => $general,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Salvar configurações da loja e métodos de pagamento
     */
    public function saveConfigs($data) {
        $general = $data['general'] ?? [];
        $payment = $data['paymentMethods'] ?? [];

        // Configurações gerais
        if (isset($general['mgt_cash_valor'])) {
            $this->upsertConfig('mgt_cash_valor', $general['mgt_cash_valor'], 'float', 'Valor de 1 MGT-Cash em R$', 'loja');
        }
        if (isset($general['max_tentativas_entrega'])) {
            $this->upsertConfig('max_tentativas_entrega', $general['max_tentativas_entrega'], 'int', 'Máximo de tentativas de entrega', 'loja');
        }

        // Métodos de pagamento
        $mapaNomes = [
            'paypal' => 'PayPal',
            'mercadopago' => 'Mercado Pago',
            'pix' => 'PIX',
        ];

        foreach ($payment as $identificador => $cfg) {
            $nome = $mapaNomes[$identificador] ?? ucfirst($identificador);
            $ativo = !empty($cfg['ativo']);
            $configuracao = isset($cfg['config']) ? json_encode($cfg['config']) : json_encode([]);

            $this->db->query("
                INSERT INTO mgt_metodos_pagamento (nome, identificador, ativo, configuracao)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    nome = VALUES(nome),
                    ativo = VALUES(ativo),
                    configuracao = VALUES(configuracao),
                    atualizado_em = NOW()
            ", [$nome, $identificador, $ativo ? 1 : 0, $configuracao]);
        }

        return $this->success(['message' => 'Configurações salvas']);
    }

    /**
     * Mapear status de pagamento para rótulo amigável
     */
    private function mapStatusPagamento($status) {
        switch ($status) {
            case 'aprovado':
                return 'Sucesso';
            case 'pendente':
            case 'processando':
                return 'Aguardando';
            case 'recusado':
            case 'cancelado':
            case 'estornado':
                return 'Erro';
            default:
                return ucfirst($status);
        }
    }

    /**
     * Upsert de configuração simples
     */
    private function upsertConfig($chave, $valor, $tipo = 'string', $descricao = '', $categoria = 'geral') {
        $this->db->query("
            INSERT INTO mgt_configuracoes (chave, valor, tipo, descricao, categoria)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                valor = VALUES(valor),
                tipo = VALUES(tipo),
                descricao = VALUES(descricao),
                categoria = VALUES(categoria),
                atualizado_em = NOW()
        ", [$chave, strval($valor), $tipo, $descricao, $categoria]);
    }
    
    /**
     * Obter configuração
     */
    private function getConfig($chave, $padrao = null) {
        $config = $this->db->fetchOne("
            SELECT valor, tipo FROM mgt_configuracoes WHERE chave = ?
        ", [$chave]);
        
        if (!$config) {
            return $padrao;
        }
        
        switch ($config['tipo']) {
            case 'int':
                return intval($config['valor']);
            case 'float':
                return floatval($config['valor']);
            case 'bool':
                return $config['valor'] === 'true';
            case 'json':
                return json_decode($config['valor'], true);
            default:
                return $config['valor'];
        }
    }
    
    /**
     * Resposta de sucesso
     */
    private function success($data, $code = 200) {
        http_response_code($code);
        return ['success' => true, 'data' => $data];
    }
    
    /**
     * Resposta de erro
     */
    private function error($message, $code = 400) {
        http_response_code($code);
        return ['success' => false, 'error' => $message];
    }
}

// ====================================
// ROTEAMENTO
// ====================================

$api = new StoreAPI();
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? '';

try {
    switch ($method) {
        case 'POST':
            if ($path === 'transactions') {
                $data = json_decode(file_get_contents('php://input'), true);
                $result = $api->createTransaction($data);
            } elseif (preg_match('/transactions\/(\d+)\/payment/', $path, $matches)) {
                $data = json_decode(file_get_contents('php://input'), true);
                $result = $api->updatePaymentStatus($matches[1], $data);
            } elseif ($path === 'config') {
                $data = json_decode(file_get_contents('php://input'), true) ?: [];
                $result = $api->saveConfigs($data);
            } else {
                $result = ['success' => false, 'error' => 'Endpoint não encontrado'];
            }
            break;
            
        case 'GET':
            if ($path === 'transactions') {
                $result = $api->listTransactions($_GET);
            } elseif ($path === 'config') {
                $result = $api->getConfigs();
            } else {
                $result = ['success' => false, 'error' => 'Endpoint não encontrado'];
            }
            break;
            
        default:
            $result = ['success' => false, 'error' => 'Método não permitido'];
            break;
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
