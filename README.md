# MaxMollCRM - Система управления заказами и складом

## 🚀 Быстрый старт

### Установка и настройка


#### Запуск контейнеров
```bash
docker compose -f compose.dev.yaml up -d
```

##### Миграции базы данных
```bash
docker compose -f compose.dev.yaml exec workspace php artisan migrate
````
##### Наполнение тестовыми данными
```bash
docker compose -f compose.dev.yaml exec workspace php artisan db:seed-test --products=10 --warehouses=5
```
## 📡 API Endpoints

## 🏬 Управление складами
**Просмотр списка складов**  
`GET localhost:80/api/warehouses`

## 📦 Управление товарами
**Товары с остатками по складам**  
`GET localhost:80/api/products/stocks`

## 🛒 Управление заказами
### Основные операции
| Метод | Endpoint                  | Описание                     |
|-------|---------------------------|------------------------------|
| GET   | `localhost:80/api/orders` | Список заказов с фильтрацией |
| POST  | `localhost:80/api/orders` | Создание нового заказа       |
| PUT   | `localhost:80/api/orders/{id}` | Обновление данных заказа |

### Управление статусами
| Метод | Endpoint                     | Действие               |
|-------|------------------------------|-------------------------|
| PUT   | `/orders/{order}/complete`   | Завершение заказа      |
| PUT   | `/orders/{order}/cancel`     | Отмена заказа          |
| PUT   | `/orders/{order}/restore`    | Возобновление заказа   |

## 🔍 Детализация запросов

### Фильтрация заказов
```http
GET /api/orders?status=completed&warehouse_id=2&customer=Иван&date_from=2023-01-01&per_page=10
```
### Параметры:
```http request
status: Фильтр по статусу (active, completed, canceled)

warehouse_id: ID склада

customer: Поиск по частичному совпадению имени

date_from/date_to: Диапазон дат создания

per_page: Количество элементов на странице
```
### Создание заказа
```http
POST /api/orders
Content-Type: application/json
```
```json
{
  "customer": "Иван Петров",
  "warehouse_id": 1,
  "status": "active",
  "items": [
    {
      "product_id": 5,
      "count": 2
    },
    {
      "product_id": 8,
      "count": 1
    }
  ]
}
```
### Обновление заказа
```http
PUT /api/orders/{id}
Content-Type: application/json
```
```json
{
  "customer": "Новое имя клиента",
  "warehouse_id": 2,
  "items": [
    {"product_id": 1, "count": 3},
    {"product_id": 5, "count": 1}
  ]
}
```
Важно: Статус заказа нельзя изменить через этот метод.

## 🛠 Технологический стек

**Backend:**  
<img src="https://laravel.com/img/logomark.min.svg" width="16" height="16"> Laravel 12 (PHP Framework)

**База данных:**  
<img src="https://www.mysql.com/common/logos/logo-mysql-170x115.png" width="16" height="16"> MySQL 8.0+ (Relational Database)

**Контейнеризация:**  
<img src="https://www.docker.com/wp-content/uploads/2022/03/vertical-logo-monochromatic.png" width="16" height="16"> Docker + Docker Compose




