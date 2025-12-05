#!/bin/bash
# ========================================
# Iniciar Servidor PHP Local
# ========================================
# Este script inicia o servidor PHP embutido
# na porta 8000

cd "$(dirname "$0")"

echo ""
echo "========================================"
echo "  Servidor PHP Local - Servidor Magnatas"
echo "========================================"
echo ""
echo "Iniciando servidor PHP na porta 8000..."
echo ""
echo "Acesse: http://localhost:8000"
echo ""
echo "Para parar o servidor, pressione CTRL+C"
echo ""
echo "========================================"
echo ""

php -S localhost:8000

exit 0
