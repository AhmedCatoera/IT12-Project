<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No stored stock column: stock on hand = stock in - stock out + adjustments.
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('unit', 20);
            $table->decimal('reorder_level', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
