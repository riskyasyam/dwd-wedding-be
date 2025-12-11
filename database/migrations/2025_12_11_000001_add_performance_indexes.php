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
        // Add indexes to improve query performance
        
        // Decorations table indexes
        Schema::table('decorations', function (Blueprint $table) {
            $table->index('slug');
            $table->index('region');
            $table->index('is_deals');
            $table->index(['discount_start_date', 'discount_end_date']);
            $table->index('created_at');
        });

        // Orders table indexes
        Schema::table('orders', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('order_number');
            $table->index('status');
            $table->index('created_at');
        });

        // Order items table indexes
        Schema::table('order_items', function (Blueprint $table) {
            $table->index('order_id');
            $table->index('decoration_id');
        });

        // Reviews table indexes
        Schema::table('reviews', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('decoration_id');
            $table->index('rating');
            $table->index('posted_at');
        });

        // Inspirations table indexes
        Schema::table('inspirations', function (Blueprint $table) {
            $table->index('location');
            $table->index('liked_count');
            $table->index('created_at');
        });

        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
            $table->index('email');
        });

        // Vouchers table indexes
        Schema::table('vouchers', function (Blueprint $table) {
            $table->index('code');
            $table->index(['valid_from', 'valid_until']);
            $table->index('is_active');
        });

        // Events table indexes
        Schema::table('events', function (Blueprint $table) {
            $table->index('slug');
            $table->index('created_at');
        });

        // Vendors table indexes
        Schema::table('vendors', function (Blueprint $table) {
            $table->index('slug');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Decorations
        Schema::table('decorations', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['region']);
            $table->dropIndex(['is_deals']);
            $table->dropIndex(['discount_start_date', 'discount_end_date']);
            $table->dropIndex(['created_at']);
        });

        // Orders
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['order_number']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        // Order items
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['decoration_id']);
        });

        // Reviews
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['decoration_id']);
            $table->dropIndex(['rating']);
            $table->dropIndex(['posted_at']);
        });

        // Inspirations
        Schema::table('inspirations', function (Blueprint $table) {
            $table->dropIndex(['location']);
            $table->dropIndex(['liked_count']);
            $table->dropIndex(['created_at']);
        });

        // Users
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['email']);
        });

        // Vouchers
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->dropIndex(['valid_from', 'valid_until']);
            $table->dropIndex(['is_active']);
        });

        // Events
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['created_at']);
        });

        // Vendors
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['created_at']);
        });
    }
};
