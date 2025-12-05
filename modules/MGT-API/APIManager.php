<?php
/**
 * MGT-API Module
 * API REST do Servidor Magnatas
 * 
 * Responsável por:
 * - Endpoints para comunicação com o site
 * - Endpoints para o Minecraft Server
 * - Endpoints para apps mobile (futuro)
 */

namespace MGT\API;

/**
 * Classe base para gerenciamento de API
 */
class APIManager {
    
    /**
     * Define o tipo de resposta como JSON
     */
    public static function setJSONResponse() {
        header('Content-Type: application/json; charset=utf-8');
    }
    
    /**
     * Retorna uma resposta de sucesso em JSON
     */
    public static function success($data, $message = 'Sucesso', $code = 200) {
        self::setJSONResponse();
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
        exit;
    }
    
    /**
     * Retorna uma resposta de erro em JSON
     */
    public static function error($message, $code = 400, $errors = []) {
        self::setJSONResponse();
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ]);
        exit;
    }
    
    /**
     * Obtém o método HTTP
     */
    public static function getMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    /**
     * Obtém dados POST em JSON
     */
    public static function getJSONData() {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }
    
    /**
     * Valida um token de autenticação
     */
    public static function validateToken($token) {
        // TODO: Implementar validação de token
        return true;
    }
}

?>
