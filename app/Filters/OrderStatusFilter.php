<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class OrderStatusFilter extends Filter
{
    public function apply(Builder $query): Builder
    {
        return $query->where('status', $this->value);
    }
}
