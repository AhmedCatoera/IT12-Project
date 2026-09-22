<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Production;
use App\Models\ProductionIngredient;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staff = User::where('role', 'staff')->first();
        $baker = $staff; // No separate baker role — staff handles production

        // 1. Create Sample Customers
        $customer1 = Customer::firstOrCreate(
            ['contact_number' => '0917-123-4567'],
            [
                'first_name' => 'Maria',
                'middle_name' => 'Clara',
                'last_name' => 'Santos',
                'address' => 'Phase 1, Brgy. Sto. Niño, Tugbok, Davao City',
                'facebook_name' => 'Maria Santos-Davao',
            ]
        );

        $customer2 = Customer::firstOrCreate(
            ['contact_number' => '0920-987-6543'],
            [
                'first_name' => 'Juan',
                'middle_name' => null,
                'last_name' => 'Dela Cruz',
                'address' => 'Km. 7 McArthur Highway, Bangkal, Davao City',
                'facebook_name' => 'Juan DC',
            ]
        );

        $customer3 = Customer::firstOrCreate(
            ['contact_number' => '0998-555-8899'],
            [
                'first_name' => 'Katrina',
                'middle_name' => null,
                'last_name' => 'Alcantara',
                'address' => 'Crossing Bayabas, Toril, Davao City',
                'facebook_name' => 'Kat Alcantara',
            ]
        );

        // 2. Sample Order 1: Custom Birthday Cake (Status: In Production, 50% Downpayment Paid)
        $order1 = Order::firstOrCreate(
            ['order_number' => 'SN-2026-0001'],
            [
                'customer_id' => $customer1->id,
                'created_by' => $staff?->id,
                'order_date' => now()->toDateString(),
                'scheduled_date' => now()->addDays(2)->setTime(14, 0),
                'order_type' => 'delivery',
                'delivery_address' => 'Phase 1, Brgy. Sto. Niño, Tugbok, Davao City',
                'status' => 'in_production',
                'total_amount' => 1800.00,
                'down_payment_required' => 900.00,
                'total_paid' => 900.00,
                'payment_status' => 'partially_paid',
                'notes' => 'Please include 7 candles and a cake knife. Fragile delivery.',
            ]
        );

        OrderItem::firstOrCreate(
            ['order_id' => $order1->id, 'item_name' => '2-Tier Customized Birthday Cake'],
            [
                'item_type' => 'cake',
                'flavor' => 'Chocolate Moist with Salted Caramel',
                'size' => '8x4 inch base, 6x4 inch top',
                'design_theme' => 'Pastel Pink Floral Garden',
                'custom_names' => 'Sofia turns 7!',
                'reference_image' => null,
                'quantity' => 1,
                'unit_price' => 1800.00,
                'subtotal' => 1800.00,
            ]
        );

        Payment::firstOrCreate(
            ['order_id' => $order1->id, 'reference_number' => 'GCASH-98234110'],
            [
                'received_by' => $staff?->id,
                'payment_type' => 'down_payment',
                'amount' => 900.00,
                'payment_method' => 'gcash',
                'payment_date' => now(),
                'notes' => '50% down payment received via GCash to confirm booking.',
            ]
        );

        $prod1 = Production::firstOrCreate(
            ['order_id' => $order1->id],
            [
                'assigned_baker_id' => $baker?->id,
                'production_date' => now()->toDateString(),
                'status' => 'in_progress',
                'started_at' => now()->subHours(2),
                'notes' => 'Sponge layers baked, chilling before fondant sculpting.',
            ]
        );

        // Record ingredients used in production run
        $flour = Ingredient::where('name', 'All-Purpose Flour')->first();
        $sugar = Ingredient::where('name', 'Refined White Sugar')->first();
        $butter = Ingredient::where('name', 'Unsalted Butter')->first();
        $eggs = Ingredient::where('name', 'Fresh Farm Eggs')->first();
        $cocoa = Ingredient::where('name', 'Dark Cocoa Powder')->first();

        if ($flour) {
            ProductionIngredient::firstOrCreate(
                ['production_id' => $prod1->id, 'ingredient_id' => $flour->id],
                ['quantity_used' => 1.50]
            );
        }
        if ($sugar) {
            ProductionIngredient::firstOrCreate(
                ['production_id' => $prod1->id, 'ingredient_id' => $sugar->id],
                ['quantity_used' => 0.80]
            );
        }
        if ($butter) {
            ProductionIngredient::firstOrCreate(
                ['production_id' => $prod1->id, 'ingredient_id' => $butter->id],
                ['quantity_used' => 0.40]
            );
        }
        if ($eggs) {
            ProductionIngredient::firstOrCreate(
                ['production_id' => $prod1->id, 'ingredient_id' => $eggs->id],
                ['quantity_used' => 6.00]
            );
        }
        if ($cocoa) {
            ProductionIngredient::firstOrCreate(
                ['production_id' => $prod1->id, 'ingredient_id' => $cocoa->id],
                ['quantity_used' => 0.30]
            );
        }

        // 3. Sample Order 2: Pastries (Status: Ready for Release, Fully Paid, Pickup)
        $order2 = Order::firstOrCreate(
            ['order_number' => 'SN-2026-0002'],
            [
                'customer_id' => $customer2->id,
                'created_by' => $staff?->id,
                'order_date' => now()->toDateString(),
                'scheduled_date' => now()->addDay()->setTime(10, 30),
                'order_type' => 'pickup',
                'delivery_address' => null,
                'status' => 'ready_for_release',
                'total_amount' => 600.00,
                'down_payment_required' => 300.00,
                'total_paid' => 600.00,
                'payment_status' => 'fully_paid',
                'notes' => 'Customer will pick up at the kitchen counter.',
            ]
        );

        OrderItem::firstOrCreate(
            ['order_id' => $order2->id, 'item_name' => 'Red Velvet Cupcakes with Cream Cheese Frosting'],
            [
                'item_type' => 'pastry',
                'flavor' => 'Red Velvet',
                'size' => 'Standard Box of 12',
                'design_theme' => 'White frosting with red crumb dust',
                'custom_names' => null,
                'reference_image' => null,
                'quantity' => 12,
                'unit_price' => 50.00,
                'subtotal' => 600.00,
            ]
        );

        Payment::firstOrCreate(
            ['order_id' => $order2->id, 'reference_number' => 'CASH-REC-001'],
            [
                'received_by' => $staff?->id,
                'payment_type' => 'full_payment',
                'amount' => 600.00,
                'payment_method' => 'cash',
                'payment_date' => now(),
                'notes' => 'Full payment made in cash upon placing order.',
            ]
        );

        // 4. Sample Order 3: Personalized Souvenirs (Status: Confirmed, Delivery)
        $order3 = Order::firstOrCreate(
            ['order_number' => 'SN-2026-0003'],
            [
                'customer_id' => $customer3->id,
                'created_by' => $staff?->id,
                'order_date' => now()->toDateString(),
                'scheduled_date' => now()->addDays(5)->setTime(16, 0),
                'order_type' => 'delivery',
                'delivery_address' => 'Crossing Bayabas, Toril, Davao City',
                'status' => 'confirmed',
                'total_amount' => 1250.00,
                'down_payment_required' => 625.00,
                'total_paid' => 625.00,
                'payment_status' => 'partially_paid',
                'notes' => 'Individual plastic packaging for each keepsake.',
            ]
        );

        OrderItem::firstOrCreate(
            ['order_id' => $order3->id, 'item_name' => 'Personalized Sintra Board Standees'],
            [
                'item_type' => 'souvenir',
                'flavor' => null,
                'size' => '4x6 inch tabletop',
                'design_theme' => 'Blue & Gold Royal Christening',
                'custom_names' => 'Prince Liam Gabriel - Oct 2026',
                'reference_image' => null,
                'quantity' => 25,
                'unit_price' => 50.00,
                'subtotal' => 1250.00,
            ]
        );

        Payment::firstOrCreate(
            ['order_id' => $order3->id, 'reference_number' => 'GCASH-77182901'],
            [
                'received_by' => $staff?->id,
                'payment_type' => 'down_payment',
                'amount' => 625.00,
                'payment_method' => 'gcash',
                'payment_date' => now(),
                'notes' => '50% down payment received via GCash.',
            ]
        );
    }
}
