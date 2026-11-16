<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionItem>
 */
class TransactionItemFactory extends Factory
{
    public function definition(): array
    {
        $menu = Menu::factory();

        return [
            'transaction_id' => Transaction::factory(),
            'menu_id' => $menu,
            'menu_name' => fake()->words(2, true),
            'price' => 6000,
            'qty' => 2,
            'subtotal' => 12000,
        ];
    }
}
