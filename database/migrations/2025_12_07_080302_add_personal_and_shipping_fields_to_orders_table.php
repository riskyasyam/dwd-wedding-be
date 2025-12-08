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
        Schema::table('orders', function (Blueprint $table) {
            // Personal Details
            $table->string('first_name')->after('user_id');
            $table->string('last_name')->after('first_name');
            $table->string('email')->after('last_name');
            $table->string('phone')->after('email');
            
            // Shipping Address
            $table->text('address')->after('phone');
            $table->string('city')->after('address');
            $table->string('district')->after('city'); // Kelurahan
            $table->string('sub_district')->after('district'); // Kecamatan
            $table->string('postal_code')->after('sub_district');
            
            // Notes (optional)
            $table->text('notes')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'email',
                'phone',
                'address',
                'city',
                'district',
                'sub_district',
                'postal_code',
                'notes',
            ]);
        });
    }
};
