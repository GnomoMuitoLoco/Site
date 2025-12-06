<?php
/**
 * Gerenciador de Gateways de Pagamento
 * Servidor Magnatas
 */

namespace MGT\Payment;

use MGT\Payment\Gateways\PayPalGateway;
use MGT\Payment\Gateways\MercadoPagoGateway;
use MGT\Payment\Gateways\PIXGateway;
use MGT\Payment\Gateways\GratisGateway;

class PaymentManager {
    
    private $gateways = [];
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
        $this->initializeGateways();
    }
    
    /**
     * Inicializar gateways
     */
    private function initializeGateways() {
        // PayPal
        $paypalConfig = $this->getGatewayConfig('paypal');
        if ($paypalConfig && $paypalConfig['ativo']) {
            $this->gateways['paypal'] = new PayPalGateway([
                'api_key' => $paypalConfig['client_id'],
                'api_secret' => $paypalConfig['secret'],
                'production' => $paypalConfig['producao'] ?? false
            ]);
        }
        
        // Mercado Pago
        $mpConfig = $this->getGatewayConfig('mercadopago');
        if ($mpConfig && $mpConfig['ativo']) {
            $this->gateways['mercadopago'] = new MercadoPagoGateway([
                'api_key' => $mpConfig['access_token'],
                'public_key' => $mpConfig['public_key'],
                'production' => $mpConfig['producao'] ?? false
            ]);
        }
        
        // PIX
        $pixConfig = $this->getGatewayConfig('pix');
        if ($pixConfig && $pixConfig['ativo']) {
            $this->gateways['pix'] = new PIXGateway([
                'pix_key' => $pixConfig['chave'],
                'beneficiary' => $pixConfig['beneficiario']
            ]);
        }
        
        // Grátis (teste)
        $gratisConfig = $this->getGatewayConfig('gratis');
        if ($gratisConfig && $gratisConfig['ativo']) {
            $this->gateways['gratis'] = new GratisGateway();
        }
    }
    
    /**
     * Processar pagamento
     */
    public function processPayment($method, $amount, $description, $metadata) {
        if (!isset($this->gateways[$method])) {
            return [
                'success' => false,
                'error' => "Método de pagamento não disponível: $method"
            ];
        }
        
        try {
            $gateway = $this->gateways[$method];
            $result = $gateway->process($amount, $description, $metadata);
            
            if ($result['success']) {
                // Registrar na tabela de pagamentos
                $this->logPayment($method, $metadata['transaction_id'], $result['transaction_id'], 'pendente');
            }
            
            return $result;
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar status de pagamento
     */
    public function checkPaymentStatus($method, $transactionId) {
        if (!isset($this->gateways[$method])) {
            return [
                'success' => false,
                'error' => 'Método não disponível'
            ];
        }
        
        return $this->gateways[$method]->getStatus($transactionId);
    }
    
    /**
     * Processar webhook
     */
    public function handleWebhook($method, $payload) {
        if (!isset($this->gateways[$method])) {
            return [
                'success' => false,
                'error' => 'Método não disponível'
            ];
        }
        
        return $this->gateways[$method]->handleWebhook($payload);
    }
    
    /**
     * Listar gateways disponíveis
     */
    public function getAvailableGateways() {
        return array_keys($this->gateways);
    }
    
    /**
     * Obter informações de gateway
     */
    public function getGatewayInfo($method) {
        if (!isset($this->gateways[$method])) {
            return null;
        }
        
        return $this->gateways[$method]->getInfo();
    }
    
    /**
     * Obter configuração de gateway do banco
     */
    private function getGatewayConfig($metodo) {
        return $this->db->fetchOne(
            "SELECT * FROM mgt_metodos_pagamento WHERE tipo = ? AND ativo = TRUE",
            [$metodo]
        );
    }
    
    /**
     * Registrar tentativa de pagamento
     */
    private function logPayment($method, $transacaoId, $gatewayTransactionId, $status) {
        $this->db->insert(
            "INSERT INTO mgt_pagamentos (transacao_id, metodo, gateway_transaction_id, status, criado_em)
             VALUES (?, ?, ?, ?, NOW())",
            [$transacaoId, $method, $gatewayTransactionId, $status]
        );
    }
    
    /**
     * Atualizar status de pagamento
     */
    public function updatePaymentStatus($transacaoId, $status, $gatewayData = null) {
        $this->db->query(
            "UPDATE mgt_transacoes 
             SET status_pagamento = ?, 
                 pagamento_dados = ?,
                 atualizado_em = NOW()
             WHERE id = ?",
            [$status, json_encode($gatewayData), $transacaoId]
        );
    }
    
    /**
     * Capturar pagamento (para gateways que necessitam)
     */
    public function capturePayment($method, $gatewayTransactionId) {
        if ($method !== 'paypal') {
            return ['success' => true]; // Apenas PayPal requer captura
        }
        
        if (!isset($this->gateways['paypal'])) {
            return ['success' => false, 'error' => 'PayPal não configurado'];
        }
        
        return $this->gateways['paypal']->capturePayment($gatewayTransactionId);
    }
}
