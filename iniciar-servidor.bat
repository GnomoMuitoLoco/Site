@echo off
cd /d "%~dp0"
color 0A
echo ========================================
echo   SERVIDOR MAGNATAS - INICIANDO...
echo ========================================
echo.
echo Servidor rodando em: http://localhost:8000
echo.
echo Pressione Ctrl+C para parar o servidor
echo ========================================
echo.
php -S localhost:8000 router.php
pause
