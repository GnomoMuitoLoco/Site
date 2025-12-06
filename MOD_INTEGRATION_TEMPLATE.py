#!/usr/bin/env python3
"""
Template de Integração - MGT-Store com Mod
Este arquivo mostra como implementar o endpoint /api/purchase no seu mod
"""

from flask import Flask, request, jsonify
import logging
import os
from datetime import datetime
from typing import Optional, Dict, Any

app = Flask(__name__)
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Configurar token de API (deve ser o mesmo em mgt_servidores.api_key)
API_TOKEN = os.getenv("MGT_API_TOKEN", "seu-token-secreto-super-seguro")

# Fila de comandos para jogadores offline
COMMAND_QUEUE: Dict[str, list] = {}


# ============================================================================
# ENDPOINT PRINCIPAL - Receber Compras
# ============================================================================

@app.route("/api/purchase", methods=["POST"])
def process_purchase():
    """
    Endpoint que recebe dados de compra da MGT-Store
    
    Headers esperados:
        Authorization: Bearer {api_token}
        Content-Type: application/json
    
    Body esperado:
    {
        "transaction_id": 123,
        "player": "nome_do_jogador",
        "amount": 100,
        "command": "cash add nome_do_jogador 100",
        "timestamp": "2025-01-15T10:30:00Z"
    }
    
    Response:
    {
        "success": true,
        "executed": true,
        "message": "Comando executado com sucesso"
    }
    """
    
    try:
        # 1. VALIDAR TOKEN
        auth_header = request.headers.get("Authorization", "")
        token = auth_header.replace("Bearer ", "").strip()
        
        if token != API_TOKEN:
            logger.warning(f"❌ Tentativa de acesso com token inválido: {token[:10]}...")
            return jsonify({
                "success": False,
                "error": "Token de autenticação inválido"
            }), 401
        
        # 2. VALIDAR JSON
        data = request.get_json()
        if not data:
            logger.warning("❌ Requisição sem corpo JSON")
            return jsonify({
                "success": False,
                "error": "Corpo da requisição vazio"
            }), 400
        
        # 3. VALIDAR CAMPOS OBRIGATÓRIOS
        required_fields = ["transaction_id", "player", "amount", "command"]
        for field in required_fields:
            if field not in data:
                logger.warning(f"❌ Campo obrigatório ausente: {field}")
                return jsonify({
                    "success": False,
                    "error": f"Campo obrigatório ausente: {field}"
                }), 400
        
        # 4. EXTRAIR DADOS
        transaction_id = data.get("transaction_id")
        player = data.get("player").strip()
        amount = data.get("amount")
        command = data.get("command").strip()
        timestamp = data.get("timestamp", datetime.now().isoformat())
        
        logger.info(f"📦 Nova compra: Transaction #{transaction_id} para {player} ({amount} items)")
        
        # 5. VALIDAR DADOS
        if not player or len(player) < 3 or len(player) > 16:
            logger.warning(f"❌ Nick inválido: {player}")
            return jsonify({
                "success": False,
                "error": "Nick de jogador inválido"
            }), 400
        
        if amount <= 0:
            logger.warning(f"❌ Quantidade inválida: {amount}")
            return jsonify({
                "success": False,
                "error": "Quantidade deve ser maior que zero"
            }), 400
        
        # 6. TENTAR EXECUTAR COMANDO
        executed = False
        message = ""
        
        # Verificar se jogador está online (implementar conforme seu mod)
        if is_player_online(player):
            try:
                # Executar comando diretamente
                execute_command(command, player)
                executed = True
                message = f"✅ Comando executado para {player}"
                logger.info(f"{message}: {command}")
            except Exception as e:
                logger.error(f"❌ Erro ao executar comando: {str(e)}")
                message = "Erro ao executar comando"
        else:
            # Enfileirar para depois
            queue_command_for_player(player, command, transaction_id)
            executed = False
            message = f"⏳ Jogador offline. Comando enfileirado para entrega posterior"
            logger.info(f"{message}: {player}")
        
        # 7. RETORNAR SUCESSO
        return jsonify({
            "success": True,
            "executed": executed,
            "message": message,
            "transaction_id": transaction_id,
            "player": player,
            "timestamp": datetime.now().isoformat()
        }), 200
    
    except Exception as e:
        logger.error(f"❌ Erro ao processar compra: {str(e)}")
        return jsonify({
            "success": False,
            "error": f"Erro ao processar requisição: {str(e)}"
        }), 500


# ============================================================================
# UTILITÁRIOS - Implementar Conforme Seu Mod
# ============================================================================

def is_player_online(player_name: str) -> bool:
    """
    Verificar se jogador está online no servidor
    
    Implemente usando a API do seu mod:
    - Bukkit: player.isOnline()
    - Sponge: player.isOnline()
    - Forge: ServerLifecycleEvent listener
    - Fabric: ServerTickEvents
    """
    # EXEMPLO - Substituir com implementação real
    # return server.get_player(player_name) is not None
    
    # Para testes, consideramos sempre online
    logger.debug(f"🔍 Verificando se {player_name} está online...")
    return True  # MODIFICAR para sua implementação


