# AGENTS.md — Aplikasi Kasir Kantin

## Stack

- Laravel 13 (PHP 8.3+), MySQL 8.
- Frontend: Blade + Vue 3 Composition API + Vite.
- Vue hanya dipakai untuk layar kasir (`/kasir`). Riwayat, kelola tenant, dan login adalah halaman Blade biasa.
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

# Isi ulang data tenant/kategori/menu secara eksplisit (tanpa fresh)
php artisan db:seed --class=KantinSeeder

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
- Kasir dipakai bersama beberapa tenant. Setiap tenant punya kategori dan menu sendiri.
- Menu mengikuti tenant lewat kategori (`categories.tenant_id`).
- Satu keranjang boleh berisi item dari beberapa tenant dan dibayar sekali. Panel pesanan dikelompokkan per tenant.
- Satu pesanan boleh memiliki nomor meja, nama pelanggan, dan catatan — semuanya opsional.
- Metode bayar: `Tunai`, `QRIS`, `Transfer`.
- Tunai: kasir memasukkan uang diterima; kembalian dihitung otomatis. Uang diterima tidak boleh kurang dari total.
- QRIS/Transfer: konfirmasi manual “sudah lunas”. Belum ada payment gateway.
- Harga item transaksi adalah snapshot. Perubahan harga menu tidak boleh mengubah riwayat. `transaction_items.price` selalu terisi (NOT NULL).
- Harga menu boleh kosong (`menus.price` NULL) yang berarti “harga belum diisi”, bukan gratis dan bukan 0. Menu tanpa harga tetap tampil di kasir dengan kartu redup, tetapi tidak bisa ditambahkan ke pesanan; `POST /api/transactions` juga menolaknya.
- Harga diisi cepat lewat `/kelola/menu`: tabel per tenant, isian harga inline, simpan per baris, dan filter “hanya yang belum berharga”. Validasi harga: bilangan bulat, minimal 0.
- Nama tenant pada item transaksi juga snapshot (`transaction_items.tenant_id`, `tenant_name`). Server mengisinya dari database; frontend tidak mengirim tenant.
- Total, subtotal, dan kembalian selalu dihitung di server. Jangan percaya nilai total dari frontend.
- Transaksi dan item disimpan dalam satu `DB::transaction`.
- Nomor transaksi: `TRX-YYYYMMDD-001`, reset setiap hari. Dibuat aman dari race condition memakai tabel `sequences` dengan `lockForUpdate()`.
- Kategori, menu, atau tenant nonaktif tidak muncul di kasir dan ditolak oleh `POST /api/transactions`. Data transaksi lama tetap utuh.
- Riwayat hari ini (WIB) menampilkan ringkasan total penjualan per tenant dari `transaction_items`.
- Tidak ada fitur stok, diskon, pajak, laporan lanjutan, multi-cabang, atau hapus permanen menu/kategori/tenant.

## Aturan teknis

- Route JSON (`/api/menus`, `/api/transactions`) diletakkan di `routes/web.php` di dalam middleware `auth`, bukan `routes/api.php`, agar session auth dan CSRF Laravel bekerja.
- `GET /api/menus` mengembalikan tenant aktif, masing-masing berisi kategori aktif dan menu aktif.
- Kelola tenant (tambah, ubah nama, aktif/nonaktif) ada di `/kelola`. Tenant baru tidak di-hardcode.
- Kelola harga menu ada di `/kelola/menu` (tabel per tenant, simpan per baris, filter `?kosong=1`).
- Login memakai email + password, maksimal 5 percobaan per menit per email + IP, dan session diregenerasi setelah login berhasil.
- Gunakan Form Request untuk validasi input.
- Logika transaksi berada di `app/Services/TransactionService.php`.
- Jangan menambah library atau fitur di luar daftar tanpa persetujuan.
- Halaman kelola menu/kategori dibuat sederhana, bukan back office lengkap.
- Test fitur menggunakan `RefreshDatabase`; pastikan tidak menyentuh database `kasir`.
- Seeder user hanya berjalan di environment `local`.
- Seeder `KantinSeeder` berisi data tenant/kategori/menu asli tanpa harga (`price` NULL) dan hanya berjalan lewat perintah eksplisit (`migrate:fresh --seed` atau `db:seed --class=KantinSeeder`), tidak otomatis di production.

## Tahapan pengerjaan

1. Database + model + factory + seeder.
2. Login minimal + API menu/transaksi + validasi.
3. Layar kasir.
4. Pembayaran + struk.
5. Riwayat + kelola tenant/menu/kategori + penyempurnaan login.
6. Tenant ganda: sidebar tenant, keranjang lintas tenant, snapshot tenant, ringkasan per tenant.
7. Harga menu kosong: `price` NULL, kartu redup di kasir, isi harga inline di `/kelola/menu`.

## Struktur utama

```text
app/Http/Controllers/         Controller web dan API (termasuk History, Tenant, MenuManagement)
app/Http/Requests/            Form Request
app/Models/                   Category, Menu, Tenant, Transaction, TransactionItem, User
app/Services/                 TransactionService (nomor, total, snapshot tenant/menu, tolak harga kosong, simpan)
database/migrations/          Skema database
database/factories/           Factory model
database/seeders/             KantinSeeder (tenant/kategori/menu tanpa harga) dan UserSeeder
resources/js/components/      Komponen Vue layar kasir (termasuk TenantSidebar, PaymentDialog)
resources/views/              Halaman Blade (kasir, riwayat, kelola tenant, harga menu, login)
routes/web.php                Route web, auth, dan JSON
docs/prototype.html           Acuan tampilan layar kasir
```
