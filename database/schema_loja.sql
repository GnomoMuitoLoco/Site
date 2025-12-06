-- =============================================
-- SCHEMA DE BANCO DE DADOS - SISTEMA DE LOJA
-- Servidor Magnatas - MGT-Cash Store
-- =============================================

-- Tabela de Servidores Minecraft
CREATE TABLE IF NOT EXISTS mgt_servidores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    identificador VARCHAR(50) UNIQUE NOT NULL COMMENT 'Ex: mgt, atm10, atm10tts',
    endereco_ip VARCHAR(255) NOT NULL COMMENT 'IP:Porta do servidor',
    api_url VARCHAR(255) NOT NULL COMMENT 'URL da API REST do mod',
    api_key VARCHAR(255) NOT NULL COMMENT 'Chave de autenticação',
    websocket_url VARCHAR(255) COMMENT 'URL do WebSocket para eventos',
    status ENUM('online', 'offline', 'manutencao') DEFAULT 'offline',
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Produtos (MGT-Cash por enquanto)
CREATE TABLE IF NOT EXISTS mgt_produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    tipo ENUM('cash', 'item', 'rank', 'bundle') DEFAULT 'cash',
    quantidade INT NOT NULL COMMENT 'Quantidade de MGT-Cash ou ID do item',
    preco DECIMAL(10, 2) NOT NULL COMMENT 'Preço em R$',
    servidor_id INT COMMENT 'NULL = todos os servidores',
    comando_execucao TEXT COMMENT 'Comando a ser executado: {player}, {amount}',
    imagem_url VARCHAR(255),
    ativo BOOLEAN DEFAULT TRUE,
    ordem INT DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servidor_id) REFERENCES mgt_servidores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Cupons de Desconto
CREATE TABLE IF NOT EXISTS mgt_cupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    tipo ENUM('percentual', 'fixo') DEFAULT 'percentual',
    valor DECIMAL(10, 2) NOT NULL COMMENT 'Porcentagem (ex: 10) ou valor fixo (ex: 5.00)',
    valor_minimo DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Valor mínimo da compra',
    uso_maximo INT DEFAULT NULL COMMENT 'NULL = ilimitado',
    uso_atual INT DEFAULT 0,
    usa_por_usuario INT DEFAULT 1 COMMENT 'Quantas vezes cada usuário pode usar',
    valido_de DATETIME DEFAULT CURRENT_TIMESTAMP,
    valido_ate DATETIME DEFAULT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Transações/Pedidos
CREATE TABLE IF NOT EXISTS mgt_transacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_numero VARCHAR(20) UNIQUE NOT NULL COMMENT 'Ex: PED-001',
    jogador_nick VARCHAR(16) NOT NULL,
    jogador_email VARCHAR(255),
    servidor_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT DEFAULT 1,
    valor_bruto DECIMAL(10, 2) NOT NULL,
    cupom_id INT DEFAULT NULL,
    desconto DECIMAL(10, 2) DEFAULT 0.00,
    valor_total DECIMAL(10, 2) NOT NULL,
    
    -- Informações de pagamento
    metodo_pagamento VARCHAR(50) COMMENT 'paypal, mercadopago, pix, etc',
    status_pagamento ENUM('pendente', 'processando', 'aprovado', 'recusado', 'cancelado', 'estornado') DEFAULT 'pendente',
    transacao_id VARCHAR(255) COMMENT 'ID da transação no gateway',
    pagamento_dados JSON COMMENT 'Dados adicionais do pagamento',
    
    -- Status de entrega
    status_entrega ENUM('aguardando', 'enviado', 'entregue', 'falha', 'fila') DEFAULT 'aguardando',
    tentativas_entrega INT DEFAULT 0,
    entregue_em DATETIME DEFAULT NULL,
    erro_entrega TEXT,
    
    -- Metadados
    ip_comprador VARCHAR(45),
    user_agent TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (servidor_id) REFERENCES mgt_servidores(id),
    FOREIGN KEY (produto_id) REFERENCES mgt_produtos(id),
    FOREIGN KEY (cupom_id) REFERENCES mgt_cupons(id) ON DELETE SET NULL,
    INDEX idx_jogador (jogador_nick),
    INDEX idx_status_pagamento (status_pagamento),
    INDEX idx_status_entrega (status_entrega),
    INDEX idx_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Histórico de Uso de Cupons
