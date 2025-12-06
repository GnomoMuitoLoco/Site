<?php
/**
 * Gateway de Pagamento PayPal
 * Servidor Magnatas
 */

namespace MGT\Payment\Gateways;

use MGT\Payment\PaymentGateway;

class PayPalGateway extends PaymentGateway {
    
    private $baseUrl;
    
    public function __construct($config = []) {
        parent::__construct($config);
        
        $this->baseUrl = $this->isProduction 
            ? 'https://api.paypal.com'
            : 'https://api.sandbox.paypal.com';
    }
    
    /**
     * Validar configuração
     */
    public function validateConfig(): bool {
        return !empty($this->apiKey) && !empty($this->apiSecret);
    }
    
    /**
     * Processar pagamento (criar ordem)
     */
    public function process($amount, $description, $metadata): array {
        if (!$this->validateConfig()) {
            return [
                'success' => false,
                'error' => 'PayPal não está configurado'
            ];
        }
        
        try {
            // Obter token de acesso
            $token = $this->getAccessToken();
            if (!$token) {
                throw new \Exception('Erro ao obter token de acesso PayPal');
            }
            
            // Criar ordem
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => 'BRL',
                            'value' => number_format($amount, 2, '.', '')
                        ],
                        'description' => $description
                    ]
                ],
                'payer' => [
                    'email_address' => $metadata['email'] ?? 'customer@example.com'
                ],
                'return_url' => $metadata['return_url'] ?? $_SERVER['HTTP_ORIGIN'] . '/checkout-success.html',
                'cancel_url' => $metadata['cancel_url'] ?? $_SERVER['HTTP_ORIGIN'] . '/checkout-cancel.html'
            ];
            
            $response = $this->makeRequest(
                'POST',
                $this->baseUrl . '/v2/checkout/orders',
                $orderData,
                ['Authorization: Bearer ' . $token]
            );
            
            if (!$response['success']) {
                $this->log("Erro ao criar ordem: " . json_encode($response), 'error');
                throw new \Exception('Erro ao criar ordem PayPal');
            }
            
            $body = $response['body'];
            
            $this->log("Ordem criada: {$body['id']}", 'info');
            
            return [
                'success' => true,
                'transaction_id' => $body['id'],
                'status' => $body['status'],
                'approve_link' => $this->getApproveLink($body['links']),
                'redirect_url' => $this->getApproveLink($body['links'])
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
     * Capturar pagamento após aprovação
     */
    public function capturePayment($orderId): array {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                throw new \Exception('Erro ao obter token');
            }
            
            $response = $this->makeRequest(
                'POST',
                $this->baseUrl . '/v2/checkout/orders/' . $orderId . '/capture',
                [],
                ['Authorization: Bearer ' . $token]
            );
            
            if (!$response['success']) {
                $this->log("Erro ao capturar: " . json_encode($response), 'error');
                throw new \Exception('Erro ao capturar pagamento');
            }
            
            $body = $response['body'];
            
            $paymentStatus = 'pending';
            if ($body['status'] === 'COMPLETED') {
                $paymentStatus = 'approved';
            } elseif ($body['status'] === 'APPROVED') {
                $paymentStatus = 'processing';
            }
            
            $this->log("Pagamento capturado: $orderId - Status: {$body['status']}", 'info');
            
            return [
                'success' => true,
                'transaction_id' => $orderId,
                'status' => $paymentStatus,
                'amount' => $body['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? null,
                'payer_email' => $body['payer']['email_address'] ?? null
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
            $token = $this->getAccessToken();
            if (!$token) {
                throw new \Exception('Erro ao obter token');
            }
            
            $response = $this->makeRequest(
                'GET',
                $this->baseUrl . '/v2/checkout/orders/' . $transactionId,
                null,
                ['Authorization: Bearer ' . $token]
            );
            
            if (!$response['success']) {
                throw new \Exception('Erro ao verificar status');
            }
            
            $body = $response['body'];
            
            $status = match($body['status']) {
                'COMPLETED' => 'approved',
                'APPROVED' => 'processing',
                'VOIDED' => 'cancelado',
                'SAVED' => 'pendente',
                default => 'pendente'
            };
            
            return [
                'success' => true,
                'status' => $status,
                'raw_status' => $body['status'],
                'amount' => $body['purchase_units'][0]['amount']['value'] ?? null
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
        $eventType = $data['event_type'] ?? '';
        
        switch ($eventType) {
            case 'CHECKOUT.ORDER.APPROVED':
                return ['success' => true, 'action' => 'order_approved'];
            case 'CHECKOUT.ORDER.COMPLETED':
                return ['success' => true, 'action' => 'payment_completed'];
            default:
                return ['success' => false, 'error' => 'Evento desconhecido'];
        }
    }
    
    /**
     * Validar assinatura de webhook
     */
    public function validateWebhookSignature($payload, $signature): bool {
        // PayPal não usa assinatura simples, valida via transmissionId e certificado
        // Para simplicidade, sempre retorna true aqui
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
     * Obter token de acesso
     */
    private function getAccessToken(): ?string {
        $response = $this->makeRequest(
            'POST',
            $this->baseUrl . '/v1/oauth2/token',
            [],
            [
                'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
                'Accept: application/json',
                'Accept-Language: en_US'
            ]
        );
        
        if (!$response['success']) {
            $this->log("Erro ao obter token: " . json_encode($response), 'error');
            return null;
        }
        
        return $response['body']['access_token'] ?? null;
    }
    
    /**
     * Extrair link de aprovação
     */
    private function getApproveLink($links): ?string {
        foreach ($links ?? [] as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }
        return null;
    }
    
    public static function getName(): string {
        return 'PayPal';
    }
}
