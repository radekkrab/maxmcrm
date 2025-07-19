<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseStoreRequest;
use App\Http\Requests\WarehouseUpdateRequest;
use App\Http\Resources\WarehouseCollection;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WarehouseController extends Controller
{
    /**
     * Получить список всех складов
     *
     * Возвращает коллекцию всех складов в системе.
     *
     * @param \Illuminate\Http\Request $request HTTP-запрос (может содержать параметры фильтрации/пагинации)
     * @return \App\Http\Resources\WarehouseCollection Коллекция складов в формате ресурса
     *
     * @throws \Illuminate\Database\QueryException При ошибках работы с базой данных
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException При других ошибках
     */
    public function index(Request $request): WarehouseCollection
    {
        $warehouses = Warehouse::all();

        return new WarehouseCollection($warehouses);
    }

    public function store(WarehouseStoreRequest $request): WarehouseResource
    {
        $warehouse = Warehouse::create($request->validated());

        return new WarehouseResource($warehouse);
    }

    public function show(Request $request, Warehouse $warehouse): WarehouseResource
    {
        return new WarehouseResource($warehouse);
    }

    public function update(WarehouseUpdateRequest $request, Warehouse $warehouse): WarehouseResource
    {
        $warehouse->update($request->validated());

        return new WarehouseResource($warehouse);
    }

    public function destroy(Request $request, Warehouse $warehouse): Response
    {
        $warehouse->delete();

        return response()->noContent();
    }
}
