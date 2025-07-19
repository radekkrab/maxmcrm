<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class CustomerFilter extends Filter
{
    public function apply(Builder $query): Builder
    {
        return $query->where('customer', 'like', "%{$this->value}%");
    }
}
