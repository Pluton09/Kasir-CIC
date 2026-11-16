<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $total = fake()->numberBetween(1, 20) * 1000;
        $paymentMethod = fake()->randomElement(['tunai', 'qris', 'transfer']);
        $paidAmount = $paymentMethod === 'tunai'
            ? $total + (fake()->numberBetween(0, 5) * 1000)
            : $total;

        return [
            'code' => 'TRX-'.now()->format('Ymd').'-'.fake()->unique()->numerify('###'),
            'table_no' => fake()->optional()->numerify('##'),
            'customer_name' => fake()->optional()->name(),
            'note' => fake()->optional()->sentence(),
            'payment_method' => $paymentMethod,
            'total' => $total,
            'paid_amount' => $paidAmount,
            'change_amount' => $paidAmount - $total,
            'user_id' => User::factory(),
        ];
    }
}
