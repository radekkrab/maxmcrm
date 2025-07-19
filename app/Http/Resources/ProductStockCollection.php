<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductStockCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total_products' => $this->collection->count(),
                'total_stock' => $this->collection->sum(function ($product) {
                    return $product->stocks->sum('stock');
                }),
                'warehouses_count' => $this->collection->first() ?
                    $this->collection->first()->stocks->count() : 0
            ]
        ];
    }
}
