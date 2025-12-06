-- MGT-Store: Setup SQL para Produção
-- Execute este script no seu banco de dados

-- ============================================================================
-- 1. CONFIGURAÇÃO DE PREÇO DO MGT-CASH (Se não existir)
-- ============================================================================

INSERT IGNORE INTO mgt_configuracoes (chave, valor)
VALUES ('mgt_cash_valor', '0.01');

-- Para modificar o preço depois:
-- UPDATE mgt_configuracoes SET valor = '0.05' WHERE chave = 'mgt_cash_valor';

-- ============================================================================
-- 2. SERVIDOR DE TESTE (Exemplo)
-- ============================================================================

INSERT INTO mgt_servidores (nome, identificador, api_url, api_key, ativo)
VALUES (
    'Servidor Magnatas (Original)',
    'mgt',
    'https://seu-mod-api.com/api',
    'sua-api-key-secreta-aqui',
    1
);

-- Para adicionar mais servidores:
-- INSERT INTO mgt_servidores (nome, identificador, api_url, api_key, ativo)
-- VALUES ('Outro Servidor', 'outro', 'https://outro-mod.com/api', 'outro-token', 1);

-- ============================================================================
-- 3. MÉTODO DE PAGAMENTO GRÁTIS (Para Testes)
-- ============================================================================

INSERT IGNORE INTO mgt_metodos_pagamento (nome, identificador, ativo, configuracao)
VALUES (
    'Teste Grátis',
    'gratis',
    1,
    '{}'
);

-- ============================================================================
-- 4. VALIDAR SCHEMA (Verificar colunas corretas)
-- ============================================================================

-- Verificar que mgt_transacoes tem as colunas corretas:
-- DESCRIBE mgt_transacoes;
-- Esperadas: status_pagamento, criado_em (NÃO status, data_criacao)

-- Verificar que mgt_servidores tem as colunas obrigatórias:
-- DESCRIBE mgt_servidores;
-- Esperadas: id, nome, identificador, api_url, api_key, ativo

-- Verificar que mgt_metodos_pagamento tem as colunas obrigatórias:
-- DESCRIBE mgt_metodos_pagamento;
-- Esperadas: id, nome, identificador, ativo, configuracao

-- ============================================================================
-- 5. EXAMPLE DATA (Dados de Exemplo para Teste)
-- ============================================================================

-- Adicionar meta da comunidade de exemplo:
INSERT IGNORE INTO mgt_meta_comunidade (mes, ano, valor_meta, valor_atual)
VALUES (1, 2025, 1000.00, 250.00);  -- Janeiro 2025, R$ 1000 de meta, R$ 250 alcançados

-- Adicionar cupom de teste:
INSERT IGNORE INTO mgt_cupons (codigo, tipo, valor, valor_minimo, uso_maximo, uso_atual, valido_ate, ativo)
VALUES (
    'TESTE10',                           -- Código do cupom
    'percentual',                        -- Tipo: percentual ou fixo
    10,                                  -- 10% de desconto
    100,                                 -- Mínimo: R$ 100 de compra
    100,                                 -- Máximo: 100 usos
    0,                                   -- Usos atuais: 0
    DATE_ADD(NOW(), INTERVAL 30 DAY),   -- Válido por 30 dias
    1                                    -- Ativo
);

-- ============================================================================
-- 6. VERIFICAR DADOS
-- ============================================================================

-- Listar configurações:
SELECT * FROM mgt_configuracoes WHERE chave LIKE 'mgt%';

-- Listar servidores:
SELECT id, nome, identificador, api_url, api_key, ativo FROM mgt_servidores;

-- Listar métodos de pagamento:
SELECT id, nome, identificador, ativo FROM mgt_metodos_pagamento;

-- Listar transações (para ver se está criando corretamente):
SELECT 
    id, 
    pedido_numero, 
    jogador_nick, 
    status_pagamento, 
    criado_em 
FROM mgt_transacoes 
ORDER BY id DESC 
LIMIT 10;

-- ============================================================================
-- 7. TROUBLESHOOTING
-- ============================================================================

-- Se a coluna status_pagamento não existir (schema antigo):
-- ALTER TABLE mgt_transacoes ADD COLUMN status_pagamento VARCHAR(50) DEFAULT 'pendente' AFTER status;
-- UPDATE mgt_transacoes SET status_pagamento = status;

-- Se a coluna criado_em não existir:
-- ALTER TABLE mgt_transacoes ADD COLUMN criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER data_criacao;
-- UPDATE mgt_transacoes SET criado_em = data_criacao;

-- Se a coluna identificador não existir em mgt_metodos_pagamento:
-- ALTER TABLE mgt_metodos_pagamento ADD COLUMN identificador VARCHAR(100) UNIQUE AFTER nome;
-- UPDATE mgt_metodos_pagamento SET identificador = LOWER(nome) WHERE identificador IS NULL;

-- Se a coluna identificador não existir em mgt_servidores:
-- ALTER TABLE mgt_servidores ADD COLUMN identificador VARCHAR(100) UNIQUE AFTER nome;
-- UPDATE mgt_servidores SET identificador = LOWER(nome) WHERE identificador IS NULL;

-- ============================================================================
-- 8. TESTE DE TRANSAÇÃO
-- ============================================================================

-- Criar transação de teste:
INSERT INTO mgt_transacoes (
    pedido_numero, 
    jogador_nick, 
    servidor_id, 
    quantidade, 
    valor_bruto, 
    desconto, 
    valor_total, 
    metodo_pagamento, 
    status_pagamento, 
    criado_em
) VALUES (
    CONCAT('TEST-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')),  -- Pedido único
    'TestPlayer',                                         -- Nick do teste
    1,                                                     -- ID do servidor (ajustar se necessário)
    100,                                                   -- 100 cash units
    1.00,                                                  -- R$ 1.00 (100 * 0.01)
    0,                                                     -- Sem desconto
    1.00,                                                  -- Total R$ 1.00
    'gratis',                                              -- Método gratis
    'pendente',                                            -- Status inicial
    NOW()                                                  -- Timestamp
);

-- Verificar:
SELECT * FROM mgt_transacoes WHERE jogador_nick = 'TestPlayer' LIMIT 1;

-- ============================================================================
-- NOTAS IMPORTANTES
-- ============================================================================

-- 1. A chave 'mgt_cash_valor' deve estar em mgt_configuracoes
--    Padrão: 0.01 (1 centavo por cash unit)
--    Pode ser modificado dinamicamente via Dashboard

-- 2. Servidores com ativo=0 não aparecem no dropdown da loja
--    Use isso para ativar/desativar servidores

-- 3. Cupons são validados pelo backend:
--    - Tipo 'percentual': desconto = valor_total * (valor/100)
--    - Tipo 'fixo': desconto = valor
--    - Validado: valor_minimo, uso_maximo, valido_ate

-- 4. As transações precisam ter status_pagamento e criado_em
--    Não use 'status' ou 'data_criacao' (colunas antigas)

-- 5. Métodos de pagamento com ativo=0 não aparecem no checkout
--    Desative métodos que não estão configurados

-- ============================================================================
-- FIM DO SCRIPT
-- ============================================================================
