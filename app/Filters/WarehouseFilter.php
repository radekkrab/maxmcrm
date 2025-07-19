<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class WarehouseFilter extends Filter
{
    public function apply(Builder $query): Builder
    {
        return $query->where('warehouse_id', $this->value);
    }
}
