<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Twice-daily physical counts. Variance is computed (counted_qty - system_qty).
        Schema::create('physical_counts', function (Blueprint $table) {
            $table->id();
            $table->date('count_date');
            $table->enum('shift', ['morning', 'night']);
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['count_date', 'shift']);
        });

        Schema::create('physical_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physical_count_id')->constrained('physical_counts')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete();
            $table->decimal('system_qty', 10, 2);  // snapshot at count time
            $table->decimal('counted_qty', 10, 2);
            $table->timestamps();

            $table->unique(['physical_count_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physical_count_items');
        Schema::dropIfExists('physical_counts');
    }
};
