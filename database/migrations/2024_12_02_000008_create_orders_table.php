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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->bigInteger('subtotal');
            $table->string('voucher_code')->nullable(); // Kode voucher yang digunakan
            $table->bigInteger('voucher_discount')->default(0); // Nominal diskon dari voucher
            $table->bigInteger('discount')->default(0); // Diskon lain (dari decoration)
            $table->bigInteger('delivery_fee')->default(0);
            $table->bigInteger('total');
            $table->enum('status', ['pending', 'paid', 'failed', 'completed', 'cancelled'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
