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
        // Alter status enum to add 'dp_paid' and 'processing'
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'dp_paid', 'paid', 'processing', 'failed', 'completed', 'cancelled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'paid', 'failed', 'completed', 'cancelled') DEFAULT 'pending'");
    }
};
