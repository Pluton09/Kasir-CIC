<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_riwayat_memerlukan_login(): void
    {
        $this->get('/riwayat')->assertRedirect(route('login'));
    }

    public function test_ringkasan_total_penjualan_per_tenant_hari_ini(): void
    {
        $user = User::factory()->create();
        $warmindo = Tenant::factory()->create(['name' => 'Warmindo']);
        $anugrah = Tenant::factory()->create(['name' => 'Warung Nasi Anugrah']);

        $indomi = $this->createMenu($warmindo, 'Indomi', 11000);
        $ayamPenyet = $this->createMenu($anugrah, 'Ayam Penyet', 27000);

        $this->travelTo(Carbon::parse('2026-11-25 10:00:00', 'Asia/Jakarta'));

        try {
            $this->actingAs($user);

            $this->postJson('/api/transactions', [
                'payment_method' => 'Tunai',
                'paid_amount' => 25000,
                'items' => [
                    ['menu_id' => $indomi->id, 'qty' => 2],
                ],
            ])->assertCreated();

            $this->postJson('/api/transactions', [
                'payment_method' => 'QRIS',
                'items' => [
                    ['menu_id' => $ayamPenyet->id, 'qty' => 1],
                ],
            ])->assertCreated();

            $response = $this->get('/riwayat');

            $response->assertOk()
                ->assertSee('Warmindo')
                ->assertSee('Warung Nasi Anugrah')
                ->assertSee('Rp 22.000')
                ->assertSee('Rp 27.000')
                ->assertSee('Rp 49.000');

            $response->assertViewHas('tenantTotals', function ($totals) {
                $byName = $totals->keyBy('tenant_name');

                return (int) $byName['Warmindo']->total === 22000
                    && (int) $byName['Warmindo']->qty === 2
                    && (int) $byName['Warung Nasi Anugrah']->total === 27000
                    && (int) $byName['Warung Nasi Anugrah']->qty === 1;
            });

            $response->assertViewHas('grandTotal', 49000);
        } finally {
            $this->travelBack();
        }
    }

    public function test_transaksi_kemarin_tidak_masuk_ringkasan_hari_ini(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['name' => 'Warmindo']);
        $menu = $this->createMenu($tenant, 'Indomi', 11000);

        try {
            $this->travelTo(Carbon::parse('2026-11-24 23:30:00', 'Asia/Jakarta'));

            $this->actingAs($user)->postJson('/api/transactions', [
                'payment_method' => 'Tunai',
                'paid_amount' => 11000,
                'items' => [
                    ['menu_id' => $menu->id, 'qty' => 1],
                ],
            ])->assertCreated();

            $this->travelTo(Carbon::parse('2026-11-25 00:30:00', 'Asia/Jakarta'));

            $this->postJson('/api/transactions', [
                'payment_method' => 'Tunai',
                'paid_amount' => 22000,
                'items' => [
                    ['menu_id' => $menu->id, 'qty' => 2],
                ],
            ])->assertCreated();

            $response = $this->get('/riwayat');

            $response->assertOk()
                ->assertViewHas('transactions', fn ($transactions) => $transactions->count() === 1)
                ->assertViewHas('grandTotal', 22000)
                ->assertViewHas('tenantTotals', function ($totals) {
                    return $totals->count() === 1
                        && (int) $totals->first()->total === 22000
                        && (int) $totals->first()->qty === 2;
                });
        } finally {
            $this->travelBack();
        }
    }

    public function test_riwayat_menampilkan_nama_tenant_per_item(): void
    {
        $user = User::factory()->create();
        $warmindo = Tenant::factory()->create(['name' => 'Warmindo']);
        $salara = Tenant::factory()->create(['name' => 'Angkringan Salara']);

        $indomi = $this->createMenu($warmindo, 'Indomi', 11000);
        $nasgor = $this->createMenu($salara, 'Nasgor Spesial', 27000);

        $this->actingAs($user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 50000,
            'items' => [
                ['menu_id' => $indomi->id, 'qty' => 1],
                ['menu_id' => $nasgor->id, 'qty' => 1],
            ],
        ])->assertCreated();

        $this->get('/riwayat')
            ->assertOk()
            ->assertSee('Warmindo')
            ->assertSee('Indomi')
            ->assertSee('Angkringan Salara')
            ->assertSee('Nasgor Spesial')
            ->assertSee('Rp 38.000');
    }

    private function createMenu(Tenant $tenant, string $name, int $price): Menu
    {
        $category = Category::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        return Menu::factory()->create([
            'category_id' => $category->id,
            'name' => $name,
            'price' => $price,
        ]);
    }
}
