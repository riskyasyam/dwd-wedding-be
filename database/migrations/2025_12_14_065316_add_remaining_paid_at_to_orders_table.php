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
            if (!Schema::hasColumn('orders', 'remaining_paid_at')) {
                $table->timestamp('remaining_paid_at')->nullable()->after('full_paid_at')->comment('When remaining payment was paid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'remaining_paid_at')) {
                $table->dropColumn('remaining_paid_at');
            }
        });
    }
};
