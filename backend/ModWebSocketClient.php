<?php
/**
 * Cliente WebSocket para Comunicação com Mod
 * Servidor Magnatas
 */

namespace MGT\WebSocket;

class ModWebSocketClient {
    
    private $uri;
    private $socket;
    private $connected = false;
    private $apiKey;
    
    public function __construct($wsUri, $apiKey) {
        $this->uri = $wsUri;
        $this->apiKey = $apiKey;
    }
    
    /**
     * Conectar ao WebSocket
     */
    public function connect(): bool {
        try {
            // Parse URI
            $parts = parse_url($this->uri);
            $host = $parts['host'];
            $port = $parts['port'] ?? 80;
            $path = $parts['path'] ?? '/ws';
            
            // Criar socket
            $this->socket = @fsockopen($host, $port, $errno, $errstr, 5);
            
            if (!$this->socket) {
                error_log("WebSocket connection failed: $errstr ($errno)");
                return false;
            }
            
            // Enviar handshake HTTP
            $key = base64_encode(random_bytes(16));
            $handshake = "GET $path HTTP/1.1\r\n";
            $handshake .= "Host: $host:$port\r\n";
            $handshake .= "Upgrade: websocket\r\n";
            $handshake .= "Connection: Upgrade\r\n";
            $handshake .= "Sec-WebSocket-Key: $key\r\n";
            $handshake .= "Sec-WebSocket-Version: 13\r\n";
            $handshake .= "\r\n";
            
            fwrite($this->socket, $handshake);
            
            // Ler resposta
            $response = fgets($this->socket, 1024);
            if (strpos($response, '101') === false) {
                fclose($this->socket);
                error_log("WebSocket handshake failed");
                return false;
            }
            
            // Ler headers até linha vazia
            while (true) {
                $line = fgets($this->socket, 1024);
                if (trim($line) === '') break;
            }
            
            $this->connected = true;
            
            // Autenticar
            $this->authenticate();
            
            return true;
            
        } catch (\Exception $e) {
            error_log("WebSocket connection error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Desconectar
     */
    public function disconnect() {
        if ($this->socket) {
            fclose($this->socket);
            $this->connected = false;
        }
    }
    
    /**
     * Autenticar no WebSocket
     */
    private function authenticate() {
        $authMessage = [
            'type' => 'auth',
            'api_key' => $this->apiKey
        ];
        
        $this->sendMessage($authMessage);
    }
    
    /**
     * Enviar mensagem
     */
    public function sendMessage($data): bool {
        if (!$this->connected || !$this->socket) {
            return false;
        }
        
        try {
            $json = json_encode($data);
            $frame = $this->createFrame($json);
            
            fwrite($this->socket, $frame);
            return true;
            
        } catch (\Exception $e) {
            error_log("Error sending WebSocket message: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Receber mensagem
     */
    public function receiveMessage(): ?array {
        if (!$this->connected || !$this->socket) {
            return null;
        }
        
        try {
            // Ler frame
            $frame = $this->readFrame();
            if (!$frame) {
                return null;
            }
            
            return json_decode($frame, true);
            
        } catch (\Exception $e) {
            error_log("Error receiving WebSocket message: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Criar frame WebSocket
     */
    private function createFrame($payload): string {
        $len = strlen($payload);
        
        if ($len <= 125) {
            $frame = chr(0x81) . chr($len | 0x80);
        } elseif ($len <= 65535) {
            $frame = chr(0x81) . chr(0xFE | 0x80) . pack('n', $len);
        } else {
            $frame = chr(0x81) . chr(0xFF | 0x80) . pack('J', $len);
        }
        
        // Gerar chave de máscara
        $mask = random_bytes(4);
        $frame .= $mask;
        
        // Aplicar máscara ao payload
        for ($i = 0; $i < strlen($payload); $i++) {
            $frame .= chr(ord($payload[$i]) ^ ord($mask[$i % 4]));
        }
        
        return $frame;
    }
    
    /**
     * Ler frame WebSocket
     */
    private function readFrame(): ?string {
        if (feof($this->socket)) {
            return null;
        }
        
        $header = fread($this->socket, 2);
        if (strlen($header) < 2) {
            return null;
        }
        
        $byte1 = ord($header[0]);
        $byte2 = ord($header[1]);
        
        $fin = (bool)($byte1 & 0x80);
        $opcode = $byte1 & 0x0f;
        $masked = (bool)($byte2 & 0x80);
        
        // Obter tamanho do payload
        $len = $byte2 & 0x7f;
        
        if ($len == 126) {
            $len_bytes = fread($this->socket, 2);
            $len = unpack('n', $len_bytes)[1];
        } elseif ($len == 127) {
            $len_bytes = fread($this->socket, 8);
            $len = unpack('J', $len_bytes)[1];
        }
        
        // Ler máscara se presente
        if ($masked) {
            fread($this->socket, 4); // Descartar máscara do servidor
        }
        
        // Ler payload
        $payload = fread($this->socket, $len);
        
        return $payload;
    }
    
    /**
     * Notificar jogador entrou
     */
    public function notifyPlayerJoin($playerName, $uuid): bool {
        return $this->sendMessage([
            'type' => 'event',
            'event_type' => 'player_join',
            'player' => $playerName,
            'uuid' => $uuid,
            'timestamp' => date('c')
        ]);
    }
    
    /**
     * Notificar compra entregue
     */
    public function notifyPurchaseDelivered($transactionId, $playerName, $amount): bool {
        return $this->sendMessage([
            'type' => 'event',
            'event_type' => 'purchase_delivered',
            'transaction_id' => $transactionId,
            'player' => $playerName,
            'amount' => $amount,
            'timestamp' => date('c')
        ]);
    }
    
    /**
     * Verificar se está conectado
     */
    public function isConnected(): bool {
        return $this->connected && is_resource($this->socket);
    }
}
