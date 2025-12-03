<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // WEDDING2024, FIRSTORDER, dll
            $table->enum('type', ['percentage', 'fixed']); // percentage = %, fixed = nominal
            $table->bigInteger('discount_value'); // 10 (untuk 10%) atau 500000 (untuk Rp 500.000)
            $table->bigInteger('min_purchase')->default(0); // Minimal pembelian untuk bisa pakai voucher
            $table->bigInteger('max_discount')->nullable(); // Max potongan (untuk percentage type)
            $table->integer('usage_limit')->nullable(); // Total usage limit (null = unlimited)
            $table->integer('usage_count')->default(0); // Sudah dipakai berapa kali
            $table->integer('usage_per_user')->default(1); // 1 user bisa pakai berapa kali
            $table->date('valid_from'); // Tanggal mulai berlaku
            $table->date('valid_until'); // Tanggal kadaluarsa
            $table->boolean('is_active')->default(true); // Admin bisa disable voucher
            $table->text('description')->nullable(); // Deskripsi voucher
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
