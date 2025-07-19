<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductStockCollection;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index(Request $request): ProductCollection
    {
        $products = Product::all();

        return new ProductCollection($products);
    }

    public function store(ProductStoreRequest $request): ProductResource
    {
        $product = Product::create($request->validated());

        return new ProductResource($product);
    }

    public function show(Request $request, Product $product): ProductResource
    {
        return new ProductResource($product);
    }

    public function update(ProductUpdateRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return new ProductResource($product);
    }

    public function destroy(Request $request, Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }

    /**
     * Получить список товаров с остатками по складам
     *
     * Возвращает коллекцию всех товаров с информацией о наличии
     * на каждом складе. Для каждого товара включает:
     * - Основные данные товара (ID, название, цену)
     * - Список складов с остатками
     * - Общее количество товара на всех складах
     *
     * @return \App\Http\Resources\ProductStockCollection Коллекция товаров с данными об остатках
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если не найдены товары или склады
     * @throws \Illuminate\Database\QueryException При ошибках работы с базой данных
     *
     */
    public function stocks(): ProductStockCollection {
        $products = Product::with(['stocks.warehouse'])->get();

        return new ProductStockCollection($products);
    }
}
