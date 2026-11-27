<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\View\View;

class HistoryController extends Controller
{
    /**
     * Riwayat hari ini (WIB) beserta total penjualan per tenant.
     */
    public function index(): View
    {
        $now = now('Asia/Jakarta');
        $today = $now->toDateString();

        $transactions = Transaction::query()
            ->with(['items' => fn ($query) => $query->orderBy('id')])
            ->whereDate('created_at', $today)
            ->orderByDesc('id')
            ->get();

        $tenantTotals = TransactionItem::query()
            ->whereDate('created_at', $today)
            ->selectRaw('tenant_id, MAX(tenant_name) as tenant_name, SUM(qty) as qty, SUM(subtotal) as total')
            ->groupBy('tenant_id')
            ->orderByDesc('total')
            ->get();

        return view('history.index', [
            'transactions' => $transactions,
            'tenantTotals' => $tenantTotals,
            'grandTotal' => $transactions->sum('total'),
            'today' => $now,
        ]);
    }
}
