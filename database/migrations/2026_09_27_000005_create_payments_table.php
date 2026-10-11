<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('payment_type', ['down_payment', 'balance_payment', 'full_payment']);
            $table->enum('payment_method', ['cash', 'gcash'])->default('cash');
            $table->decimal('amount', 10, 2);
            $table->string('reference_no', 100)->nullable();   // GCash reference number (a GCash payment needs this or a screenshot)
            $table->string('proof_image', 255)->nullable();    // GCash screenshot, stored in storage/app/public/payment-proofs
            $table->dateTime('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
