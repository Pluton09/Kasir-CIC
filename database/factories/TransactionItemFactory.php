<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\Tenant;
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
            'tenant_id' => Tenant::factory(),
            'tenant_name' => fake()->company(),
            'menu_id' => $menu,
            'menu_name' => fake()->words(2, true),
            'price' => 6000,
            'qty' => 2,
            'subtotal' => 12000,
        ];
    }
}
