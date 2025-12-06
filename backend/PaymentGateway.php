<?php
/**
 * Classe Base Abstrata para Gateways de Pagamento
 * Servidor Magnatas
 */

namespace MGT\Payment;

abstract class PaymentGateway {
    
    protected $config = [];
    protected $apiKey;
    protected $apiSecret;
    protected $isProduction = false;
    
    /**
     * Construtor
     */
    public function __construct($config = []) {
        $this->config = $config;
        $this->apiKey = $config['api_key'] ?? null;
        $this->apiSecret = $config['api_secret'] ?? null;
        $this->isProduction = $config['production'] ?? false;
    }
    
    /**
     * Validar configuração
     */
    abstract public function validateConfig(): bool;
    
    /**
     * Processar pagamento
     */
    abstract public function process($amount, $description, $metadata): array;
    
    /**
     * Verificar status de um pagamento
     */
    abstract public function getStatus($transactionId): array;
    
    /**
     * Processar webhook
     */
    abstract public function handleWebhook($data): array;
    
    /**
     * Validar requisição de webhook
     */
    abstract public function validateWebhookSignature($payload, $signature): bool;
    
    /**
     * Obter URL de redirecionamento
     */
    abstract public function getRedirectUrl($transactionData): string;
    
    /**
     * Fazer requisição HTTP
     */
    protected function makeRequest($method, $url, $data = null, $headers = []) {
        $ch = curl_init($url);
        
        $defaultHeaders = [
            'Content-Type: application/json',
            'User-Agent: MagnatasStoreAPI/1.0'
        ];
        
        $allHeaders = array_merge($defaultHeaders, $headers);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_CUSTOMREQUEST => $method
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'http_code' => 0
            ];
        }
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'body' => json_decode($response, true) ?? $response,
            'raw' => $response
        ];
    }
    
    /**
     * Log de operação
     */
    protected function log($message, $level = 'info') {
        $timestamp = date('Y-m-d H:i:s');
        $logFile = dirname(__DIR__) . '/logs/payment_' . date('Y-m-d') . '.log';
        
        // Criar diretório se não existir
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents(
            $logFile,
            "[$timestamp] [$level] " . static::class . ": $message\n",
            FILE_APPEND
        );
    }
    
    /**
     * Gerar hash de segurança
     */
    protected function generateSignature($data, $secret = null) {
        $secret = $secret ?? $this->apiSecret;
        return hash_hmac('sha256', $data, $secret);
    }
    
    /**
     * Formatar valor monetário
     */
    protected function formatAmount($amount, $decimals = 2) {
        return number_format($amount, $decimals, '', '');
    }
    
    /**
     * Obter informações do gateway
     */
    public function getInfo() {
        return [
            'name' => static::getName(),
            'configured' => $this->validateConfig(),
            'production' => $this->isProduction
        ];
    }
    
    /**
     * Nome do gateway (override em subclasses)
     */
    public static function getName(): string {
        return 'Unknown Gateway';
    }
}
