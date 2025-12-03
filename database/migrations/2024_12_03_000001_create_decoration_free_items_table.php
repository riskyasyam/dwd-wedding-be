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
        Schema::create('decoration_free_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decoration_id')->constrained('decorations')->onDelete('cascade');
            $table->string('item_name'); // Cinematic, Foto & Video, Makeup, dll
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1); // Jumlah item yang didapat
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decoration_free_items');
    }
};
