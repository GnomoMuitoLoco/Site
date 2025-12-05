<?php
/**
 * MGT-Dashboard Module
 * Sistema de Dashboard do Servidor Magnatas
 * 
 * Responsável por:
 * - Interface de administração
 * - Exibição de estatísticas
 * - Menu principal
 */

namespace MGT\Dashboard;

/**
 * Classe de gerenciamento do Dashboard
 */
class DashboardManager {
    
    /**
     * Obtém as estatísticas do servidor
     */
    public static function getStats() {
        return [
            'status' => 'online',
            'visitors' => rand(100, 1000),
            'php_version' => phpversion(),
            'current_time' => date('H:i:s'),
            'current_date' => date('d/m/Y'),
        ];
    }
    
    /**
     * Obtém informações do sistema
     */
    public static function getSystemInfo() {
        return [
            'logged_user' => $_SESSION['username'] ?? 'Admin',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
            'host' => $_SERVER['HTTP_HOST'] ?? 'localhost:8000',
            'os' => php_uname('s'),
            'timezone' => date_default_timezone_get(),
        ];
    }
    
    /**
     * Obtém os itens do menu principal
     */
    public static function getMenuItems() {
        return [
            [
                'id' => 'home',
                'label' => '📊 Dashboard',
                'icon' => '📊',
                'active' => true,
            ],
            [
                'id' => 'loja',
                'label' => '🛍️ Loja',
                'icon' => '🛍️',
            ],
            [
                'id' => 'servidores',
                'label' => '🎮 Servidores',
                'icon' => '🎮',
            ],
            [
                'id' => 'usuarios',
                'label' => '👥 Usuários',
                'icon' => '👥',
            ],
            [
                'id' => 'configuracoes',
                'label' => '⚙️ Configurações',
                'icon' => '⚙️',
            ],
            [
                'id' => 'site',
                'label' => '🌐 Ver Site',
                'icon' => '🌐',
                'href' => '/index.html',
            ],
        ];
    }
    
    /**
     * Obtém os itens da loja
     */
    public static function getStoreItems() {
        return [
            [
                'id' => 'produtos',
                'title' => 'Produtos',
                'icon' => '📦',
                'description' => 'Cadastrar e gerenciar produtos da loja',
            ],
            [
                'id' => 'categorias',
                'title' => 'Categorias',
                'icon' => '🏷️',
                'description' => 'Criar categorias para vincular produtos',
            ],
            [
                'id' => 'servidores',
                'title' => 'Servidores',
                'icon' => '🎮',
                'description' => 'Vincular Remote Console para entrega automática',
            ],
            [
                'id' => 'cupons',
                'title' => 'Cupons',
                'icon' => '🎟️',
                'description' => 'Criar cupons de desconto para a loja',
            ],
            [
                'id' => 'registros',
                'title' => 'Registros',
                'icon' => '📋',
                'description' => 'Verificar todos os pedidos e status',
            ],
            [
                'id' => 'meta',
                'title' => 'Meta da Comunidade',
                'icon' => '🎯',
                'description' => 'Valor mensal a ser atingido',
            ],
        ];
    }
}

?>
