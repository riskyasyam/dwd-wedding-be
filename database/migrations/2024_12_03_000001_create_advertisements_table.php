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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image'); // Path to uploaded image
            $table->text('description')->nullable();
            $table->string('link_url')->nullable(); // Optional link jika diklik
            $table->integer('order')->default(0); // Untuk sorting urutan tampil
            $table->boolean('is_active')->default(true); // Enable/disable iklan
            $table->date('start_date')->nullable(); // Mulai tampil
            $table->date('end_date')->nullable(); // Berakhir tampil
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
