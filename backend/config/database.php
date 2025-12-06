<?php
// Banco fake para modo de teste
class FakeDB {
    public function fetchOne($sql, $params = []) {
        // Simula método de pagamento grátis ativo
        if (strpos($sql, 'mgt_metodos_pagamento') !== false && isset($params[0]) && $params[0] === 'gratis') {
            return [
                'tipo' => 'gratis',
                'ativo' => true
            ];
        }
        // Simula outros gateways desativados
        return null;
    }
    public function insert($sql, $params = []) { return true; }
    public function query($sql, $params = []) { return true; }
}
$pdo = new FakeDB();
