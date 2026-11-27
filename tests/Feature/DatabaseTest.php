<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_menggunakan_database_kasir_testing(): void
    {
        $this->assertSame('kasir_testing', DB::connection()->getDatabaseName());
    }

    public function test_zona_waktu_aplikasi_wib(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
    }

    public function test_seeder_membuat_tenant_kategori_dan_menu_asli(): void
    {
        $this->seed();

        $this->assertDatabaseCount('tenants', 4);
        $this->assertDatabaseCount('categories', 8);
        $this->assertDatabaseCount('menus', 79);

        $this->assertDatabaseHas('tenants', [
            'name' => 'Warmindo',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('tenants', [
            'name' => 'Warung Nasi Anugrah',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('tenants', [
            'name' => 'Angkringan Salara',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('tenants', [
            'name' => 'Tenant 4',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('menus', [
            'name' => 'Indomi',
            'price' => null,
        ]);
        $this->assertDatabaseHas('menus', [
            'name' => 'Soto Mie',
            'price' => null,
        ]);
        $this->assertDatabaseHas('menus', [
            'name' => 'Ayam Penyet',
            'price' => null,
        ]);
        $this->assertDatabaseHas('menus', [
            'name' => 'Es Kopi Susu',
            'price' => null,
        ]);

        $warmindo = Tenant::where('name', 'Warmindo')->firstOrFail();
        $anugrah = Tenant::where('name', 'Warung Nasi Anugrah')->firstOrFail();
        $salara = Tenant::where('name', 'Angkringan Salara')->firstOrFail();

        $this->assertSame(26, $warmindo->menus()->count());
        $this->assertSame(14, $anugrah->menus()->count());
        $this->assertSame(39, $salara->menus()->count());
    }

    public function test_semua_menu_seeder_dibuat_tanpa_harga(): void
    {
        $this->seed();

        $this->assertSame(0, Menu::whereNotNull('price')->count());
        $this->assertSame(79, Menu::whereNull('price')->count());
    }

    public function test_tenant_keempat_nonaktif_dan_tanpa_menu(): void
    {
        $this->seed();

        $tenant = Tenant::where('name', 'Tenant 4')->firstOrFail();

        $this->assertFalse($tenant->is_active);
        $this->assertSame(0, $tenant->categories()->count());
        $this->assertSame(0, $tenant->menus()->count());
    }

    public function test_nama_kategori_boleh_sama_di_tenant_berbeda(): void
    {
        $this->seed();

        $makanan = Category::where('name', 'Makanan')->get();

        $this->assertCount(2, $makanan);
        $this->assertNotSame($makanan[0]->tenant_id, $makanan[1]->tenant_id);
    }

    public function test_user_seeder_tidak_berjalan_di_environment_testing(): void
    {
        $this->seed();

        $this->assertDatabaseCount('users', 0);
    }
}
