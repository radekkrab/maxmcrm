

## MaxMollCRM

docker compose -f compose.dev.yaml up -d
docker compose -f compose.dev.yaml exec workspace php artisan migrate
docker compose -f compose.dev.yaml exec workspace php artisan db:seed-test --products=10 --warehouses=5

Просмотреть список складов:
localhost:80/api/warehouses

Просмотреть список товаров с их остатками по складам:
localhost:80/api/products/stocks

Получить список заказов (с фильтрами и настраиваемой пагинацией)
Создать заказ (в заказе может быть несколько позиций с разным количеством)
Обновить заказ (данные покупателя и список позиций, но не статус)
Завершить заказ
Отменить заказ
Возобновить заказ (перевод из отмены в работу)





