@echo off
:: Script de instalacion rapida para XAMPP en Windows
:: Sistema de Tickets de Consultoria

echo ==========================================
echo Sistema de Tickets de Consultoria
echo Instalacion automatica para XAMPP
echo ==========================================
echo.

:: Verificar si existe .env
if exist .env (
    echo [i] El archivo .env ya existe
    echo.
) else (
    echo [+] Creando archivo .env desde .env.example...
    copy .env.example .env
    echo [OK] Archivo .env creado
    echo.
    echo [!] IMPORTANTE: Edita el archivo .env con tus credenciales
    echo.
)

:: Verificar carpeta de uploads
if exist public\uploads\ (
    echo [OK] Carpeta public\uploads existe
) else (
    echo [+] Creando carpeta public\uploads...
    mkdir public\uploads
    echo [OK] Carpeta creada
)
echo.

:: Instrucciones para crear la base de datos
echo ==========================================
echo Siguiente paso: Crear la base de datos
echo ==========================================
echo.
echo Opcion 1 - phpMyAdmin (Recomendado):
echo   1. Abre http://localhost/phpmyadmin
echo   2. Click en la pestana SQL
echo   3. Abre el archivo docs\database.sql
echo   4. Copia todo el contenido
echo   5. Pegalo en phpMyAdmin y ejecuta
echo.
echo Opcion 2 - Linea de comandos:
echo   1. Abre CMD como Administrador
echo   2. cd C:\xampp\mysql\bin
echo   3. mysql.exe -u root -p ^< "%~dp0docs\database.sql"
echo   4. Presiona Enter (sin contrasena)
echo.

:: Verificar si MySQL esta corriendo
echo Verificando servicios de XAMPP...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] MySQL esta corriendo
) else (
    echo [!] MySQL no esta corriendo
    echo     Inicia MySQL desde el Panel de Control de XAMPP
)

tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] Apache esta corriendo
) else (
    echo [!] Apache no esta corriendo
    echo     Inicia Apache desde el Panel de Control de XAMPP
)
echo.

echo ==========================================
echo Acceso a la aplicacion
echo ==========================================
echo.
echo Una vez creada la base de datos, accede a:
echo   http://localhost/ticket-consultoria/public
echo.
echo Credenciales por defecto:
echo   Email: admin@arelance.com
echo   Contrasena: admin123
echo.
echo [!] Cambia la contrasena despues del primer acceso
echo.
echo Documentacion completa: INSTALACION-XAMPP.md
echo.

pause
