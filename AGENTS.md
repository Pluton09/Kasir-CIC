# AGENTS.md — Aplikasi Kasir Kantin

## Stack

- Laravel 13 (PHP 8.3+), MySQL 8.
- Frontend: Blade + Vue 3 Composition API + Vite.
- Vue hanya dipakai untuk layar kasir (`/kasir`). Riwayat, kelola menu, dan login adalah halaman Blade biasa.
- Tidak memakai Inertia, Vue Router, Sanctum, atau Tailwind.
- Semua teks antarmuka berbahasa Indonesia. Format uang: `Rp 6.000`.
- Zona waktu aplikasi: `Asia/Jakarta` (WIB). “Hari ini” pada riwayat mengikuti WIB.
- Database development: `kasir`. Database test: `kasir_testing`.
- Test dikonfigurasi di `phpunit.xml` dan **wajib** memakai `DB_DATABASE=kasir_testing`, bukan `kasir`.

## Perintah

```bash
# Prasyarat: MySQL Laragon berjalan di port 3306, root tanpa password.

composer install
npm install

# Siapkan database development
php artisan migrate:fresh --seed

# Jalankan aplikasi
npm run dev
php artisan serve
# Buka http://localhost:8000

# Jalankan test (memakai kasir_testing)
php artisan test

# Build aset produksi
npm run build
```

Akun kasir contoh (hanya dibuat saat `APP_ENV=local`):

```text
Email    : kasir@kasir.test
Password : password
```

## Aturan bisnis

- Hanya makan di tempat. Tidak ada opsi bawa pulang/take away.
- Satu pesanan boleh memiliki nomor meja, nama pelanggan, dan catatan — semuanya opsional.
- Metode bayar: `tunai`, `qris`, `transfer`.
- Tunai: kasir memasukkan uang diterima; kembalian dihitung otomatis. Uang diterima tidak boleh kurang dari total.
- QRIS/Transfer: konfirmasi manual “sudah lunas”. Belum ada payment gateway.
- Harga item transaksi adalah snapshot. Perubahan harga menu tidak boleh mengubah riwayat.
- Total, subtotal, dan kembalian selalu dihitung di server. Jangan percaya nilai total dari frontend.
- Transaksi dan item disimpan dalam satu `DB::transaction`.
- Nomor transaksi: `TRX-YYYYMMDD-001`, reset setiap hari. Dibuat aman dari race condition memakai tabel `sequences` dengan `lockForUpdate()`.
- Kategori atau menu nonaktif tidak muncul di kasir. Data transaksi lama tetap utuh.
- Tidak ada fitur stok, diskon, pajak, laporan lanjutan, multi-cabang, atau hapus permanen menu/kategori.

## Aturan teknis

- Route JSON (`/api/menus`, `/api/transactions`) diletakkan di `routes/web.php` di dalam middleware `auth`, bukan `routes/api.php`, agar session auth dan CSRF Laravel bekerja.
- Gunakan Form Request untuk validasi input.
- Jangan menambah library atau fitur di luar daftar tanpa persetujuan.
- Halaman kelola menu/kategori dibuat sederhana, bukan back office lengkap.
- Test fitur menggunakan `RefreshDatabase`; pastikan tidak menyentuh database `kasir`.
- Seeder user hanya berjalan di environment `local`.

## Tahapan pengerjaan

1. Database + model + factory + seeder.
2. Login minimal + API menu/transaksi + validasi.
3. Layar kasir.
4. Pembayaran + struk.
5. Riwayat + kelola menu/kategori + penyempurnaan login.

## Struktur utama

```text
app/Http/Controllers/         Controller web dan API
app/Http/Requests/            Form Request
app/Models/                   Category, Menu, Transaction, TransactionItem, User
app/Services/                 Logika transaksi (tahap berikutnya)
database/migrations/          Skema database
database/factories/           Factory model
database/seeders/             Seeder kategori, menu, user
resources/js/components/      Komponen Vue layar kasir
resources/views/              Halaman Blade
routes/web.php                Route web, auth, dan JSON
docs/prototype.html           Acuan tampilan layar kasir
```
