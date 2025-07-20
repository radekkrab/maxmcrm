<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => $this->stock->product->name,
            'warehouse' => $this->stock->warehouse->name,
            'amount' => $this->amount,
            'operation_type' => $this->operation_type,
            'source' => $this->source?->getResourceType(),
            'reason' => $this->reason,
            'user' => $this->user?->name,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
