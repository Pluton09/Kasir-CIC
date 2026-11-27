<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_kasir_memerlukan_login(): void
    {
        $this->get('/kasir')->assertRedirect(route('login'));
    }

    public function test_halaman_kasir_dapat_dibuka_setelah_login(): void
    {
        $this->withoutVite();

        $user = User::factory()->create(['name' => 'Kasir Satu']);

        $this->actingAs($user)
            ->get('/kasir')
            ->assertOk()
            ->assertSee('id="kasir-app"', false)
            ->assertSee('name="csrf-token"', false)
            ->assertSee('data-user="Kasir Satu"', false);
    }
}
