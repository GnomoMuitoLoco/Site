<?php
/**
 * MGT-Utils Module
 * Utilitários e funções auxiliares
 * 
 * Responsável por:
 * - Funções de formatação
 * - Helpers diversos
 * - Validações
 */

namespace MGT\Utils;

/**
 * Classe de utilitários
 */
class Utils {
    
    /**
     * Formata um valor monetário
     */
    public static function formatMoney($value) {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
    
    /**
     * Sanitiza entrada do usuário
     */
    public static function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Valida email
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Gera um UUID simples
     */
    public static function generateUUID() {
        return bin2hex(random_bytes(16));
    }
    
    /**
     * Formata uma data
     */
    public static function formatDate($date, $format = 'd/m/Y H:i:s') {
        return date($format, strtotime($date));
    }
    
    /**
     * Retorna a diferença de tempo em formato legível
     */
    public static function timeAgo($timestamp) {
        $time_ago = strtotime($timestamp);
        $time = time() - $time_ago;
        
        $tokens = array (
            31536000 => 'ano',
            2592000 => 'mês',
            604800 => 'semana',
            86400 => 'dia',
            3600 => 'hora',
            60 => 'minuto',
            1 => 'segundo'
        );
        
        foreach ($tokens as $unit => $text) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '') . ' atrás';
        }
    }
    
    /**
     * Faz um redirect seguro
     */
    public static function redirect($url, $external = false) {
        if (!$external) {
            $url = APP_URL . $url;
        }
        header('Location: ' . $url);
        exit;
    }
}

?>
