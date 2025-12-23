@echo off
REM Script pour corriger les permissions des sessions
echo Correction des permissions du dossier sessions...

cd /d %~dp0

REM Supprimer les anciens fichiers de session
echo Suppression des anciens fichiers de session...
del /Q sessions\* 2>nul

REM Modifier les permissions
echo Attribution des permissions completes...
icacls sessions /grant "BUILTIN\Users:(OI)(CI)F" /T
icacls sessions /grant "Everyone:(OI)(CI)F" /T

echo.
echo ========================================
echo   Permissions corrigees avec succes!
echo ========================================
echo.
echo Vous pouvez maintenant rafraichir votre navigateur.
echo.
pause
