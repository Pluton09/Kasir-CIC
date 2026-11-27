<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function store(StoreTransactionRequest $request, TransactionService $service): JsonResponse
    {
        $transaction = $service->create($request->user(), $request->validated());

        return response()->json([
            'code' => $transaction->code,
            'created_at' => $transaction->created_at,
            'payment_method' => $transaction->payment_method,
            'total' => $transaction->total,
            'paid_amount' => $transaction->paid_amount,
            'change_amount' => $transaction->change_amount,
            'table_no' => $transaction->table_no,
            'customer_name' => $transaction->customer_name,
            'note' => $transaction->note,
            'items' => $transaction->items->map(fn ($item) => [
                'tenant_id' => $item->tenant_id,
                'tenant_name' => $item->tenant_name,
                'menu_name' => $item->menu_name,
                'price' => $item->price,
                'qty' => $item->qty,
                'subtotal' => $item->subtotal,
            ])->values(),
        ], 201);
    }
}
