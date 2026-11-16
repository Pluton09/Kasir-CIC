<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('table_no', 20)->nullable();
            $table->string('customer_name', 100)->nullable();
            $table->string('note', 255)->nullable();
            $table->string('payment_method', 20);
            $table->unsignedInteger('total');
            $table->unsignedInteger('paid_amount');
            $table->unsignedInteger('change_amount')->default(0);
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
