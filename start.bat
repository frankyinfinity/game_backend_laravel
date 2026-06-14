@echo off
start /b php artisan serve --host=0.0.0.0 --port=8085
start /b php artisan reverb:start
start /b php artisan queue:listen
ssh -i "C:\chiave_vm\ssh-key-2026-03-19.key" -N -R 8085:127.0.0.1:8085 opc@84.8.249.14
