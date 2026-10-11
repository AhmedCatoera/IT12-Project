<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Subtotal is computed (quantity x unit_price). unit_price is the price agreed
        // for this order, which can differ from the product's base price.
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            // A listed product, or a custom item typed by the Owner or Staff (custom_name + custom_category).
            $table->foreignId('product_id')->nullable()->constrained('products')->restrictOnDelete();
            $table->string('custom_name', 120)->nullable();
            $table->string('custom_category', 20)->nullable();
            // Cake details (only for cakes, listed or custom; null for pastries, breads and souvenirs).
            // The choices live in config/sweetnest.php.
            $table->string('cake_type', 20)->nullable();        // sponge, moist or chiffon (validated against the config list)
            $table->string('flavor', 100)->nullable();     // chiffon flavor, or the icing flavor for sponge and moist
            $table->string('size', 100)->nullable();
            $table->unsignedTinyInteger('tiers')->nullable();   // 1, 2 or 3 (config cake_tiers)
            $table->string('design_theme', 255)->nullable();
            $table->string('inscription', 255)->nullable();
            $table->string('reference_image', 255)->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
