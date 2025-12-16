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
        Schema::table('reviews', function (Blueprint $table) {
            // Make user_id nullable (already nullable from create, but ensure it)
            $table->foreignId('user_id')->nullable()->change();
            
            // Add customer_name field for fake reviews (only if not exists)
            if (!Schema::hasColumn('reviews', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.  
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Revert user_id to not nullable
            $table->foreignId('user_id')->nullable(false)->change();
            
            // Drop customer_name only if it was added by this migration
            if (Schema::hasColumn('reviews', 'customer_name')) {
                $table->dropColumn('customer_name');
            }
        });
    }
};
