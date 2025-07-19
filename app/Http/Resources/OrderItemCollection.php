<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderItemCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total_items' => $this->collection->count(),
                'total_quantity' => $this->collection->sum('count'),
                'total_price' => $this->collection->sum(function ($item) {
                    return $item->count * ($item->price ?? $item->product->price);
                })
            ]
        ];
    }
}