CREATE TABLE IF NOT EXISTS mgt_cupom_uso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cupom_id INT NOT NULL,
    transacao_id INT NOT NULL,
    jogador_nick VARCHAR(16) NOT NULL,
    usado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cupom_id) REFERENCES mgt_cupons(id) ON DELETE CASCADE,
    FOREIGN KEY (transacao_id) REFERENCES mgt_transacoes(id) ON DELETE CASCADE,
    INDEX idx_cupom_jogador (cupom_id, jogador_nick)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Configurações do Sistema
CREATE TABLE IF NOT EXISTS mgt_configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    tipo ENUM('string', 'int', 'float', 'bool', 'json') DEFAULT 'string',
    descricao TEXT,
    categoria VARCHAR(50) DEFAULT 'geral',
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Métodos de Pagamento
CREATE TABLE IF NOT EXISTS mgt_metodos_pagamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    identificador VARCHAR(50) UNIQUE NOT NULL COMMENT 'paypal, mercadopago, pix',
    ativo BOOLEAN DEFAULT FALSE,
    configuracao JSON COMMENT 'API keys, secrets, etc',
    taxa_percentual DECIMAL(5, 2) DEFAULT 0.00,
    taxa_fixa DECIMAL(10, 2) DEFAULT 0.00,
    ordem INT DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Meta da Comunidade
CREATE TABLE IF NOT EXISTS mgt_meta_comunidade (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mes INT NOT NULL COMMENT '1-12',
    ano INT NOT NULL,
    valor_meta DECIMAL(10, 2) NOT NULL,
    valor_atual DECIMAL(10, 2) DEFAULT 0.00,
    descricao TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_mes_ano (mes, ano)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Logs de Eventos do Mod
CREATE TABLE IF NOT EXISTS mgt_mod_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    servidor_id INT NOT NULL,
    tipo_evento VARCHAR(50) NOT NULL COMMENT 'player_join, player_leave, purchase_executed',
    jogador_nick VARCHAR(16),
    dados JSON,
    recebido_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servidor_id) REFERENCES mgt_servidores(id),
    INDEX idx_servidor_tipo (servidor_id, tipo_evento),
    INDEX idx_jogador (jogador_nick)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DADOS INICIAIS
-- =============================================

-- Inserir servidores padrão
INSERT INTO mgt_servidores (nome, identificador, endereco_ip, api_url, api_key, websocket_url, status, ativo) VALUES
('Servidor Magnatas', 'mgt', 'mgt.servidormagnatas.com.br:25565', 'http://mgt.servidormagnatas.com.br:8080/api', 'CHANGE_ME_KEY', 'ws://mgt.servidormagnatas.com.br:8080/ws', 'offline', TRUE),
('ATM10', 'atm10', 'atm10.servidormagnatas.com.br:25565', 'http://atm10.servidormagnatas.com.br:8080/api', 'CHANGE_ME_KEY', 'ws://atm10.servidormagnatas.com.br:8080/ws', 'offline', TRUE),
('ATM10 To The Sky', 'atm10tts', 'atm10tts.servidormagnatas.com.br:25565', 'http://atm10tts.servidormagnatas.com.br:8080/api', 'CHANGE_ME_KEY', 'ws://atm10tts.servidormagnatas.com.br:8080/ws', 'offline', TRUE);

