@extends('layouts.app')

@section('title', 'Kelola Harga Menu')

@section('content')
    <div class="page">
        <div class="page-head">
            <h1>Kelola Harga Menu</h1>
            <p>Menu tanpa harga tetap tampil di kasir, tetapi tidak bisa dijual sampai harganya diisi.</p>
        </div>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <div class="summary-grid">
            <div class="card summary">
                <span>Total menu</span>
                <strong>{{ $totalMenus }}</strong>
                <small>Semua tenant</small>
            </div>

            <div class="card summary {{ $unpricedCount > 0 ? '' : 'grand' }}">
                <span>Belum berharga</span>
                <strong>{{ $unpricedCount }}</strong>
                <small>{{ $unpricedCount > 0 ? 'Perlu diisi' : 'Semua sudah diisi' }}</small>
            </div>
        </div>

        <div class="chips">
            <a
                href="{{ route('menus.index') }}"
                class="chip {{ $onlyUnpriced ? '' : 'on' }}"
                @if (! $onlyUnpriced) aria-current="page" @endif
            >Semua menu</a>

            <a
                href="{{ route('menus.index', ['kosong' => 1]) }}"
                class="chip {{ $onlyUnpriced ? 'on' : '' }}"
                @if ($onlyUnpriced) aria-current="page" @endif
            >Hanya yang belum berharga</a>
        </div>

        @forelse ($tenants as $tenant)
            <section class="panel">
                <div class="tx-head">
                    <h2 style="margin: 0;">{{ $tenant->name }}</h2>
                    <span class="badge {{ $tenant->is_active ? 'on' : 'off' }}">
                        {{ $tenant->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Menu</th>
                                <th>Harga</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tenant->categories as $category)
                                @foreach ($category->menus as $menu)
                                    @php($formId = 'harga-'.$menu->id)
                                    <tr>
                                        <td class="mut">{{ $category->name }}</td>
                                        <td>
                                            {{ $menu->name }}
                                            @if ($menu->price === null)
                                                <span class="badge off">Belum diisi</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form
                                                id="{{ $formId }}"
                                                method="POST"
                                                action="{{ route('menus.price', $menu) }}"
                                                class="price-form"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <input
                                                    type="number"
                                                    name="price"
                                                    value="{{ $menu->price }}"
                                                    min="0"
                                                    step="1"
                                                    inputmode="numeric"
                                                    placeholder="Belum diisi"
                                                    aria-label="Harga {{ $menu->name }}"
                                                    required
                                                >
                                            </form>
                                        </td>
                                        <td>
                                            <button type="submit" form="{{ $formId }}">Simpan</button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <p class="empty">
                {{ $onlyUnpriced ? 'Semua menu sudah punya harga.' : 'Belum ada menu.' }}
            </p>
        @endforelse
    </div>
@endsection
