<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'price',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'price' => 'float',
        ];
    }

    /**
     * Отношение к остаткам на складах
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Отношение к складам через остатки
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function warehouses(): HasManyThrough
    {
        return $this->hasManyThrough(
            Warehouse::class,
            Stock::class,
            'product_id', // Внешний ключ в таблице stocks
            'id', // Внешний ключ в таблице warehouses
            'id', // Локальный ключ в таблице products
            'warehouse_id' // Локальный ключ в таблице stocks
        );
    }
}
