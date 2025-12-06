<?php
/**
 * Gateway de Pagamento Mercado Pago
 * Servidor Magnatas
 */

namespace MGT\Payment\Gateways;

use MGT\Payment\PaymentGateway;

class MercadoPagoGateway extends PaymentGateway {
    
    private $baseUrl = 'https://api.mercadopago.com';
    
    /**
     * Validar configuração
     */
    public function validateConfig(): bool {
        return !empty($this->apiKey);
    }
    
    /**
     * Processar pagamento (criar preferência)
     */
    public function process($amount, $description, $metadata): array {
        if (!$this->validateConfig()) {
            return [
                'success' => false,
                'error' => 'Mercado Pago não está configurado'
            ];
        }
        
        try {
            $preferenceData = [
                'items' => [
                    [
                        'title' => $description,
                        'unit_price' => floatval($amount),
                        'quantity' => 1,
                        'currency_id' => 'BRL'
                    ]
                ],
                'payer' => [
                    'email' => $metadata['email'] ?? 'customer@example.com'
                ],
                'back_urls' => [
                    'success' => $metadata['return_url'] ?? $_SERVER['HTTP_ORIGIN'] . '/checkout-success.html',
                    'failure' => $metadata['cancel_url'] ?? $_SERVER['HTTP_ORIGIN'] . '/checkout-cancel.html',
                    'pending' => $metadata['pending_url'] ?? $_SERVER['HTTP_ORIGIN'] . '/checkout-pending.html'
                ],
                'auto_return' => 'approved',
                'external_reference' => $metadata['transaction_id'] ?? uniqid(),
                'payment_type' => 'account_money'
            ];
            
            $response = $this->makeRequest(
                'POST',
                $this->baseUrl . '/checkout/preferences',
                $preferenceData,
                ['Authorization: Bearer ' . $this->apiKey]
            );
            
            if (!$response['success']) {
                $this->log("Erro ao criar preferência: " . json_encode($response), 'error');
                throw new \Exception('Erro ao criar preferência Mercado Pago');
            }
            
            $body = $response['body'];
            
            $this->log("Preferência criada: {$body['id']}", 'info');
            
            return [
                'success' => true,
                'transaction_id' => $body['id'],
                'status' => 'pending',
                'init_point' => $body['init_point'],
                'redirect_url' => $body['init_point']
            ];
            
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 'error');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar status de um pagamento
     */
    public function getStatus($transactionId): array {
        try {
            // Buscar pagamentos pela referência externa
            $response = $this->makeRequest(
                'GET',
                $this->baseUrl . '/v1/payments/search?external_reference=' . $transactionId,
                null,
                ['Authorization: Bearer ' . $this->apiKey]
            );
            
            if (!$response['success'] || empty($response['body']['results'])) {
                throw new \Exception('Pagamento não encontrado');
            }
            
            $payment = $response['body']['results'][0];
            
            $status = match($payment['status']) {
                'approved' => 'approved',
                'pending' => 'processing',
                'authorized' => 'processing',
                'in_process' => 'processando',
                'in_mediation' => 'disputa',
                'rejected' => 'recusado',
                'cancelled' => 'cancelado',
                'refunded' => 'reembolsado',
                default => 'pendente'
            };
            
            return [
                'success' => true,
                'status' => $status,
                'raw_status' => $payment['status'],
                'amount' => $payment['transaction_amount'] ?? null,
                'payment_id' => $payment['id']
            ];
            
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 'error');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Processar webhook
     */
    public function handleWebhook($data): array {
        $type = $data['type'] ?? '';
        $id = $data['data']['id'] ?? null;
        
        if ($type === 'payment') {
            // Buscar status do pagamento
            $status = $this->getPaymentStatus($id);
            
            if ($status['status'] === 'approved') {
                return [
                    'success' => true,
                    'action' => 'payment_completed',
                    'payment_id' => $id
                ];
            } else {
                return [
                    'success' => true,
                    'action' => 'payment_status_changed',
                    'status' => $status['status'],
                    'payment_id' => $id
                ];
            }
        }
        
        return ['success' => false, 'error' => 'Tipo de webhook desconhecido'];
    }
    
    /**
     * Validar assinatura de webhook
     */
    public function validateWebhookSignature($payload, $signature): bool {
        // Mercado Pago valida webhooks por IP e tipo de evento
        // Para simplicidade, sempre retorna true
        // Em produção, implementar validação adequada
        return true;
    }
    
    /**
     * Obter URL de redirecionamento
     */
    public function getRedirectUrl($transactionData): string {
        return $transactionData['redirect_url'] ?? '';
    }
    
    /**
     * Obter status de um pagamento específico
     */
    private function getPaymentStatus($paymentId): array {
        try {
            $response = $this->makeRequest(
                'GET',
                $this->baseUrl . '/v1/payments/' . $paymentId,
                null,
                ['Authorization: Bearer ' . $this->apiKey]
            );
            
            if (!$response['success']) {
                throw new \Exception('Erro ao buscar pagamento');
            }
            
            $payment = $response['body'];
            
            $status = match($payment['status']) {
                'approved' => 'approved',
                'pending' => 'processing',
                'authorized' => 'processing',
                'in_process' => 'processando',
                'in_mediation' => 'disputa',
                'rejected' => 'recusado',
                'cancelled' => 'cancelado',
                'refunded' => 'reembolsado',
                default => 'pendente'
            };
            
            return [
                'status' => $status,
                'raw_status' => $payment['status'],
                'amount' => $payment['transaction_amount']
            ];
            
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 'error');
            return ['status' => 'unknown', 'error' => $e->getMessage()];
        }
    }
    
    public static function getName(): string {
        return 'Mercado Pago';
    }
}
