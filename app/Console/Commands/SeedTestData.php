<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Stock;

class SeedTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:seed-test
                            {--products=5 : Number of products to create}
                            {--warehouses=3 : Number of warehouses to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed test data using model factories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding test data using factories...');

        // Очистка данных
        $this->call('migrate:fresh');

        // Создаем склады
        $warehouses = Warehouse::factory()
            ->count($this->option('warehouses'))
            ->create();

        $this->info("Created {$warehouses->count()} warehouses");

        // Создаем товары
        $products = Product::factory()
            ->count($this->option('products'))
            ->create();

        $this->info("Created {$products->count()} products");

        // Создаем остатки на складах
        $warehouses->each(function ($warehouse) use ($products) {
            $products->each(function ($product) use ($warehouse) {
                Stock::factory()
                    ->for($warehouse)
                    ->for($product)
                    ->create([
                        'stock' => rand(10, 100)
                    ]);
            });
        });

        $this->info("Created stocks for all products in all warehouses");
        $this->info('Test data seeded successfully!');
    }
}
