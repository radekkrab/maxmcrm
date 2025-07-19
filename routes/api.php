<?php

use Illuminate\Support\Facades\Route;


Route::apiResource('warehouses', App\Http\Controllers\WarehouseController::class);

Route::apiResource('products', App\Http\Controllers\ProductController::class);

Route::apiResource('orders', App\Http\Controllers\OrderController::class);
