<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class DateRangeFilter extends Filter
{
    public function apply(Builder $query): Builder
    {
        [$from, $to] = is_array($this->value) ? $this->value : [$this->value, null];

        return $query
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to));
    }
}
