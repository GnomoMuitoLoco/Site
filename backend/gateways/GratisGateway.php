<?php
namespace MGT\Payment\Gateways;

class GratisGateway {
    public function __construct($config = []) {}

    public function process($amount, $description, $metadata) {
        // Aprova automaticamente
        return [
            'success' => true,
            'transaction_id' => uniqid('gratis_', true),
            'approval_url' => 'http://localhost:8000/payment-test.html?method=gratis&id=' . ($metadata['transaction_id'] ?? uniqid()),
        ];
    }

    public function getStatus($transactionId) {
        return [ 'success' => true, 'status' => 'aprovado' ];
    }

    public function handleWebhook($payload) {
        return [ 'success' => true ];
    }

    public function getInfo() {
        return [
            'nome' => 'Grátis',
            'descricao' => 'Pagamento de teste (aprovado automaticamente)',
            'icone' => '🎁',
        ];
    }
}
