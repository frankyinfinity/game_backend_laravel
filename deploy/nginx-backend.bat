@echo off
REM =====================================================================
REM  nginx-backend.bat - Backend Laravel con Nginx + pool PHP-CGI
REM  Rimpiazza "php artisan serve" SULLA STESSA PORTA 8085: il frontend
REM  (config.js -> http://localhost:8085) e il tunnel SSH di start.bat
REM  (-R 8085:127.0.0.1:8085) continuano a funzionare senza modifiche.
REM  Il pool php-cgi su 127.0.0.1:9100 serve PIU' richieste in parallelo.
REM  Avvia anche: server statico/CORS 8086, Reverb 8081, queue worker.
REM
REM  Uso:
REM    nginx-backend.bat          -> avvio (default)
REM    nginx-backend.bat start    -> avvio
REM    nginx-backend.bat stop     -> arresto
REM    nginx-backend.bat restart  -> arresto + avvio
REM    nginx-backend.bat status   -> mostra porte e processi
REM
REM  Porta HTTP: 8085   |   Pool FastCGI: 127.0.0.1:9100 (4 worker)
REM =====================================================================

setlocal EnableDelayedExpansion

set PHP_CGI=C:\php\php-cgi.exe
set NGINX_HOME=C:\nginx
set BACKEND_ROOT=C:\project\game\backend
set HTTP_PORT=8085
set CGI_PORT=9100
set PHP_WORKERS=4

set CMD=%1
if "%CMD%"=="" set CMD=start

if /i "%CMD%"=="stop"   goto :stop
if /i "%CMD%"=="restart" goto :restart
if /i "%CMD%"=="status" goto :status
goto :start


:start
echo === Avvio backend Nginx + PHP-CGI ===

REM --- 0) Nginx installato? ---
if not exist "%NGINX_HOME%\nginx.exe" (
    echo [ERROR] Nginx non trovato in %NGINX_HOME%
    echo Scaricalo da https://nginx.org/en/download.html ed estrailo in %NGINX_HOME%
    exit /b 1
)

REM --- 0b) Config nginx: SEMPRE rigenerata (porta e root sempre allineati) ---
echo [INFO] Genero %BACKEND_ROOT%\deploy\nginx.conf ...
call :write_nginx_conf

REM --- 0c) Porta %HTTP_PORT% gia' occupata? Se e' artisan serve (php.exe)
REM         la fermo e la prendo; se e' un altro processo mi fermo. ---
set BUSY_PID=
for /f "tokens=5" %%a in ('netstat -aon 2^>nul ^| findstr ":%HTTP_PORT% " ^| findstr "LISTENING"') do set BUSY_PID=%%a
if defined BUSY_PID (
    set BUSY_IMG=
    for /f "skip=3 tokens=1" %%i in ('tasklist /fi "PID eq %BUSY_PID%" 2^>nul') do set BUSY_IMG=%%i
    if /i "!BUSY_IMG!"=="php.exe" (
        echo [INFO] Porta %HTTP_PORT% occupata da php.exe PID %BUSY_PID% - artisan serve: lo fermo...
        taskkill /f /pid %BUSY_PID% >nul 2>&1
        ping -n 2 127.0.0.1 >nul
    ) else if /i "!BUSY_IMG!"=="nginx.exe" (
        echo [INFO] Porta %HTTP_PORT% gia' servita dal nostro nginx, ok.
    ) else (
        echo [ERROR] Porta %HTTP_PORT% occupata da !BUSY_IMG! PID %BUSY_PID%: liberala e rilancia lo script.
        exit /b 1
    )
)

REM --- 1) Pool PHP-CGI: worker concorrenti ---
set PHP_CGI_RUNNING=
for /f "skip=3 tokens=1" %%p in ('tasklist /fi "imagename eq php-cgi.exe" 2^>nul') do set PHP_CGI_RUNNING=1
if defined PHP_CGI_RUNNING (
    echo [1/4] Pool PHP-CGI gia' attivo, skip
) else (
    set PHP_FCGI_CHILDREN=%PHP_WORKERS%
    set PHP_FCGI_MAX_REQUESTS=500
    start /b "php-cgi" "%PHP_CGI%" -b 127.0.0.1:%CGI_PORT%
    echo [1/4] Pool PHP-CGI avviato su 127.0.0.1:%CGI_PORT%, %PHP_WORKERS% worker
)

REM --- 2) Nginx ---
set NGINX_RUNNING=
for /f "skip=3 tokens=1" %%p in ('tasklist /fi "imagename eq nginx.exe" 2^>nul') do set NGINX_RUNNING=1
if defined NGINX_RUNNING (
    echo [2/4] Nginx gia' attivo, skip
) else (
    copy /y "%BACKEND_ROOT%\deploy\nginx.conf" "%NGINX_HOME%\conf\nginx.conf" >nul
    pushd %NGINX_HOME%
    start /b "nginx" nginx.exe
    popd
    echo [2/4] Nginx avviato sulla porta %HTTP_PORT%
)

