

## MaxMollCRM

docker compose -f compose.dev.yaml up -d
docker compose -f compose.dev.yaml exec workspace php artisan migrate
docker compose -f compose.dev.yaml exec workspace php artisan db:seed-test --products=10 --warehouses=5

Просмотреть список складов:
localhost:80/api/warehouses

Просмотреть список товаров с их остатками по складам:
localhost:80/api/products/stocks

Получить список заказов (с фильтрами и настраиваемой пагинацией):
GET localhost:80/api/orders?status=completed&warehouse_id=2&customer=Иван&date_from=2023-01-01&per_page=10

Создать заказ (в заказе может быть несколько позиций с разным количеством):
localhost:80/api/orders
{
"customer": "Vanya",       // Имя клиента
"warehouse_id": "1",  // ID существующего склада
"status": "active",         // Один из: active, completed, canceled    // Дата завершения (опционально)
"items": [                                // Массив позиций заказа
{
"product_id": "1",  // ID существующего товара
"count": "5"        // Количество (мин. 1)
},
{
"product_id": "2",  
"count": "5"  
}
]
}

Обновить заказ (данные покупателя и список позиций, но не статус)

Завершить заказ
Отменить заказ
Возобновить заказ (перевод из отмены в работу)





