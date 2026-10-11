<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stock out = ingredients used for a job order, one slip with many lines (1:M).
        Schema::create('stock_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained('job_orders')->restrictOnDelete();
            $table->date('out_date');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_out_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_out_id')->constrained('stock_outs')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->timestamps();

            $table->unique(['stock_out_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_out_items');
        Schema::dropIfExists('stock_outs');
    }
};
