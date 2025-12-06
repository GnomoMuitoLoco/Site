<?php
/**
 * Gateway de Pagamento PIX
 * Servidor Magnatas
 */

namespace MGT\Payment\Gateways;

use MGT\Payment\PaymentGateway;

class PIXGateway extends PaymentGateway {
    
    /**
     * Validar configuração
     */
    public function validateConfig(): bool {
        return !empty($this->config['pix_key']) && !empty($this->config['beneficiary']);
    }
    
    /**
     * Processar pagamento (gerar QR Code PIX)
     */
    public function process($amount, $description, $metadata): array {
        if (!$this->validateConfig()) {
            return [
                'success' => false,
                'error' => 'PIX não está configurado'
            ];
        }
        
        try {
            $transactionId = $metadata['transaction_id'] ?? uniqid('PIX_');
            
            // Gerar payload PIX (brcode)
            $pixPayload = $this->generatePixPayload(
                $amount,
                $this->config['pix_key'],
                $this->config['beneficiary'],
                $transactionId,
                $description
            );
            
            // Gerar QR Code
            $qrCode = $this->generateQRCode($pixPayload);
            
            $this->log("PIX gerado: $transactionId", 'info');
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'status' => 'pending',
                'pix_payload' => $pixPayload,
                'qr_code' => $qrCode,
                'amount' => $amount,
                'key' => $this->config['pix_key'],
                'beneficiary' => $this->config['beneficiary']
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
     * Verificar status de um pagamento PIX
     */
    public function getStatus($transactionId): array {
        // PIX requer integração com banco do recebedor
        // Esta é uma implementação simplificada
        // Em produção, integrar com sistema de confirmação do banco
        
        try {
            // Aqui seria feita integração com API do banco para verificar confirmação
            // Por enquanto, sempre retorna pending até confirmação manual
            
            return [
                'success' => true,
                'status' => 'pending',
                'raw_status' => 'awaiting_confirmation',
                'message' => 'Aguardando confirmação de pagamento PIX'
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
     * Processar webhook (confirmação de PIX)
     */
    public function handleWebhook($data): array {
        // Implementar webhook do banco para confirmar PIX
        
        if (!isset($data['pix_id']) || !isset($data['amount'])) {
            return [
                'success' => false,
                'error' => 'Dados de webhook inválidos'
            ];
        }
        
        try {
            $this->log("Webhook PIX recebido: {$data['pix_id']}", 'info');
            
            return [
                'success' => true,
                'action' => 'pix_confirmed',
                'pix_id' => $data['pix_id'],
                'amount' => $data['amount'],
                'timestamp' => $data['timestamp'] ?? date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 'error');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Validar assinatura de webhook
     */
    public function validateWebhookSignature($payload, $signature): bool {
        // Validar assinatura de webhook do banco
        // Implementação específica por banco
        return true;
    }
    
    /**
     * Obter URL de redirecionamento
     */
    public function getRedirectUrl($transactionData): string {
        // PIX não usa redirecionamento, retorna para página de espera
        return $_SERVER['HTTP_ORIGIN'] . '/checkout-pix-waiting.html';
    }
    
    /**
     * Gerar payload PIX (EMV Code)
     * Formato: Estrutura de Dados Maestro (EMV-QRCPS-01)
     */
    private function generatePixPayload(
        $amount,
        $pixKey,
        $beneficiary,
        $referenceId,
        $description
    ): string {
        // Gerar brcode simplificado
        // Em produção, usar biblioteca oficial do Banco Central
        
        $amount_str = str_pad((int)($amount * 100), 13, '0', STR_PAD_LEFT);
        
        $payload = "00020126580014br.gov.bcb.pix" .
                   "0136" . md5($pixKey) . // chave PIX (simplificado)
                   "52040000" .
                   "5303986" . // Brasil
                   "54" . str_pad(strlen($amount_str), 2, '0', STR_PAD_LEFT) . $amount_str .
                   "5802BR" .
                   "59" . str_pad(strlen($beneficiary), 2, '0', STR_PAD_LEFT) . $beneficiary .
                   "60" . str_pad(strlen($referenceId), 2, '0', STR_PAD_LEFT) . $referenceId;
        
        // Calcular checksum CRC16
        $checksum = $this->calculateCRC16($payload);
        
        return $payload . $checksum;
    }
    
    /**
     * Calcular CRC16 para PIX
     */
    private function calculateCRC16($data): string {
        $crc = 0xFFFF;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $byte = ord($data[$i]);
            $crc ^= ($byte << 8);
            
            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) > 0) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }
        
        return '6304' . strtoupper(dechex($crc));
    }
    
    /**
     * Gerar QR Code
     */
    private function generateQRCode($pixPayload): string {
        // Usar biblioteca qrcode externa ou serviço online
        // Exemplo usando Google Charts API (não recomendado em produção)
        
        $encodedPayload = urlencode($pixPayload);
        return "https://chart.googleapis.com/chart?chs=400x400&chld=M|0&cht=qr&chl=$encodedPayload";
    }
    
    /**
     * Gerar QR Code (versão em Data URI - PNG)
     * Requer extensão GD do PHP
     */
    public function generateQRCodeAsDataURI($pixPayload): string {
        // Implementação usando biblioteca PHP nativa ou externa
        // Exemplo simplificado - em produção, usar biblioteca como 'endroid/qr-code'
        
        $url = "https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=" . 
               urlencode($pixPayload);
        
        $imageData = file_get_contents($url);
        return 'data:image/png;base64,' . base64_encode($imageData);
    }
    
    /**
     * Validar chave PIX
     */
    public function validatePixKey($key): bool {
        // Email
        if (filter_var($key, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        
        // Telefone (11 dígitos com DDD)
        if (preg_match('/^\d{11}$/', preg_replace('/\D/', '', $key))) {
            return true;
        }
        
        // CPF (11 dígitos)
        if (preg_match('/^\d{11}$/', preg_replace('/\D/', '', $key))) {
            return true;
        }
        
        // CNPJ (14 dígitos)
        if (preg_match('/^\d{14}$/', preg_replace('/\D/', '', $key))) {
            return true;
        }
        
        // UUID (aleatória)
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $key)) {
            return true;
        }
        
        return false;
    }
    
    public static function getName(): string {
        return 'PIX';
    }
}
