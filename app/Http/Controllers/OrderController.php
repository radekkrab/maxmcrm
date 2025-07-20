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
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}
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
     * Завершение заказа
     *
     * @param Order $order
     * @return OrderResource
     */
    public function complete(Order $order): OrderResource
    {
        $this->orderService->completeOrder($order);

        return new OrderResource($order->fresh()->load('items.product'));
    }

    /**
     * Отмена заказа
     *
     * @param Order $order
     * @return OrderResource
     */
    public function cancel(Order $order): OrderResource
    {
        $this->orderService->cancelOrder($order);
        return new OrderResource($order->fresh()->load('items.product'));
    }

    /**
     * Возобновление заказа
     *
     * @param Order $order
     * @return OrderResource
     */
    public function restore(Order $order): OrderResource
    {
        $this->orderService->restoreOrder($order);
        return new OrderResource($order->fresh()->load('items.product'));
    }
}
