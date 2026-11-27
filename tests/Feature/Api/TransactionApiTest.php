<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_endpoint_transaksi_menolak_tanpa_login(): void
    {
        $this->postJson('/api/transactions', [])->assertUnauthorized();
    }

    public function test_total_dihitung_server_dan_harga_palsu_dari_frontend_diabaikan(): void
    {
        $menu = $this->createMenu(6000);
        $tenantId = $menu->category->tenant_id;

        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'table_no' => '4',
            'customer_name' => 'Budi',
            'note' => 'Tanpa es',
            'payment_method' => 'Tunai',
            'paid_amount' => 20000,
            'total' => 1,
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'qty' => 2,
                    'price' => 1,
                    'subtotal' => 1,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'code',
                'created_at',
                'payment_method',
                'total',
                'paid_amount',
                'change_amount',
                'table_no',
                'customer_name',
                'note',
                'items' => [
                    ['tenant_id', 'tenant_name', 'menu_name', 'price', 'qty', 'subtotal'],
                ],
            ])
            ->assertJsonPath('code', 'TRX-'.now()->format('Ymd').'-001')
            ->assertJsonPath('table_no', '4')
            ->assertJsonPath('customer_name', 'Budi')
            ->assertJsonPath('note', 'Tanpa es')
            ->assertJsonPath('total', 12000)
            ->assertJsonPath('paid_amount', 20000)
            ->assertJsonPath('change_amount', 8000)
            ->assertJsonPath('items.0.tenant_id', $tenantId)
            ->assertJsonPath('items.0.tenant_name', 'Warmindo')
            ->assertJsonPath('items.0.menu_name', 'Teh Manis')
            ->assertJsonPath('items.0.price', 6000)
            ->assertJsonPath('items.0.qty', 2)
            ->assertJsonPath('items.0.subtotal', 12000);

        $this->assertDatabaseHas('transactions', [
            'code' => 'TRX-'.now()->format('Ymd').'-001',
            'total' => 12000,
            'paid_amount' => 20000,
            'change_amount' => 8000,
        ]);
    }

    public function test_snapshot_harga_item_tetap_utuh_setelah_harga_menu_diubah(): void
    {
        $menu = $this->createMenu(6000);

        $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 6000,
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 1],
            ],
        ])->assertCreated();

        $item = Transaction::firstOrFail()->items()->firstOrFail();

        $menu->update([
            'name' => 'Teh Manis Baru',
            'price' => 9000,
        ]);

        $item->refresh();

        $this->assertSame('Teh Manis', $item->menu_name);
        $this->assertSame(6000, $item->price);
        $this->assertSame(6000, $item->subtotal);
    }

    public function test_snapshot_tenant_tetap_utuh_setelah_tenant_diganti_nama(): void
    {
        $tenant = $this->createTenant('Warmindo');
        $menu = $this->createMenu(6000, tenant: $tenant);

        $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 6000,
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 1],
            ],
        ])->assertCreated();

        $item = Transaction::firstOrFail()->items()->firstOrFail();

        $tenant->update(['name' => 'Warmindo Baru']);

        $item->refresh();

        $this->assertSame($tenant->id, $item->tenant_id);
        $this->assertSame('Warmindo', $item->tenant_name);
    }

    public function test_transaksi_campuran_dua_tenant_tersimpan_benar(): void
    {
        $warmindo = $this->createTenant('Warmindo');
        $anugrah = $this->createTenant('Warung Nasi Anugrah');

        $indomi = $this->createMenu(11000, name: 'Indomi', tenant: $warmindo);
        $ayamPenyet = $this->createMenu(27000, name: 'Ayam Penyet', tenant: $anugrah);

        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'table_no' => '7',
            'payment_method' => 'Tunai',
            'paid_amount' => 50000,
            'items' => [
                ['menu_id' => $indomi->id, 'qty' => 1],
                ['menu_id' => $ayamPenyet->id, 'qty' => 1],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('total', 38000)
            ->assertJsonPath('paid_amount', 50000)
            ->assertJsonPath('change_amount', 12000)
            ->assertJsonPath('items.0.tenant_id', $warmindo->id)
            ->assertJsonPath('items.0.tenant_name', 'Warmindo')
            ->assertJsonPath('items.0.menu_name', 'Indomi')
            ->assertJsonPath('items.1.tenant_id', $anugrah->id)
            ->assertJsonPath('items.1.tenant_name', 'Warung Nasi Anugrah')
            ->assertJsonPath('items.1.menu_name', 'Ayam Penyet');

        $this->assertDatabaseHas('transaction_items', [
            'tenant_id' => $warmindo->id,
            'tenant_name' => 'Warmindo',
            'menu_name' => 'Indomi',
            'subtotal' => 11000,
        ]);
        $this->assertDatabaseHas('transaction_items', [
            'tenant_id' => $anugrah->id,
            'tenant_name' => 'Warung Nasi Anugrah',
            'menu_name' => 'Ayam Penyet',
            'subtotal' => 27000,
        ]);
    }

    public function test_tenant_id_dan_nama_dari_frontend_diabaikan(): void
    {
        $menu = $this->createMenu(6000);
        $tenantId = $menu->category->tenant_id;

        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 6000,
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'qty' => 1,
                    'tenant_id' => 99999,
                    'tenant_name' => 'Tenant Palsu',
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('items.0.tenant_id', $tenantId)
            ->assertJsonPath('items.0.tenant_name', 'Warmindo');
    }

    public function test_tunai_kurang_ditolak_dan_tidak_menyimpan_transaksi(): void
    {
        $menu = $this->createMenu(6000);

        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 5000,
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('paid_amount');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertDatabaseCount('sequences', 0);
    }

    public function test_tunai_tanpa_paid_amount_ditolak(): void
    {
        $menu = $this->createMenu(6000);

        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('paid_amount');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_menu_nonaktif_ditolak(): void
    {
        $menu = $this->createMenu(6000, isActive: false);

        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 6000,
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
    }

    public function test_menu_di_kategori_nonaktif_ditolak(): void
    {
        $menu = $this->createMenu(6000, categoryActive: false);

        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 6000,
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_menu_di_tenant_nonaktif_ditolak(): void
    {
        $tenant = $this->createTenant('Tenant Tutup', isActive: false);
        $menu = $this->createMenu(6000, tenant: $tenant);

        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 6000,
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertDatabaseCount('sequences', 0);
    }

    public function test_menu_tidak_ada_ditolak(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 6000,
            'items' => [
                ['menu_id' => 99999, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('items.0.menu_id');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_items_kosong_ditolak(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 6000,
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_qty_di_luar_1_sampai_99_ditolak(): void
    {
        $menu = $this->createMenu(6000);

        foreach ([0, 100] as $qty) {
            $this->actingAs($this->user)->postJson('/api/transactions', [
                'payment_method' => 'Tunai',
                'paid_amount' => 6000,
                'items' => [
                    ['menu_id' => $menu->id, 'qty' => $qty],
                ],
            ])->assertStatus(422)
                ->assertJsonValidationErrors('items.0.qty');
        }

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_qris_dan_transfer_dianggap_lunas_dengan_kembalian_nol(): void
    {
        $menu = $this->createMenu(6000);

        foreach (['QRIS', 'Transfer'] as $method) {
            $response = $this->actingAs($this->user)->postJson('/api/transactions', [
                'payment_method' => $method,
                'paid_amount' => 1,
                'items' => [
                    ['menu_id' => $menu->id, 'qty' => 1],
                ],
            ]);

            $response->assertCreated()
                ->assertJsonPath('payment_method', $method)
                ->assertJsonPath('total', 6000)
                ->assertJsonPath('paid_amount', 6000)
                ->assertJsonPath('change_amount', 0);
        }
    }

    public function test_transaksi_berurutan_tidak_loncat_saat_gagal_dan_reset_besok_hari(): void
    {
        $this->travelTo(Carbon::parse('2026-11-16 10:00:00'));

        try {
            $menu = $this->createMenu(6000);

            $this->actingAs($this->user)->postJson('/api/transactions', [
                'payment_method' => 'Tunai',
                'paid_amount' => 6000,
                'items' => [['menu_id' => $menu->id, 'qty' => 1]],
            ])->assertCreated()
                ->assertJsonPath('code', 'TRX-20261116-001');

            $this->actingAs($this->user)->postJson('/api/transactions', [
                'payment_method' => 'Tunai',
                'paid_amount' => 6000,
                'items' => [['menu_id' => $menu->id, 'qty' => 1]],
            ])->assertCreated()
                ->assertJsonPath('code', 'TRX-20261116-002');

            $inactiveMenu = $this->createMenu(6000, isActive: false);

            $this->actingAs($this->user)->postJson('/api/transactions', [
                'payment_method' => 'Tunai',
                'paid_amount' => 6000,
                'items' => [['menu_id' => $inactiveMenu->id, 'qty' => 1]],
            ])->assertStatus(422);

            $this->actingAs($this->user)->postJson('/api/transactions', [
                'payment_method' => 'Tunai',
                'paid_amount' => 6000,
                'items' => [['menu_id' => $menu->id, 'qty' => 1]],
            ])->assertCreated()
                ->assertJsonPath('code', 'TRX-20261116-003');

            $this->travelTo(Carbon::parse('2026-11-17 09:00:00'));

            $this->actingAs($this->user)->postJson('/api/transactions', [
                'payment_method' => 'Tunai',
                'paid_amount' => 6000,
                'items' => [['menu_id' => $menu->id, 'qty' => 1]],
            ])->assertCreated()
                ->assertJsonPath('code', 'TRX-20261117-001');
        } finally {
            $this->travelBack();
        }
    }

    public function test_transaksi_dengan_satu_menu_nonaktif_tidak_menyimpan_item_sebagian(): void
    {
        $activeMenu = $this->createMenu(6000);
        $inactiveMenu = $this->createMenu(5000, isActive: false);

        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 20000,
            'items' => [
                ['menu_id' => $activeMenu->id, 'qty' => 1],
                ['menu_id' => $inactiveMenu->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertDatabaseCount('sequences', 0);
    }

    public function test_payment_method_hanya_boleh_tunai_qris_atau_transfer(): void
    {
        $menu = $this->createMenu(6000);

        foreach (['tunai', 'qris', 'transfer', 'cash', 'kartu'] as $method) {
            $this->actingAs($this->user)->postJson('/api/transactions', [
                'payment_method' => $method,
                'paid_amount' => 6000,
                'items' => [
                    ['menu_id' => $menu->id, 'qty' => 1],
                ],
            ])->assertStatus(422)
                ->assertJsonValidationErrors('payment_method');
        }

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_menu_yang_sama_digabung_menjadi_satu_item(): void
    {
        $menu = $this->createMenu(6000);

        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 30000,
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 2],
                ['menu_id' => $menu->id, 'qty' => 3],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('total', 30000)
            ->assertJsonPath('items.0.menu_name', 'Teh Manis')
            ->assertJsonPath('items.0.qty', 5)
            ->assertJsonPath('items.0.subtotal', 30000);

        $this->assertDatabaseCount('transaction_items', 1);
        $this->assertDatabaseHas('transaction_items', [
            'menu_id' => $menu->id,
            'qty' => 5,
            'subtotal' => 30000,
        ]);
    }

    public function test_gabungan_qty_menu_yang_sama_tidak_boleh_lebih_dari_99(): void
    {
        $menu = $this->createMenu(6000);

        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 600000,
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 50],
                ['menu_id' => $menu->id, 'qty' => 50],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertDatabaseCount('sequences', 0);
    }

    public function test_batas_panjang_field_dan_batas_paid_amount_diterapkan(): void
    {
        $menu = $this->createMenu(6000);

        $base = [
            'payment_method' => 'Tunai',
            'paid_amount' => 6000,
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 1],
            ],
        ];

        $cases = [
            ['field' => 'table_no', 'value' => str_repeat('1', 21)],
            ['field' => 'customer_name', 'value' => str_repeat('a', 101)],
            ['field' => 'note', 'value' => str_repeat('a', 256)],
            ['field' => 'paid_amount', 'value' => -1],
            ['field' => 'paid_amount', 'value' => 100000001],
            ['field' => 'paid_amount', 'value' => 'bukan-angka'],
        ];

        foreach ($cases as $case) {
            $payload = array_merge($base, [$case['field'] => $case['value']]);

            $this->actingAs($this->user)->postJson('/api/transactions', $payload)
                ->assertStatus(422)
                ->assertJsonValidationErrors($case['field']);
        }

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_menu_tanpa_harga_ditolak_walau_frontend_dilewati(): void
    {
        $menu = $this->createMenu(price: null);

        $response = $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 20000,
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('items');

        $this->assertStringContainsString(
            'belum memiliki harga',
            (string) $response->json('errors.items.0'),
        );

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertDatabaseCount('sequences', 0);
    }

    public function test_menu_bisa_dijual_setelah_harga_diisi(): void
    {
        $menu = $this->createMenu(price: null);

        $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 6000,
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 1],
            ],
        ])->assertStatus(422);

        $menu->update(['price' => 6000]);

        $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 6000,
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 1],
            ],
        ])->assertCreated()
            ->assertJsonPath('total', 6000)
            ->assertJsonPath('items.0.price', 6000)
            ->assertJsonPath('items.0.subtotal', 6000);
    }

    public function test_snapshot_harga_item_tidak_pernah_null(): void
    {
        $menu = $this->createMenu(price: 6000);

        $this->actingAs($this->user)->postJson('/api/transactions', [
            'payment_method' => 'Tunai',
            'paid_amount' => 6000,
            'items' => [
                ['menu_id' => $menu->id, 'qty' => 1],
            ],
        ])->assertCreated();

        // Harga menu dikosongkan lagi setelah transaksi; snapshot tetap terisi.
        $menu->update(['price' => null]);

        $item = Transaction::firstOrFail()->items()->firstOrFail();
        $item->refresh();

        $this->assertNotNull($item->price);
        $this->assertSame(6000, $item->price);

        $this->assertDatabaseHas('transaction_items', [
            'menu_id' => $menu->id,
            'price' => 6000,
        ]);
    }

    private function createTenant(string $name = 'Warmindo', bool $isActive = true): Tenant
    {
        return Tenant::factory()->create([
            'name' => $name,
            'is_active' => $isActive,
        ]);
    }

    private function createMenu(
        ?int $price = 6000,
        bool $isActive = true,
        bool $categoryActive = true,
        string $name = 'Teh Manis',
        ?Tenant $tenant = null,
        bool $tenantActive = true,
        string $tenantName = 'Warmindo',
    ): Menu {
        $tenant ??= $this->createTenant($tenantName, $tenantActive);

        $category = Category::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => $categoryActive,
        ]);

        return Menu::factory()->create([
            'category_id' => $category->id,
            'name' => $name,
            'price' => $price,
            'is_active' => $isActive,
        ]);
    }
}