REM --- 3) Servizi Laravel fuori dal pool FastCGI ---
set REVERB_RUNNING=
for /f %%i in ('powershell -NoProfile -Command "@(Get-CimInstance Win32_Process -Filter \"Name='php.exe'\" | Where-Object { $_.CommandLine -match 'reverb:start' }).Count" 2^>nul') do if %%i gtr 0 set REVERB_RUNNING=1
set QUEUE_RUNNING=
for /f %%i in ('powershell -NoProfile -Command "@(Get-CimInstance Win32_Process -Filter \"Name='php.exe'\" | Where-Object { $_.CommandLine -match 'queue:listen' }).Count" 2^>nul') do if %%i gtr 0 set QUEUE_RUNNING=1
if defined REVERB_RUNNING (
    echo [3/4] Reverb gia' attivo, skip
) else (
    start /b "reverb" /min cmd /c "cd /d %BACKEND_ROOT% && php artisan reverb:start"
)
if defined QUEUE_RUNNING (
    echo [3/4] Queue worker gia' attivo, skip
) else (
    start /b "queue" /min cmd /c "cd /d %BACKEND_ROOT% && php artisan queue:listen --timeout=300"
)
echo [3/4] Reverb ^| queue worker: verificati/avviati

REM --- 4) Server statico/CORS 8086 (usato da STATIC_URL per immagini/tile) ---
set PORT8086_RUNNING=
for /f "tokens=5" %%a in ('netstat -aon 2^>nul ^| findstr ":8086 " ^| findstr "LISTENING"') do set PORT8086_RUNNING=1
if defined PORT8086_RUNNING (
    echo [4/4] Server statico 8086 gia' attivo, skip
) else (
    start /b "static8086" /min cmd /c "cd /d %BACKEND_ROOT% && php -S 0.0.0.0:8086 -t public public/server.php"
    echo [4/4] Server statico/CORS avviato su 8086
)

echo.
echo Backend disponibile su: http://127.0.0.1:%HTTP_PORT%
echo Statistiche: nginx-backend.bat status
goto :eof


:stop
echo === Arresto backend Nginx + PHP-CGI ===
taskkill /f /im nginx.exe >nul 2>&1 && echo [1/2] Nginx arrestato
taskkill /f /im php-cgi.exe >nul 2>&1 && echo [2/2] Pool PHP-CGI arrestato
echo Fatto. Reverb e queue worker NON sono toccati: arrestali a mano se serve.
goto :eof


:restart
call :stop
echo.
ping -n 3 127.0.0.1 >nul
goto :start


:status
echo === Stato backend ===
tasklist /fi "imagename eq nginx.exe" 2>nul | find /i "nginx.exe" >nul && (echo nginx.exe      RUNNING) || (echo nginx.exe      stopped)
tasklist /fi "imagename eq php-cgi.exe" 2>nul | find /i "php-cgi.exe" >nul && (echo php-cgi.exe    RUNNING) || (echo php-cgi.exe    stopped)
netstat -ano | findstr ":%HTTP_PORT% " | findstr LISTENING >nul && (echo porta %HTTP_PORT%   LISTENING) || (echo porta %HTTP_PORT%   NOT listening)
netstat -ano | findstr ":%CGI_PORT% " | findstr LISTENING >nul && (echo porta %CGI_PORT%   LISTENING) || (echo porta %CGI_PORT%   NOT listening)
netstat -ano | findstr ":8086 " | findstr LISTENING >nul && (echo porta 8086   LISTENING) || (echo porta 8086   NOT listening)
echo.
echo Test rapido:
curl.exe --max-time 10 -s -o NUL -w "http://127.0.0.1:%HTTP_PORT%/up -> HTTP %%{http_code} in %%{time_total}s\n" http://127.0.0.1:%HTTP_PORT%/up
goto :eof


REM ---------------------------------------------------------------------
REM  Genera la config Nginx (stessa di public/server.php: CORS + statici)
REM ---------------------------------------------------------------------
:write_nginx_conf
if not exist "%BACKEND_ROOT%\deploy" mkdir "%BACKEND_ROOT%\deploy"
(
echo worker_processes  1;
echo error_log  logs/error.log;
echo pid        logs/nginx.pid;
echo events { worker_connections  1024; }
echo http {
echo     include       mime.types;
echo     default_type  application/octet-stream;
echo     sendfile           on;
echo     keepalive_timeout  65;
echo     client_max_body_size 200m;
echo     upstream php_cgi_pool {
echo         server 127.0.0.1:%CGI_PORT%;
echo         keepalive 16;
echo     }
echo     server {
echo         listen       %HTTP_PORT%;
echo         server_name  _;
echo         root         "%BACKEND_ROOT:\=/%/public";
echo         index        index.php index.html;
echo         add_header Access-Control-Allow-Origin * always;
echo         add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
echo         add_header Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With, Accept" always;
echo         if ^($request_method = OPTIONS^) { return 204; }
echo         location / { try_files $uri $uri/ /index.php?$query_string; }
echo         location ~ \.php$ {
echo             try_files $uri =404;
echo             include       fastcgi.conf;
echo             fastcgi_pass  php_cgi_pool;
echo             fastcgi_read_timeout 300s;
echo         }
echo         location ~* ^/storage/ {
echo             access_log off;
echo             expires 7d;
echo         }
echo     }
echo }
) > "%BACKEND_ROOT%\deploy\nginx.conf"
echo [INFO] Config scritta in %BACKEND_ROOT%\deploy\nginx.conf
goto :eof
