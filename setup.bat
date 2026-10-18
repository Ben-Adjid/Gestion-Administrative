@echo off
echo ========================================================
echo   DemarchesTchad - Orchestrateur de Deploiement Local
echo ========================================================
echo.

cd backend
if not exist .env (
    copy .env.example .env
    echo [Backend] Fichier .env cree a partir de .env.example
)
echo [Backend] Installation des dependances Composer...
call composer install --no-interaction --prefer-dist --optimize-autoloader
if errorlevel 1 goto :failed

echo [Backend] Generation de la cle applicative...
findstr /R /C:"^APP_KEY=base64:" .env >nul
if errorlevel 1 php artisan key:generate --ansi
if errorlevel 1 goto :failed

echo [Backend] Execution des migrations et du seeder national...
php artisan migrate --seed --force
if errorlevel 1 goto :failed

echo [Backend] Lancement de la suite de tests PHPUnit...
php artisan test
if errorlevel 1 goto :failed

cd ..\frontend
if not exist .env.local (
    copy .env.example .env.local
    echo [Frontend] Fichier .env.local configure.
)
echo [Frontend] Installation des dependances NPM...
call npm ci
if errorlevel 1 goto :failed

echo [Frontend] Verification lint et build Next.js...
call npm run lint
if errorlevel 1 goto :failed
call npm run build
if errorlevel 1 goto :failed

cd ..\backend
start "DemarchesTchad API" cmd /k "php artisan serve"
cd ..\frontend
start "DemarchesTchad Frontend" cmd /k "npm run dev"

echo.
echo ========================================================
echo   Initialisation terminee avec succes !
echo ========================================================
pause
exit /b 0

:failed
echo.
echo [ERREUR] L'initialisation a echoue. Verifiez le message precedent et la configuration .env.
pause
exit /b 1
