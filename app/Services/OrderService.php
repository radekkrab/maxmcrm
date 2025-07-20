<?php

namespace App\Services;

use App\Exceptions\OrderCancellationException;
use App\Exceptions\OrderRestoreException;
use App\Models\Order;
use App\Exceptions\OrderCompletionException;
use App\Models\Stock;
use App\Models\StockMovement;
use Blueprint\Models\Model;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Завершение заказа с проверкой бизнес-правил
     *
     * @param Order $order
     * @return void
     * @throws OrderCompletionException
     */
    public function completeOrder(Order $order): void
    {
        $this->validateOrderForCompletion($order);

        DB::transaction(function () use ($order) {
            $this->processStockDeduction($order);
            $this->markOrderAsCompleted($order);
        });
    }

    /**
     * Проверка возможности завершения заказа
     */
    protected function validateOrderForCompletion(Order $order): void
    {
        if ($order->status !== 'active') {
            throw new OrderCompletionException('Можно завершать только активные заказы');
        }

        if ($order->completed_at) {
            throw new OrderCompletionException('Заказ уже завершен');
        }

        if (!$order->warehouse) {
            throw new OrderCompletionException('Склад не найден');
        }
    }

    /**
     * Списывание товаров со склада
     */
    protected function processStockDeduction(Order $order): void
    {
        foreach ($order->items as $item) {
            $stock = $order->warehouse->stocks()
                ->where('product_id', $item->product_id)
                ->firstOrFail();

            if ($stock->stock < $item->count) {
                throw new OrderCompletionException(
                    "Недостаточно товара {$item->product->name} на складе. Доступно: {$stock->stock}, требуется: {$item->count}"
                );
            }

            $stock->decrement('stock', $item->count);

            // Добавляем запись о движении
            $this->recordStockMovement(
                $stock,
                -$item->count, // Отрицательное значение для списания
                'order_completion',
                $order,
                "Завершение заказа #{$order->id}"
            );
        }
    }

    /**
     * Пометка заказа как завершенного
     */
    protected function markOrderAsCompleted(Order $order): void
    {
        $order->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    /**
     * Отмена заказа с возвратом товаров (если завершен)
     */
    public function cancelOrder(Order $order): void
    {
        $this->validateOrderForCancellation($order);

        DB::transaction(function () use ($order) {
            if ($order->status === 'completed') {
                $this->returnItemsToStock($order);
            }

            $this->markOrderAsCanceled($order);
        });
    }

    /**
     * Возобновление отмененного заказа
     */
    public function restoreOrder(Order $order): void
    {
        $this->validateOrderForRestoration($order);

        DB::transaction(function () use ($order) {
            $this->checkStockAvailability($order);
            $this->deductItemsFromStock($order);
            $this->reactivateOrder($order);
        });
    }

    // Вспомогательные методы для cancel
    protected function validateOrderForCancellation(Order $order): void
    {
        if (!in_array($order->status, ['active', 'completed'])) {
            throw new OrderCancellationException(
                'Можно отменять только активные или завершенные заказы'
            );
        }
    }

    protected function returnItemsToStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $stock = Stock::where('product_id', $item->product_id)
                ->where('warehouse_id', $order->warehouse_id)
                ->firstOrFail();

            $stock->increment('stock', $item->count);

            // Добавляем запись о движении
            $this->recordStockMovement(
                $stock,
                $item->count, // Положительное значение для возврата
                'order_cancellation',
                $order,
                "Отмена завершенного заказа #{$order->id}"
            );
        }
    }

    protected function markOrderAsCanceled(Order $order): void
    {
        $order->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'completed_at' => null
        ]);
    }

    // Вспомогательные методы для restore
    protected function validateOrderForRestoration(Order $order): void
    {
        if ($order->status !== 'canceled') {
            throw new OrderRestoreException(
                'Можно возобновить только отмененные заказы'
            );
        }

        if ($order->completed_at) {
            throw new OrderRestoreException(
                'Нельзя возобновить ранее завершенный заказ'
            );
        }
    }

    protected function checkStockAvailability(Order $order): void
    {
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
    }

    protected function deductItemsFromStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $stock = Stock::where('product_id', $item->product_id)
                ->where('warehouse_id', $order->warehouse_id)
                ->firstOrFail();

            $stock->decrement('stock', $item->count);

            // Добавляем запись о движении
            $this->recordStockMovement(
                $stock,
                -$item->count, // Отрицательное значение для списания
                'order_restoration',
                $order,
                "Возобновление заказа #{$order->id}"
            );
        }
    }

    protected function reactivateOrder(Order $order): void
    {
        $order->update([
            'status' => 'active',
            'canceled_at' => null,
            'completed_at' => null
        ]);
    }

    private function recordStockMovement(
        Stock $stock,
        int $amount,
        string $operationType,
        Model $source,
        ?string $reason = null
    ): void {
        StockMovement::create([
            'stock_id' => $stock->id,
            'amount' => $amount,
            'operation_type' => $operationType,
            'source_type' => get_class($source),
            'source_id' => $source->id,
            'reason' => $reason,
            'user_id' => auth()->id()
        ]);
    }
}
