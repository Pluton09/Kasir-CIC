<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_menu_menolak_tanpa_login(): void
    {
        $this->getJson('/api/menus')->assertUnauthorized();
    }

    public function test_endpoint_menu_hanya_mengembalikan_tenant_kategori_dan_menu_aktif(): void
    {
        $user = User::factory()->create();

        $tenant = Tenant::factory()->create([
            'name' => 'Warmindo',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $inactiveTenant = Tenant::factory()->create([
            'name' => 'Tenant Nonaktif',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $activeCategory = Category::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Aneka Minuman',
            'is_active' => true,
        ]);
        $inactiveCategory = Category::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Kategori Nonaktif',
            'is_active' => false,
        ]);
        $inactiveTenantCategory = Category::factory()->create([
            'tenant_id' => $inactiveTenant->id,
            'name' => 'Kategori Tenant Nonaktif',
            'is_active' => true,
        ]);

        $activeMenu = Menu::factory()->create([
            'category_id' => $activeCategory->id,
            'name' => 'Teh Manis',
            'price' => 7000,
            'is_active' => true,
        ]);
        Menu::factory()->create([
            'category_id' => $activeCategory->id,
            'name' => 'Menu Nonaktif',
            'is_active' => false,
        ]);
        Menu::factory()->create([
            'category_id' => $inactiveCategory->id,
            'name' => 'Menu Kategori Nonaktif',
            'is_active' => true,
        ]);
        Menu::factory()->create([
            'category_id' => $inactiveTenantCategory->id,
            'name' => 'Menu Tenant Nonaktif',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/menus');

        $response->assertOk()
            ->assertJsonCount(1, 'tenants')
            ->assertJsonCount(1, 'tenants.0.categories')
            ->assertJsonCount(1, 'tenants.0.categories.0.menus')
            ->assertJsonPath('tenants.0.name', 'Warmindo')
            ->assertJsonPath('tenants.0.categories.0.name', 'Aneka Minuman')
            ->assertJsonPath('tenants.0.categories.0.menus.0.id', $activeMenu->id)
            ->assertJsonPath('tenants.0.categories.0.menus.0.name', 'Teh Manis')
            ->assertJsonPath('tenants.0.categories.0.menus.0.price', 7000);

        $response->assertJsonMissing(['name' => 'Tenant Nonaktif'])
            ->assertJsonMissing(['name' => 'Kategori Nonaktif'])
            ->assertJsonMissing(['name' => 'Menu Nonaktif'])
            ->assertJsonMissing(['name' => 'Menu Kategori Nonaktif'])
            ->assertJsonMissing(['name' => 'Menu Tenant Nonaktif']);
    }

    public function test_tenant_diurutkan_berdasarkan_sort_order(): void
    {
        $user = User::factory()->create();

        Tenant::factory()->create(['name' => 'Tenant Ketiga', 'sort_order' => 30]);
        Tenant::factory()->create(['name' => 'Tenant Pertama', 'sort_order' => 10]);
        Tenant::factory()->create(['name' => 'Tenant Kedua', 'sort_order' => 20]);

        $this->actingAs($user)->getJson('/api/menus')
            ->assertOk()
            ->assertJsonPath('tenants.0.name', 'Tenant Pertama')
            ->assertJsonPath('tenants.1.name', 'Tenant Kedua')
            ->assertJsonPath('tenants.2.name', 'Tenant Ketiga');
    }

    public function test_menu_tanpa_harga_tetap_muncul_dengan_price_null(): void
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

        Menu::factory()->create([
            'category_id' => $category->id,
            'name' => 'Indomi',
            'price' => null,
            'is_active' => true,
        ]);

        $this->actingAs($user)->getJson('/api/menus')
            ->assertOk()
            ->assertJsonPath('tenants.0.categories.0.menus.0.name', 'Indomi')
            ->assertJsonPath('tenants.0.categories.0.menus.0.price', null);
    }
}
