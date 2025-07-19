

## About Laravel

docker compose -f compose.dev.yaml up -d
docker compose -f compose.dev.yaml exec workspace php artisan migrate
docker compose -f compose.dev.yaml exec workspace php artisan db:seed-test --products=10 --warehouses=5


