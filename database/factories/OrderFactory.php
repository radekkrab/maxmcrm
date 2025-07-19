<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\Warehouse;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'customer' => fake()->word(),
            'completed_at' => fake()->dateTime(),
            'status' => fake()->randomElement(["active","completed","canceled"]),
            'warehouse_id' => Warehouse::factory(),
        ];
    }
}