def execute_command(command: str, player: str) -> None:
    """
    Executar comando no servidor
    
    Implementar usando console do seu mod:
    - Bukkit: Bukkit.dispatchCommand(console, command)
    - Sponge: Sponge.getCommandManager().process(console, command)
    - Forge: MinecraftServer.getServer().getCommandManager().execute(...)
    """
    logger.info(f"⚙️ Executando: {command}")
    
    # EXEMPLO - Substituir com implementação real
    # Bukkit.dispatchCommand(Bukkit.getConsoleSender(), command)
    
    # Para testes:
    print(f"[MGT-Store] Executando comando: {command}")


def queue_command_for_player(player: str, command: str, transaction_id: int) -> None:
    """
    Enfileirar comando para jogador offline
    Executar quando jogador entrar no servidor
    """
    if player not in COMMAND_QUEUE:
        COMMAND_QUEUE[player] = []
    
    COMMAND_QUEUE[player].append({
        "command": command,
        "transaction_id": transaction_id,
        "queued_at": datetime.now().isoformat(),
        "executed": False
    })
    
    logger.info(f"📝 Comando enfileirado para {player}: {command}")
    logger.info(f"   Fila atual: {len(COMMAND_QUEUE[player])} comando(s)")


# ============================================================================
# EVENTO - Quando Jogador Conecta
# ============================================================================

def on_player_join(player_name: str):
    """
    Chamar este evento quando jogador entrar no servidor
    Executar todos os comandos enfileirados
    
    Bukkit: PlayerJoinEvent
    Sponge: ClientConnectionEvent.Join
    Forge: PlayerEvent.PlayerLoggedInEvent
    Fabric: ServerTickEvents ou PlayerManagerMixin
    """
    if player_name in COMMAND_QUEUE:
        logger.info(f"👤 {player_name} conectado. Processando fila...")
        
        commands = COMMAND_QUEUE[player_name]
        for item in commands:
            if not item["executed"]:
                try:
                    execute_command(item["command"], player_name)
                    item["executed"] = True
                    logger.info(f"✅ Comando executado para {player_name}: {item['command']}")
                except Exception as e:
                    logger.error(f"❌ Erro ao executar comando enfileirado: {str(e)}")
        
        # Limpar fila
        COMMAND_QUEUE[player_name] = [cmd for cmd in commands if not cmd["executed"]]


# ============================================================================
# HEALTHCHECK - Testar Integração
# ============================================================================

@app.route("/api/health", methods=["GET"])
def health_check():
    """Endpoint para verificar se mod está respondendo"""
    return jsonify({
        "status": "ok",
        "service": "MGT-Store Mod Integration",
        "version": "1.0.0",
        "timestamp": datetime.now().isoformat()
    }), 200


@app.route("/api/status", methods=["GET"])
def mod_status():
    """Endpoint para verificar status do mod"""
    return jsonify({
        "success": True,
        "mod_online": True,
        "players_online": 0,  # Substituir com valor real
        "commands_queued": sum(len(cmds) for cmds in COMMAND_QUEUE.values()),
        "api_version": "1.0"
    }), 200


# ============================================================================
# RUN - Para Testes Locais
# ============================================================================

if __name__ == "__main__":
    print("""
    ╔════════════════════════════════════════════════════════════════╗
    ║         MGT-Store Mod Integration - Template                  ║
    ║                                                                ║
    ║  API Token: use a variável de ambiente MGT_API_TOKEN          ║
    ║  Endpoints:                                                    ║
    ║    POST   /api/purchase  - Receber compras                    ║
    ║    GET    /api/health    - Health check                       ║
    ║    GET    /api/status    - Status do mod                      ║
    ║                                                                ║
    ║  Teste local:                                                  ║
    ║    python3 mod_integration.py                                 ║
    ║                                                                ║
    ║  Em produção, integrar ao seu servidor Minecraft              ║
    ╚════════════════════════════════════════════════════════════════╝
    """)
    
    app.run(
        host="0.0.0.0",
        port=3000,
        debug=True  # Desativar em produção
    )


# ============================================================================
# EXEMPLO DE USO - Testar com cURL
# ============================================================================

"""
# 1. Health check
curl http://localhost:3000/api/health

# 2. Simular compra
curl -X POST http://localhost:3000/api/purchase \
  -H "Authorization: Bearer seu-token-secreto-super-seguro" \
  -H "Content-Type: application/json" \
  -d '{
    "transaction_id": 123,
    "player": "steve",
    "amount": 100,
    "command": "cash add steve 100",
    "timestamp": "2025-01-15T10:30:00Z"
  }'

# Response esperada:
{
  "success": true,
  "executed": true,
  "message": "✅ Comando executado para steve",
  "transaction_id": 123,
  "player": "steve",
  "timestamp": "2025-01-15T10:35:00Z"
}
"""
