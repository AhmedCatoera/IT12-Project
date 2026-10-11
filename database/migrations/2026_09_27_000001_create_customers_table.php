<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 50);
            $table->string('middle_name', 50)->nullable();
            $table->string('last_name', 50);
            $table->string('contact_number', 15)->unique(); // digits only, e.g. 09171234567
            // Saved address, split into parts (all optional; a delivery can also use its own address)
            $table->string('street', 150)->nullable();        // house or unit no. and street
            $table->string('address_line2', 150)->nullable(); // subdivision, building or landmark
            $table->string('barangay', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->char('postal_code', 4)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();   // map pin, Davao area
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('facebook_name', 100)->nullable();
            $table->boolean('is_active')->default(true); // deactivate instead of delete
            $table->timestamps();

            $table->index(['is_active', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
