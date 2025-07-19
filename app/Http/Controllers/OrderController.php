<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderCancellationException;
use App\Exceptions\OrderCompletionException;
use App\Exceptions\OrderRestoreException;
use App\Filters\FilterFactory;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\OrderStoreRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Получить список заказов с пагинацией и фильтрацией
     *
     * @param OrderIndexRequest $request Запрос с параметрами фильтрации:
     *              - status (active/completed/canceled)
     *              - warehouse_id (ID склада)
     *              - customer (поиск по частичному совпадению)
     *              - date_from/date_to (фильтр по дате)
     *              - per_page (количество на странице)
     *
     * @return OrderCollection Пагинированная коллекция заказов с связанными данными
     */
    public function index(OrderIndexRequest $request): OrderCollection
    {
        $query = Order::query()
            ->with(['warehouse', 'items.product'])
            ->orderBy('created_at', 'desc');

        foreach ($request->validated() as $key => $value) {
            if ($filter = FilterFactory::make($key, $value)) {
                $query = $filter->apply($query);
            }
        }

        return new OrderCollection(
            $query->paginate($request->per_page ?? 15)
        );
    }

    /**
     * Создать новый заказ
     *
     * Создает заказ на основе переданных данных, включая:
     * - Информацию о клиенте
     * - Склад для заказа
     * - Статус заказа
     * - Список товаров с количеством
     *
     * @param OrderStoreRequest $request Валидированные данные заказа:
     *              - customer (string) - Имя клиента
     *              - warehouse_id (int) - ID склада
     *              - status (string) - Статус (active/completed/canceled)
     *              - items (array) - Массив товаров:
     *                  - product_id (int) - ID товара
     *                  - count (int) - Количество (мин. 1)
     *
     * @return OrderResource Ресурс созданного заказа
     *
     * @throws \Illuminate\Database\QueryException При ошибке сохранения в БД
     */
    public function store(OrderStoreRequest $request): OrderResource
    {
        $order = Order::create($request->validated());

        return new OrderResource($order);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        return new OrderResource($order);
    }

    /**
     * Обновить данные заказа
     *
     * Обновляет основную информацию о заказе и товарные позиции.
     * Статус заказа не может быть изменен через этот метод.
     *
     * @param OrderUpdateRequest $request Валидированные данные для обновления:
     *              - customer (string) - Новое имя клиента (опционально)
     *              - warehouse_id (int) - Новый ID склада (опционально)
     *              - items (array) - Полный новый список позиций:
     *                  - product_id (int) - ID товара (обязательно)
     *                  - count (int) - Количество (мин. 1, обязательно)
     *
     * @param Order $order Модель заказа для обновления
     *
     * @return OrderResource Ресурс обновленного заказа
     *
     * @throws \Illuminate\Database\QueryException При ошибках сохранения
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если заказ не найден
     *
     * @example Пример запроса
     * {
     *   "customer": "Новое имя клиента",
     *   "warehouse_id": 2,
     *   "items": [
     *     {"product_id": 1, "count": 3},
     *     {"product_id": 5, "count": 1}
     *   ]
     * }
     */
    public function update(OrderUpdateRequest $request, Order $order): OrderResource
    {
        DB::transaction(function () use ($request, $order) {
            // Обновляем основные данные
            $order->update($request->except('items'));

            // Полностью заменяем позиции заказа
            $order->items()->delete();
            $order->items()->createMany($request->items);
        });

        return new OrderResource($order->fresh()->load('items.product'));
    }

    public function destroy(Request $request, Order $order): Response
    {
        $order->delete();

        return response()->noContent();
    }

    /**
     * Завершить заказ
     *
     * Переводит заказ в статус 'completed' и фиксирует дату завершения.
     * Перед завершением проверяет:
     * - Что заказ находится в активном статусе
     * - Что все товары в наличии на складе
     *
     * @param Order $order Модель заказа для завершения
     * @return OrderResource Ресурс завершенного заказа
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \App\Exceptions\OrderCompletionException При невозможности завершить заказ
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "status": "completed",
     *     "completed_at": "2023-05-20T14:30:00Z",
     *     ...
     *   }
     * }
     */
    public function complete(Order $order): OrderResource
    {
        if ($order->status !== 'active') {
            throw new OrderCompletionException('Можно завершать только активные заказы');
        }

        DB::transaction(function () use ($order) {
            // Проверяем наличие товаров
            foreach ($order->items as $item) {
                $stock = Stock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->firstOrFail();

                if ($stock->stock < $item->count) {
                    throw new OrderCompletionException(
                        "Недостаточно товара {$item->product_id} на складе"
                    );
                }
            }

            // Списываем товары
            foreach ($order->items as $item) {
                Stock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->decrement('stock', $item->count);
            }

            $order->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
        });

        return new OrderResource($order->fresh());
    }

    /**
     * Отменить заказ
     *
     * Переводит заказ в статус 'canceled'.
     * Если заказ был завершен - возвращает товары на склад.
     *
     * @param Order $order Модель заказа для отмены
     * @return OrderResource Ресурс отмененного заказа
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \App\Exceptions\OrderCancellationException При невозможности отменить заказ
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "status": "canceled",
     *     ...
     *   }
     * }
     */
    public function cancel(Order $order): OrderResource
    {
        if (!in_array($order->status, ['active', 'completed'])) {
            throw new OrderCancellationException('Можно отменять только активные или завершенные заказы');
        }

        DB::transaction(function () use ($order) {
            // Возвращаем товары на склад, если заказ был завершен
            if ($order->status === 'completed') {
                foreach ($order->items as $item) {
                    Stock::where('product_id', $item->product_id)
                        ->where('warehouse_id', $order->warehouse_id)
                        ->increment('stock', $item->count);
                }
            }

            $order->update([
                'status' => 'canceled',
                'canceled_at' => now()
            ]);
        });

        return new OrderResource($order->fresh());
    }

    /**
     * Возобновить отмененный заказ
     *
     * Переводит заказ из статуса 'canceled' в 'active' с проверкой:
     * - Достаточно ли товаров на складе для возобновления
     * - Заказ должен быть в статусе 'canceled'
     *
     * @param Order $order Модель заказа для возобновления
     * @return OrderResource Ресурс возобновленного заказа
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \App\Exceptions\OrderRestoreException При невозможности возобновить заказ
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "status": "active",
     *     "canceled_at": null,
     *     ...
     *   }
     * }
     * @response 400 {
     *   "message": "Недостаточно товара на складе"
     * }
     */
    public function restore(Order $order): OrderResource
    {
        // Основная проверка статуса
        if ($order->status !== 'canceled') {
            throw new OrderRestoreException('Можно возобновить только отмененные заказы');
        }

        // Проверка, что заказ не был завершен ранее
        if ($order->completed_at) {
            throw new OrderRestoreException('Нельзя возобновить ранее завершенный заказ');
        }

        // Проверка существования склада
        if (!$order->warehouse) {
            throw new OrderRestoreException('Связанный склад не найден');
        }

        DB::transaction(function () use ($order) {
            // Проверяем наличие товаров перед возобновлением
            foreach ($order->items as $item) {
                $stock = Stock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->firstOrFail();

                if ($stock->stock < $item->count) {
                    throw new OrderRestoreException(
                        "Недостаточно товара {$item->product->name} на складе. Доступно: {$stock->stock}, требуется: {$item->count}"
                    );
                }
            }

            // Списываем товары со склада
            foreach ($order->items as $item) {
                Stock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->decrement('stock', $item->count);
            }

            // Обновляем статус заказа
            $order->update([
                'status' => 'active',
                'canceled_at' => null,
                'completed_at' => null
            ]);
        });

        return new OrderResource($order->fresh()->load('items.product'));
    }
}
