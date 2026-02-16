@echo off
echo ==========================================
echo   Iniciando Setup do Sistema de Bombeiros
echo ==========================================

echo [1/3] Subindo containers Docker...
docker-compose up -d --build

echo [2/3] Configurando API Desafio (Backend/Frontend)...
docker exec -it bombeiros-api-desafio composer run setup

echo [3/3] Configurando Sistema Terceiro (Simulador)...
docker exec -it bombeiros-sistema-terceiro composer run setup

echo ==========================================
echo   Setup Concluido!
echo ==========================================
echo   Acesse:
echo   - Sistema Terceiro: http://localhost:8000
echo   - API Desafio:      http://localhost:8001
echo   - RabbitMQ:         http://localhost:15672
echo ==========================================
pause
