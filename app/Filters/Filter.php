<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

abstract class Filter
{
    public function __construct(
        protected readonly string|int|array|null $value
    ) {}

    abstract public function apply(Builder $query): Builder;
}
