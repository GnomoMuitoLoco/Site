@echo off
REM ========================================
REM Iniciar Servidor PHP Local
REM ========================================
REM Este script inicia o servidor PHP embutido
REM na porta 8000 (ou outra porta desejada)

cd /d "%~dp0"

echo.
echo ========================================
echo   Servidor PHP Local - Servidor Magnatas
echo ========================================
echo.
echo Iniciando servidor PHP na porta 8000...
echo.
echo Acesse: http://localhost:8000
echo.
echo Para parar o servidor, pressione CTRL+C
echo.
echo ========================================
echo.

php -S localhost:8000 router.php

pause
