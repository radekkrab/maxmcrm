<?php

namespace App\Http\Controllers;

use App\Http\Resources\StockMovementCollection;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $movements = StockMovement::query()
            ->with(['stock.product', 'stock.warehouse', 'user'])
            ->when($request->warehouse_id, fn($q, $id) =>
            $q->whereHas('stock', fn($q) => $q->where('warehouse_id', $id)))
            ->when($request->product_id, fn($q, $id) =>
            $q->whereHas('stock', fn($q) => $q->where('product_id', $id)))
            ->when($request->operation_type, fn($q, $type) =>
            $q->where('operation_type', $type))
            ->when($request->date_from, fn($q, $date) =>
            $q->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn($q, $date) =>
            $q->whereDate('created_at', '<=', $date))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return new StockMovementCollection($movements);
    }
}
