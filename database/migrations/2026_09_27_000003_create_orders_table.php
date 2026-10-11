<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Totals, amount paid, down payment and payment status are NOT stored:
        // they are computed from order_items and payments (see Order model).
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique(); // SN-YYYY-<order id>
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('order_date');
            $table->dateTime('scheduled_at');
            $table->enum('order_type', ['pickup', 'delivery'])->default('pickup');
            // Regular (due 2 or more days after the order date) or rush (due the next day or the
            // same day). A rush order adds a fee (percent of the items total) and is paid in full.
            $table->string('order_kind', 10)->default('regular');
            $table->decimal('rush_fee_percent', 5, 2)->nullable();
            // What the delivery app (Maxim) quotes for the trip, typed by the Owner or Staff. Null for pickups.
            $table->decimal('delivery_fee', 10, 2)->nullable();
            // Set when the order is cancelled: the share of what was paid that goes back to the customer.
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->dateTime('refunded_at')->nullable();
            // Where the order goes: the delivery address (a copy, so it stays the same if the
            // customer moves later) or, for pickups, the spot the customer collects it at
            // (the shop by default, or a meet-up place they ask for).
            $table->string('delivery_street', 150)->nullable();
            $table->string('delivery_address_line2', 150)->nullable();
            $table->string('delivery_barangay', 100)->nullable();
            $table->string('delivery_city', 100)->nullable();
            $table->string('delivery_province', 100)->nullable();
            $table->char('delivery_postal_code', 4)->nullable();
            $table->decimal('delivery_latitude', 10, 7)->nullable();
            $table->decimal('delivery_longitude', 10, 7)->nullable();
            $table->enum('status', [
                'pending',            // waiting for the payment (50% down on regular orders, full on rush)
                'confirmed',          // down payment received
                'in_production',      // set only by its job order
                'ready_for_release',  // set only by its job order
                'out_for_delivery',
                'completed',
                'cancelled',
            ])->default('pending');
            $table->string('cancel_reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
