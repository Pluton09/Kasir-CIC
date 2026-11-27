<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Data awal tenant, kategori, dan menu kantin.
 *
 * Harga menu sengaja dikosongkan (NULL) karena belum tersedia. Isi harga
 * lewat halaman Kelola Menu.
 *
 * Seeder ini tidak dijalankan otomatis oleh aplikasi. Jalankan hanya lewat
 * perintah eksplisit, misalnya:
 *
 *     php artisan db:seed --class=KantinSeeder
 */
class KantinSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->tenants() as $tenantData) {
            $tenant = Tenant::updateOrCreate(
                ['name' => $tenantData['name']],
                [
                    'sort_order' => $tenantData['sort_order'],
                    'is_active' => $tenantData['is_active'],
                ],
            );

            foreach ($tenantData['categories'] as $categoryName => $menuNames) {
                $category = Category::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => $categoryName],
                    ['is_active' => true],
                );

                foreach ($menuNames as $name) {
                    Menu::updateOrCreate(
                        ['category_id' => $category->id, 'name' => $name],
                        ['price' => null, 'is_active' => true],
                    );
                }
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tenants(): array
    {
        return [
            [
                'name' => 'Warmindo',
                'sort_order' => 1,
                'is_active' => true,
                'categories' => [
                    'Aneka Mie & Ramen' => [
                        'Indomi',
                        'Indomi Rebus Telor',
                        'Indomi Goreng Telor',
                        'Pop Mie',
                        'Indomi Dabel Telor',
                        'Indomi Spesial',
                        'Indomi Kumplit',
                        'Indomi Tek-Tek',
                        'Mie Gacoan',
                        'Mie Jebew',
                        'Ramen',
                    ],
                    'Mie Persis' => [
                        'Vegan',
                        'Soto Mie',
                        'Kari Ayam',
                        'Cakalang',
                        'Rendang',
                    ],
                    'Toping' => [
                        'Telor',
                        'Baso',
                        'Kornet',
                        'Keju',
                    ],
                    'Aneka Minuman' => [
                        'Kopi Panas',
                        'Dancow',
                        'Teh Manis',
                        'Teh Tawar',
                        'Jeruk Peras Panas',
                        'Nescafé',
                    ],
                ],
            ],
            [
                'name' => 'Warung Nasi Anugrah',
                'sort_order' => 2,
                'is_active' => true,
                'categories' => [
                    'Makanan' => [
                        'Jukut Goreng',
                        'Ayam Penyet',
                        'Chicken Teriyaki',
                        'Ayam Bakar',
                        'Ayam Goreng',
                        'Beef Teriyaki',
                    ],
                    'Aneka Cemilan' => [
                        'Susu Murni',
                        'Roti Bakar Manis Keju',
                        'Roti Bakar Manis Coklat',
                        'Roti Bakar Kornet',
                        'Roti Bakar Kornet Telur',
                        'Roti Bakar Telur',
                        'Dimsum',
                        'Pisang Keju',
                    ],
                ],
            ],
            [
                'name' => 'Angkringan Salara',
                'sort_order' => 3,
                'is_active' => true,
                'categories' => [
                    'Makanan' => [
                        'Nasi Putih',
                        'Nasi Ayam Geprek',
                        'Nasi Ayam Katsu',
                        'Nasi Ikan Goreng',
                        'Nasi Telur Ceplok Balado',
                        'Nasi Telur Dadar Kari',
                        'Nasi Telur Dadar Geprek',
                        'Nasgor Spesial',
                        'Nasgor Katsu',
                        'Nasgor Ayam Geprek',
                        'Tumis Kangkung',
                        'Tumis Genjer',
                        'Tumis Toge dan Tahu',
                        'Mie Nyemek',
                        'Mie Tulang',
                        'Omelet',
                        'Kentang Goreng Sosis',
                        'Kentang Goreng Nugget',
                        'Kentang Goreng Katsu',
                        'Tahu Cabe Garam',
                        'Tahu Gejrot',
                        'Seblak Biasa',
                        'Seblak Spesial',
                    ],
                    'Minuman' => [
                        'Es Teh Manis',
                        'Es Teh Tawar',
                        'Dancow Dingin',
                        'America Ice',
                        'Es Kopi Susu',
                        'Es Kopi Gula Aren',
                        'Es Kopi Latte',
                        'Es Kopi Butterscotch',
                        'Juice Melon',
                        'Juice Naga',
                        'Juice Strawberry',
                        'Soda Gembira',
                        'Extra Jos Susu',
                        'Nutrisari',
                        'Pop Ice',
                        'Kopi Dingin',
                    ],
                ],
            ],
            [
                // Nama tenant belum ada; diisi lewat halaman Kelola Tenant.
                'name' => 'Tenant 4',
                'sort_order' => 4,
                'is_active' => false,
                'categories' => [],
            ],
        ];
    }
}
