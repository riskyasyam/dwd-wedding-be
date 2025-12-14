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
        // Add minimum_dp_percentage to decorations table
        Schema::table('decorations', function (Blueprint $table) {
            if (!Schema::hasColumn('decorations', 'minimum_dp_percentage')) {
                $table->integer('minimum_dp_percentage')->default(30)->after('is_deals')->comment('Minimum DP percentage (default 30%)');
            }
        });

        // Add DP-related fields to orders table
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payment_type')) {
                $table->enum('payment_type', ['full', 'dp'])->default('full')->after('total')->comment('Payment type: full payment or DP');
            }
            if (!Schema::hasColumn('orders', 'dp_amount')) {
                $table->bigInteger('dp_amount')->default(0)->after('payment_type')->comment('DP amount paid');
            }
            if (!Schema::hasColumn('orders', 'remaining_amount')) {
                $table->bigInteger('remaining_amount')->default(0)->after('dp_amount')->comment('Remaining amount to be paid');
            }
            if (!Schema::hasColumn('orders', 'dp_paid_at')) {
                $table->timestamp('dp_paid_at')->nullable()->after('remaining_amount')->comment('When DP was paid');
            }
            if (!Schema::hasColumn('orders', 'full_paid_at')) {
                $table->timestamp('full_paid_at')->nullable()->after('dp_paid_at')->comment('When fully paid');
            }
            if (!Schema::hasColumn('orders', 'snap_token')) {
                $table->string('snap_token')->nullable()->after('full_paid_at')->comment('Midtrans snap token for full payment');
            }
            if (!Schema::hasColumn('orders', 'dp_snap_token')) {
                $table->string('dp_snap_token')->nullable()->after('snap_token')->comment('Midtrans snap token for DP payment');
            }
            if (!Schema::hasColumn('orders', 'remaining_snap_token')) {
                $table->string('remaining_snap_token')->nullable()->after('dp_snap_token')->comment('Midtrans snap token for remaining payment');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('decorations', function (Blueprint $table) {
            if (Schema::hasColumn('decorations', 'minimum_dp_percentage')) {
                $table->dropColumn('minimum_dp_percentage');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $columns = [
                'payment_type',
                'dp_amount',
                'remaining_amount',
                'dp_paid_at',
                'full_paid_at',
                'snap_token',
                'dp_snap_token',
                'remaining_snap_token'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
