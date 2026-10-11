<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Owner-only corrections: spoilage, expired, damaged, count corrections, fixes.
        // Line quantities are signed: positive adds stock, negative removes it.
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physical_count_id')->nullable()->unique()->constrained('physical_counts')->nullOnDelete();
            $table->enum('reason', ['spoilage', 'expired', 'damaged', 'count_correction', 'entry_correction', 'other']);
            $table->date('adjusted_date');
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks');
            $table->timestamps();
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->timestamps();

            $table->unique(['stock_adjustment_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
    }
};
