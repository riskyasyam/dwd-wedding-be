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
        Schema::table('order_items', function (Blueprint $table) {
            $table->bigInteger('base_price')->after('quantity'); // Harga asli decoration
            $table->bigInteger('discount')->default(0)->after('base_price'); // Diskon dari decoration
            // price tetap ada sebagai harga final (base_price - discount)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['base_price', 'discount']);
        });
    }
};
