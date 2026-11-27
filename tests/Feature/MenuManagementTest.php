<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_harga_menu_memerlukan_login(): void
    {
        $this->get('/kelola/menu')->assertRedirect(route('login'));
    }

    public function test_halaman_menampilkan_menu_belum_berharga_per_tenant(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['name' => 'Warmindo', 'is_active' => true]);
        $category = Category::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Aneka Minuman',
        ]);

        Menu::factory()->create([
            'category_id' => $category->id,
            'name' => 'Teh Manis',
            'price' => null,
        ]);
        Menu::factory()->create([
            'category_id' => $category->id,
            'name' => 'Kopi Panas',
            'price' => 7000,
        ]);

        $this->actingAs($user)->get('/kelola/menu')
            ->assertOk()
            ->assertSee('Warmindo')
            ->assertSee('Aneka Minuman')
            ->assertSee('Teh Manis')
            ->assertSee('Kopi Panas')
            ->assertSee('Belum diisi')
            ->assertSee('value="7000"', false);
    }

    public function test_filter_hanya_yang_belum_berharga(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['name' => 'Warmindo']);
        $category = Category::factory()->create(['tenant_id' => $tenant->id]);

        Menu::factory()->create([
            'category_id' => $category->id,
            'name' => 'Menu Kosong',
            'price' => null,
        ]);
        Menu::factory()->create([
            'category_id' => $category->id,
            'name' => 'Menu Berharga',
            'price' => 9000,
        ]);

        $this->actingAs($user)->get('/kelola/menu?kosong=1')
            ->assertOk()
            ->assertSee('Menu Kosong')
            ->assertDontSee('Menu Berharga');
    }

    public function test_harga_dapat_disimpan_per_baris_tanpa_mengubah_menu_lain(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['name' => 'Warmindo']);
        $category = Category::factory()->create(['tenant_id' => $tenant->id]);

        $menu = Menu::factory()->create([
            'category_id' => $category->id,
            'name' => 'Indomi',
            'price' => null,
        ]);
        $other = Menu::factory()->create([
            'category_id' => $category->id,
            'name' => 'Ramen',
            'price' => 23000,
        ]);

        $this->actingAs($user)
            ->patch(route('menus.price', $menu), ['price' => 15000])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('menus', ['id' => $menu->id, 'price' => 15000]);
        $this->assertDatabaseHas('menus', ['id' => $other->id, 'price' => 23000]);
    }

    public function test_harga_nol_diterima(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $category = Category::factory()->create(['tenant_id' => $tenant->id]);
        $menu = Menu::factory()->create([
            'category_id' => $category->id,
            'price' => null,
        ]);

        $this->actingAs($user)
            ->patch(route('menus.price', $menu), ['price' => 0])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('menus', ['id' => $menu->id, 'price' => 0]);
    }

    public function test_harga_harus_integer_dan_tidak_negatif(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $category = Category::factory()->create(['tenant_id' => $tenant->id]);
        $menu = Menu::factory()->create([
            'category_id' => $category->id,
            'price' => null,
        ]);

        foreach (['abc', '1.5', -1, ''] as $value) {
            $this->actingAs($user)
                ->patch(route('menus.price', $menu), ['price' => $value])
                ->assertSessionHasErrors('price');
        }

        $this->assertDatabaseHas('menus', ['id' => $menu->id, 'price' => null]);
    }
}
