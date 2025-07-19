<?php

use Illuminate\Support\Facades\Route;

Route::get('products/stocks', [App\Http\Controllers\ProductController::class, 'stocks']);

Route::put('/orders/{order}/complete', [App\Http\Controllers\OrderController::class, 'complete']);

Route::put('/orders/{order}/restore', [App\Http\Controllers\OrderController::class, 'restore']);

Route::put('/orders/{order}/cancel', [App\Http\Controllers\OrderController::class, 'cancel']);

Route::apiResource('warehouses', App\Http\Controllers\WarehouseController::class);

Route::apiResource('products', App\Http\Controllers\ProductController::class);

Route::apiResource('orders', App\Http\Controllers\OrderController::class);