-- Inserir produtos padrão (MGT-Cash)
INSERT INTO mgt_produtos (nome, descricao, tipo, quantidade, preco, comando_execucao, ordem) VALUES
('100 MGT-Cash', '100 moedas MGT para usar no servidor', 'cash', 100, 5.00, 'cash add {player} {amount}', 1),
('250 MGT-Cash', '250 moedas MGT para usar no servidor', 'cash', 250, 10.00, 'cash add {player} {amount}', 2),
('700 MGT-Cash', '700 moedas MGT para usar no servidor', 'cash', 700, 25.00, 'cash add {player} {amount}', 3),
('1500 MGT-Cash', '1500 moedas MGT para usar no servidor', 'cash', 1500, 50.00, 'cash add {player} {amount}', 4);

-- Inserir configurações padrão
INSERT INTO mgt_configuracoes (chave, valor, tipo, descricao, categoria) VALUES
('mgt_cash_valor', '0.05', 'float', 'Valor de 1 MGT-Cash em R$', 'loja'),
('loja_ativa', 'true', 'bool', 'Loja está ativa para compras', 'loja'),
('max_tentativas_entrega', '3', 'int', 'Máximo de tentativas de entrega antes de ir para fila', 'loja'),
('tempo_retry_entrega', '300', 'int', 'Tempo em segundos entre tentativas de entrega', 'loja'),
('webhook_discord', '', 'string', 'URL do webhook Discord para notificações', 'notificacoes');

-- Inserir meta da comunidade para dezembro de 2025
INSERT INTO mgt_meta_comunidade (mes, ano, valor_meta, valor_atual, descricao) VALUES
(12, 2025, 1000.00, 490.00, 'Meta de doações para manter os servidores em dezembro');

-- =============================================
-- STORED PROCEDURES E TRIGGERS
-- =============================================

-- Procedure para gerar número de pedido sequencial
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS gerar_numero_pedido(OUT numero_pedido VARCHAR(20))
BEGIN
    DECLARE ultimo_id INT;
    SELECT COALESCE(MAX(id), 0) + 1 INTO ultimo_id FROM mgt_transacoes;
    SET numero_pedido = CONCAT('PED-', LPAD(ultimo_id, 6, '0'));
END$$
DELIMITER ;

-- Trigger para atualizar meta da comunidade quando pagamento for aprovado
DELIMITER $$
CREATE TRIGGER atualizar_meta_comunidade
AFTER UPDATE ON mgt_transacoes
FOR EACH ROW
BEGIN
    IF NEW.status_pagamento = 'aprovado' AND OLD.status_pagamento != 'aprovado' THEN
        UPDATE mgt_meta_comunidade 
        SET valor_atual = valor_atual + NEW.valor_total
        WHERE mes = MONTH(CURRENT_DATE) 
        AND ano = YEAR(CURRENT_DATE)
        AND ativo = TRUE;
    END IF;
END$$
DELIMITER ;

-- =============================================
-- VIEWS ÚTEIS
-- =============================================

-- View de resumo de transações
CREATE OR REPLACE VIEW vw_transacoes_resumo AS
SELECT 
    t.id,
    t.pedido_numero,
    t.jogador_nick,
    s.nome AS servidor_nome,
    p.nome AS produto_nome,
    t.quantidade,
    t.valor_total,
    t.status_pagamento,
    t.status_entrega,
    t.metodo_pagamento,
    t.criado_em,
    t.entregue_em
FROM mgt_transacoes t
INNER JOIN mgt_servidores s ON t.servidor_id = s.id
INNER JOIN mgt_produtos p ON t.produto_id = p.id
ORDER BY t.criado_em DESC;

-- View de cupons ativos
CREATE OR REPLACE VIEW vw_cupons_ativos AS
SELECT 
    c.*,
    (c.uso_maximo - c.uso_atual) AS usos_restantes
FROM mgt_cupons c
WHERE c.ativo = TRUE
AND (c.valido_ate IS NULL OR c.valido_ate > NOW())
AND (c.uso_maximo IS NULL OR c.uso_atual < c.uso_maximo);
