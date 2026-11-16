<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_menggunakan_database_kasir_testing(): void
    {
        $this->assertSame('kasir_testing', DB::connection()->getDatabaseName());
    }

    public function test_zona_waktu_aplikasi_wib(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
    }

    public function test_seeder_membuat_kategori_dan_menu_contoh(): void
    {
        $this->seed();

        $this->assertDatabaseCount('categories', 4);
        $this->assertDatabaseCount('menus', 19);
        $this->assertDatabaseHas('menus', [
            'name' => 'Teh Manis',
            'price' => 6000,
        ]);
        $this->assertDatabaseHas('menus', [
            'name' => 'Telor Ayam Kampung BJ',
            'price' => 8000,
        ]);
        $this->assertDatabaseHas('menus', [
            'name' => 'Toping Bakso',
            'price' => 5000,
        ]);
    }

    public function test_user_seeder_tidak_berjalan_di_environment_testing(): void
    {
        $this->seed();

        $this->assertDatabaseCount('users', 0);
    }
}
