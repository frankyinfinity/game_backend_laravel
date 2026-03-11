# backend
# php artisan serve --host=0.0.0.0 --port=8085
# php artisan reverb:start
# php workerman-draw-items.php start
# php artisan queue:listen
# far partire il container docker di redis

# frontend
# npm start

# redis (installare su docker)
# docker run -d --name redis -p 6379:6379 -v C:\redis_data:/data redis:7 --appendonly yes
# docker exec -it redis redis-cli CONFIG GET appendonly
# docker exec -it redis redis-cli PING
