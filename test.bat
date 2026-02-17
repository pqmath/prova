@echo off
echo ==========================================
echo   Rodando Testes (API Desafio)
echo ==========================================
echo.
docker exec -it bombeiros-api-desafio composer test
echo.
echo ==========================================
echo   Testes finalizados!
echo ==========================================
pause
