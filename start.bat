@echo off
start /b php artisan serve --host=0.0.0.0 --port=8085
start /b php artisan reverb:start
start /b php artisan queue:work --timeout=300
gcloud compute ssh --zone "europe-west12-c" "instance-game" --project "game-500515" --tunnel-through-iap -- -N -R 8085:127.0.0.1:8085
