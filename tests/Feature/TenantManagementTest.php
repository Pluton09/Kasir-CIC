<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_kelola_memerlukan_login(): void
    {
        $this->get('/kelola')->assertRedirect(route('login'));
    }

    public function test_halaman_kelola_menampilkan_semua_tenant_termasuk_yang_nonaktif(): void
    {
        $user = User::factory()->create();

        Tenant::factory()->create(['name' => 'Warmindo', 'is_active' => true]);
        Tenant::factory()->create(['name' => 'Tenant Tutup', 'is_active' => false]);

        $this->actingAs($user)->get('/kelola')
            ->assertOk()
            ->assertSee('Warmindo')
            ->assertSee('Tenant Tutup')
            ->assertSee('Aktif')
            ->assertSee('Nonaktif');
    }

    public function test_tenant_baru_dapat_ditambahkan_dan_langsung_aktif(): void
    {
        $user = User::factory()->create();

        Tenant::factory()->create(['sort_order' => 4]);

        $this->actingAs($user)->post(route('tenants.store'), [
            'name' => 'Tenant Baru',
        ])->assertRedirect();

        $tenant = Tenant::where('name', 'Tenant Baru')->firstOrFail();

        $this->assertTrue($tenant->is_active);
        $this->assertSame(5, $tenant->sort_order);
    }

    public function test_tenant_baru_tanpa_menu_muncul_di_api_kasir(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tenants.store'), [
            'name' => 'Tenant Empat Baru',
        ])->assertRedirect();

        $response = $this->getJson('/api/menus')->assertOk();

        $tenant = collect($response->json('tenants'))
            ->firstWhere('name', 'Tenant Empat Baru');

        $this->assertNotNull($tenant);
        $this->assertSame([], $tenant['categories']);
    }

    public function test_nama_tenant_wajib_diisi_dan_tidak_boleh_duplikat(): void
    {
        $user = User::factory()->create();

        Tenant::factory()->create(['name' => 'Warmindo']);

        $this->actingAs($user)->post(route('tenants.store'), [
            'name' => '',
        ])->assertSessionHasErrors('name');

        $this->actingAs($user)->post(route('tenants.store'), [
            'name' => 'Warmindo',
        ])->assertSessionHasErrors('name');

        $this->assertDatabaseCount('tenants', 1);
    }

    public function test_nama_tenant_dapat_diubah(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['name' => 'Warmindo']);

        $this->actingAs($user)->patch(route('tenants.update', $tenant), [
            'name' => 'Warmindo Baru',
        ])->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Warmindo Baru',
        ]);
    }

    public function test_tenant_nonaktif_hilang_dari_kasir_dan_ditolak_saat_transaksi(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create([
            'name' => 'Warmindo',
            'is_active' => true,
        ]);

        $category = Category::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        $menu = Menu::factory()->create([
            'category_id' => $category->id,
            'name' => 'Indomi',
            'price' => 11000,
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('tenants.toggle', $tenant))
            ->assertRedirect();

        $tenant->refresh();
        $this->assertFalse($tenant->is_active);

        $this->getJson('/api/menus')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Warmindo']);

        $this->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 11000,
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 1],
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('transactions', 0);

        $this->post(route('tenants.toggle', $tenant))->assertRedirect();

        $tenant->refresh();
        $this->assertTrue($tenant->is_active);

        $this->getJson('/api/menus')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Warmindo']);
    }
}
