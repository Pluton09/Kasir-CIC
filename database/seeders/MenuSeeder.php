<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            'Minuman' => [
                ['Teh Manis', 6000],
                ['Teh Tarik', 6000],
                ['Teh Tawar', 6000],
                ['Kopi Hitam', 5000],
                ['Es Jeruk', 7000],
            ],
            'Makanan' => [
                ['Telor Ayam Kampung BJ', 8000],
                ['Nasi Putih', 5000],
                ['Mie Goreng', 12000],
                ['Nasi Ayam Goreng', 18000],
                ['Nasi Goreng', 15000],
                ['Mie Ayam', 13000],
            ],
            'Toping' => [
                ['Toping Bakso', 5000],
                ['Toping Keju', 5000],
                ['Toping Kornet', 5000],
                ['Toping Sosis', 5000],
            ],
            'Jajanan' => [
                ['Gorengan', 2000],
                ['Roti Bakar', 10000],
                ['Pisang Goreng', 5000],
                ['Tahu Isi', 3000],
            ],
        ];

        foreach ($menus as $categoryName => $items) {
            $category = Category::where('name', $categoryName)->firstOrFail();

            foreach ($items as [$name, $price]) {
                Menu::updateOrCreate(
                    ['category_id' => $category->id, 'name' => $name],
                    ['price' => $price, 'is_active' => true],
                );
            }
        }
    }
}
