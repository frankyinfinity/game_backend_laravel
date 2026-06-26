# game
# gcloud compute ssh --zone "europe-west12-c" "instance-game" --project "game-500515" --tunnel-through-iap -- -N -R 8085:127.0.0.1:8085

# backend
# php artisan serve --host=0.0.0.0 --port=8085
# php artisan reverb:start
# php artisan queue:listen

# frontend
# npm start
