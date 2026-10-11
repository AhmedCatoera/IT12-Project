<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stock in = one receipt (header) with many ingredient lines (1:M).
        Schema::create('stock_ins', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no', 50)->nullable();
            $table->string('supplier_name', 100)->nullable();
            $table->date('received_date');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_in_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_in_id')->constrained('stock_ins')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->date('expiration_date')->nullable();
            $table->timestamps();

            $table->unique(['stock_in_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_in_items');
        Schema::dropIfExists('stock_ins');
    }
};
