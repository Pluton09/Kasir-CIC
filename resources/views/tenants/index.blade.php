@extends('layouts.app')

@section('title', 'Kelola Tenant')

@section('content')
    <div class="page">
        <div class="page-head">
            <h1>Kelola Tenant</h1>
            <p>Tenant nonaktif tidak tampil di layar kasir. Riwayat lama tetap utuh.</p>
        </div>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <section class="panel">
            <h2>Tambah tenant</h2>
            <form method="POST" action="{{ route('tenants.store') }}" class="inline-form">
                @csrf
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    maxlength="100"
                    placeholder="Nama tenant baru"
                    aria-label="Nama tenant baru"
                    required
                >
                <button type="submit" class="pri" style="width: auto;">Tambah tenant</button>
            </form>
        </section>

        <section class="panel">
            <h2>Daftar tenant</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Status</th>
                            <th class="num">Kategori</th>
                            <th class="num">Menu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tenants as $tenant)
                            <tr>
                                <td>
                                    <form
                                        method="POST"
                                        action="{{ route('tenants.update', $tenant) }}"
                                        class="inline-form"
                                    >
                                        @csrf
                                        @method('PATCH')
                                        <input
                                            type="text"
                                            name="name"
                                            value="{{ $tenant->name }}"
                                            maxlength="100"
                                            aria-label="Nama tenant {{ $tenant->name }}"
                                            required
                                        >
                                        <button type="submit">Simpan</button>
                                    </form>
                                </td>
                                <td>
                                    <span class="badge {{ $tenant->is_active ? 'on' : 'off' }}">
                                        {{ $tenant->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="num">{{ $tenant->categories_count }}</td>
                                <td class="num">{{ $tenant->menus_count }}</td>
                                <td>
                                    <div class="row-actions">
                                        <form method="POST" action="{{ route('tenants.toggle', $tenant) }}">
                                            @csrf
                                            <button type="submit">
                                                {{ $tenant->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="mut">Belum ada tenant.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
