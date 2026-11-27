<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            // Snapshot tenant; sengaja tanpa foreign key ketat agar riwayat
            // lama tetap utuh meskipun data tenant berubah.
            $table->unsignedBigInteger('tenant_id')->nullable()->after('transaction_id');
            $table->string('tenant_name', 100)->nullable()->after('tenant_id');

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn(['tenant_id', 'tenant_name']);
        });
    }
};
