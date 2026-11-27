<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Harga menu boleh kosong (NULL) selama belum diisi.
     * NULL berarti "harga belum diisi" dan tidak boleh dianggap 0.
     */
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->unsignedInteger('price')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->unsignedInteger('price')->nullable(false)->change();
        });
    }
};
