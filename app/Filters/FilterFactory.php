<?php

namespace App\Filters;

class FilterFactory
{
    private const FILTERS = [
        'status' => OrderStatusFilter::class,
        'warehouse_id' => WarehouseFilter::class,
        'customer' => CustomerFilter::class,
        'date_range' => DateRangeFilter::class,
    ];

    public static function make(string $key, mixed $value): ?Filter
    {
        $filterClass = self::FILTERS[$key] ?? null;

        return $filterClass ? new $filterClass($value) : null;
    }
}
