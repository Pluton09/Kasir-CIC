<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_utama_memerlukan_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_halaman_utama_mengarah_ke_kasir_setelah_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('kasir'));
    }
}
