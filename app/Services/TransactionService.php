<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    /**
     * Simpan transaksi + item dalam satu database transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $data) {
            $mergedItems = [];

            // Gabungkan menu yang sama agar satu menu hanya menjadi satu baris item.
            foreach ($data['items'] as $item) {
                $menuId = (int) $item['menu_id'];
                $qty = (int) $item['qty'];

                if (! isset($mergedItems[$menuId])) {
                    $mergedItems[$menuId] = [
                        'menu_id' => $menuId,
                        'qty' => 0,
                    ];
                }

                $mergedItems[$menuId]['qty'] += $qty;
            }

            foreach ($mergedItems as $item) {
                if ($item['qty'] > 99) {
                    throw ValidationException::withMessages([
                        'items' => 'Total jumlah untuk satu menu maksimal 99.',
                    ]);
                }
            }

            $menuIds = collect(array_keys($mergedItems))->values();

            // Kunci baris menu agar harga yang dipakai benar-benar harga saat transaksi.
            // available() sekaligus menolak menu dari kategori/tenant yang nonaktif.
            $menus = Menu::query()
                ->available()
                ->with('category.tenant')
                ->whereIn('id', $menuIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($menus->count() !== $menuIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Ada menu yang tidak tersedia atau sudah nonaktif.',
                ]);
            }

            // Menu tanpa harga tidak boleh dijual. NULL berarti belum diisi,
            // bukan gratis, jadi jangan dianggap 0.
            $unpriced = $menus->filter(fn (Menu $menu) => $menu->price === null);

            if ($unpriced->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Menu belum memiliki harga: '.$unpriced->pluck('name')->implode(', ')
                        .'. Isi harga lewat halaman Harga Menu.',
                ]);
            }

            $total = 0;
            $lines = [];

            foreach ($mergedItems as $item) {
                $menu = $menus->get($item['menu_id']);
                $tenant = $menu->category->tenant;
                $qty = $item['qty'];
                $subtotal = $menu->price * $qty;

                $total += $subtotal;

                $lines[] = [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'menu_id' => $menu->id,
                    'menu_name' => $menu->name,
                    'price' => $menu->price,
                    'qty' => $qty,
                    'subtotal' => $subtotal,
                ];
            }

            $paymentMethod = $data['payment_method'];

            if ($paymentMethod === 'Tunai') {
                $paidAmount = (int) $data['paid_amount'];

                if ($paidAmount < $total) {
                    throw ValidationException::withMessages([
                        'paid_amount' => 'Uang diterima kurang dari total pembayaran.',
                    ]);
                }

                $changeAmount = $paidAmount - $total;
            } else {
                // QRIS/Transfer: konfirmasi manual, dianggap sudah lunas.
                $paidAmount = $total;
                $changeAmount = 0;
            }

            $transaction = Transaction::create([
                'code' => $this->nextCode(),
                'table_no' => $data['table_no'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'note' => $data['note'] ?? null,
                'payment_method' => $paymentMethod,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'user_id' => $user->id,
            ]);

            $transaction->items()->createMany($lines);

            return $transaction->load('items');
        }, 3);
    }

    /**
     * Nomor harian TRX-YYYYMMDD-001. Baris sequence per hari dikunci
     * dengan lockForUpdate supaya transaksi paralel tidak mendapat nomor sama.
     */
    private function nextCode(): string
    {
        $date = now()->format('Ymd');
        $name = 'transaction_code:'.$date;

        DB::table('sequences')->insertOrIgnore([
            'name' => $name,
            'value' => 0,
        ]);

        $sequence = DB::table('sequences')
            ->where('name', $name)
            ->lockForUpdate()
            ->first();

        $nextValue = (int) $sequence->value + 1;

        DB::table('sequences')
            ->where('name', $name)
            ->update(['value' => $nextValue]);

        return 'TRX-'.$date.'-'.str_pad((string) $nextValue, 3, '0', STR_PAD_LEFT);
    }
}
