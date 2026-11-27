@extends('layouts.app')

@section('title', 'Riwayat Hari Ini')

@section('content')
    <div class="page">
        <div class="page-head">
            <h1>Riwayat Hari Ini</h1>
            <p>{{ $today->locale('id')->translatedFormat('l, d F Y') }} · WIB</p>
        </div>

        <div class="summary-grid">
            @forelse ($tenantTotals as $row)
                <div class="card summary">
                    <span>{{ $row->tenant_name ?: 'Tanpa tenant' }}</span>
                    <strong><x-rupiah :value="$row->total" /></strong>
                    <small>{{ (int) $row->qty }} item terjual</small>
                </div>
            @empty
                <div class="card summary">
                    <span>Belum ada penjualan</span>
                    <strong>Rp 0</strong>
                    <small>Hari ini</small>
                </div>
            @endforelse

            <div class="card summary grand">
                <span>Total hari ini</span>
                <strong><x-rupiah :value="$grandTotal" /></strong>
                <small>{{ $transactions->count() }} transaksi</small>
            </div>
        </div>

        @forelse ($transactions as $transaction)
            <section class="panel">
                <div class="tx-head">
                    <strong>{{ $transaction->code }}</strong>
                    <span>{{ $transaction->created_at->format('H:i') }} WIB</span>
                </div>

                <div class="tx-meta">
                    <span>{{ $transaction->payment_method }}</span>
                    @if ($transaction->table_no)
                        <span>Meja {{ $transaction->table_no }}</span>
                    @endif
                    @if ($transaction->customer_name)
                        <span>{{ $transaction->customer_name }}</span>
                    @endif
                    @if ($transaction->note)
                        <span>Catatan: {{ $transaction->note }}</span>
                    @endif
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>Menu</th>
                                <th class="num">Qty</th>
                                <th class="num">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transaction->items as $item)
                                <tr>
                                    <td class="tenant-name">{{ $item->tenant_name ?: 'Tanpa tenant' }}</td>
                                    <td>{{ $item->menu_name }}</td>
                                    <td class="num">{{ $item->qty }}</td>
                                    <td class="num"><x-rupiah :value="$item->subtotal" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="tx-meta" style="justify-content: flex-end; margin-top: 10px;">
                    <span>Diterima <x-rupiah :value="$transaction->paid_amount" /></span>
                    <span>Kembalian <x-rupiah :value="$transaction->change_amount" /></span>
                    <strong style="color: var(--tx);">Total <x-rupiah :value="$transaction->total" /></strong>
                </div>
            </section>
        @empty
            <p class="empty">Belum ada transaksi hari ini.</p>
        @endforelse
    </div>
@endsection
