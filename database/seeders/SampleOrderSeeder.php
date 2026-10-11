<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\JobOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PhysicalCount;
use App\Models\Product;
use App\Models\User;
use App\Support\Address;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/** Sample customers and orders in every stage, dated around today. */
class SampleOrderSeeder extends Seeder
{
    private ?User $staff;

    public function run(): void
    {
        $this->staff = User::where('role', 'staff')->first();

        // Addresses around Davao City, with map pins (latitude, longitude).
        $c = [];
        foreach ([
            ['Maria', 'Clara', 'Santos', '09171234567', 'Maria Santos',
                ['Blk 7 Lot 3, Narra St.', 'Phase 1', 'Santo Niño', '8022', 7.0846, 125.5093]],
            ['Juan', null, 'Dela Cruz', '09209876543', 'Juan DC',
                ['Km. 7 McArthur Highway', null, 'Bangkal', '8000', 7.0476, 125.5667]],
            ['Katrina', null, 'Alcantara', '09985558899', 'Kat Alcantara',
                ['Purok 3, Bayabas Rd.', 'Crossing Bayabas', 'Bayabas', '8025', 7.0302, 125.5233]],
            ['Paolo', null, 'Villanueva', '09175550123', null,
                ['Blk 2 Lot 15, Dahlia St.', null, 'Matina Aplaya', '8000', 7.0468, 125.5874]],
            ['Grace', 'Lim', 'Uy', '09189990001', 'Grace Uy',
                ['Door 3, J.P. Laurel Ave.', 'Near SM Lanang Premier', 'Pampanga', '8000', 7.1005, 125.6300]],
            ['Ramon', null, 'Cabrera', '09223334444', null,
                ['Purok 7', null, 'Buhangin', '8000', null, null]],
        ] as [$first, $middle, $last, $phone, $fb, [$street, $line2, $barangay, $zip, $lat, $lng]]) {
            $c[$first] = Customer::create([
                'first_name' => $first, 'middle_name' => $middle, 'last_name' => $last,
                'contact_number' => $phone, 'facebook_name' => $fb, 'is_active' => true,
                'street' => $street, 'address_line2' => $line2, 'barangay' => $barangay,
                'city' => Address::DEFAULT_CITY, 'province' => Address::DEFAULT_PROVINCE,
                'postal_code' => $zip, 'latitude' => $lat, 'longitude' => $lng,
            ]);
        }
        $c['Ramon']->update(['is_active' => false]); // deactivated example

        $p = fn (string $name) => Product::where('name', $name)->firstOrFail();
        $today = today();

        // 1. Due today, being baked now (ingredients get logged once the job order is done).
        //    Delivery with an ₱80 fee from the delivery app: total 2,580, down payment 1,290.
        $o1 = $this->order($c['Maria'], $today->copy()->subDays(4), $today->copy()->setTime(16, 0), 'delivery', 'confirmed',
            'Please include 7 candles and a cake knife.', [
                [$p('2-Tier Celebration Cake'), 1, 2500, 'moist', 'Chocolate', '8x8', 'Pastel pink floral garden, two tiers', 'Sofia turns 7!', 2],
            ], 80);
        $this->pay($o1, 'down_payment', 1290, 'gcash', 'GC-98234110', $today->copy()->subDays(4)->setTime(11, 20));
        $j1 = $this->job($o1, $today->copy()->subDay(), 'in_progress', $today->copy()->setTime(8, 30), null);

        // 2. Due tomorrow, already baked.
        $o2 = $this->order($c['Juan'], $today->copy()->subDays(3), $today->copy()->addDay()->setTime(10, 30), 'pickup', 'confirmed',
            null, [
                [$p('Red Velvet Cupcakes (box of 12)'), 1, 600, null, null, null, null, null],
            ]);
        $this->pay($o2, 'full_payment', 600, 'cash', null, $today->copy()->subDays(3)->setTime(14, 5));
        $j2 = $this->job($o2, $today->copy()->subDay(), 'completed', $today->copy()->subDay()->setTime(9, 0), $today->copy()->subDay()->setTime(13, 40));
        $this->used($j2, $today->copy()->subDay(), ['All-Purpose Flour' => 0.5, 'Refined White Sugar' => 0.4, 'Unsalted Butter' => 0.25, 'Eggs' => 4, 'Cream Cheese' => 0.5, 'Cake Boxes (8-inch)' => 1]);

        // 3. Down payment in, waiting for a job order. Delivery fee ₱70: total 1,320, down payment 660.
        $o3 = $this->order($c['Katrina'], $today->copy()->subDays(2), $today->copy()->addDays(5)->setTime(9, 0), 'delivery', 'confirmed',
            'Individual plastic packaging for each keepsake.', [
                [$p('Sintra Board Standee'), 25, 50, null, null, null, null, null],
            ], 70);
        $this->pay($o3, 'down_payment', 660, 'gcash', 'GC-77182901', $today->copy()->subDays(2)->setTime(16, 45));

        // 4. New rush order (due tomorrow, so 40% fee and full payment needed) still unpaid,
        //    with one item typed in that is not on the price list. Picked up at a meet-up spot.
        $o4 = $this->order($c['Paolo'], $today, $today->copy()->addDay()->setTime(15, 0), 'pickup', 'pending',
            null, [
                [$p('Custom Birthday Cake (8-inch)'), 1, 1300, 'chiffon', 'Ube', '8x8', 'Superhero theme, red and blue', 'Happy 5th Birthday Migo'],
                [$p('Personalized Mug'), 2, 180, null, null, null, null, null],
                [['custom' => 'Number 5 cake topper (fondant)', 'category' => 'souvenir'], 1, 250, null, null, null, null, null],
            ]);
        $o4->update(Address::withPrefix([
            'street' => 'SM City Davao, Quimpo Blvd.', 'address_line2' => 'Main entrance', 'barangay' => 'Matina',
            'city' => Address::DEFAULT_CITY, 'province' => Address::DEFAULT_PROVINCE, 'postal_code' => '8000',
            'latitude' => 7.0512, 'longitude' => 125.5947,
        ], 'delivery_'));

        // 5. Completed and fully paid last week.
        $o5 = $this->order($c['Grace'], $today->copy()->subDays(8), $today->copy()->subDays(3)->setTime(11, 0), 'pickup', 'confirmed',
            null, [
                [$p('Fudge Brownies (box of 12)'), 2, 450, null, null, null, null, null],
            ]);
        $this->pay($o5, 'down_payment', 450, 'cash', null, $today->copy()->subDays(8)->setTime(10, 0));
        $j5 = $this->job($o5, $today->copy()->subDays(4), 'completed', $today->copy()->subDays(4)->setTime(8, 0), $today->copy()->subDays(4)->setTime(12, 0));
        $this->used($j5, $today->copy()->subDays(4), ['All-Purpose Flour' => 0.6, 'Refined White Sugar' => 0.7, 'Unsalted Butter' => 0.5, 'Eggs' => 8, 'Cocoa Powder' => 0.4]);
        $this->pay($o5, 'balance_payment', 450, 'cash', null, $today->copy()->subDays(3)->setTime(11, 10));
        $o5->update(['status' => 'completed']);

        // 6. Cancelled after paying in full: half the total (₱350) went back to the customer.
        $o6 = $this->order($c['Maria'], $today->copy()->subDays(6), $today->copy()->subDay()->setTime(13, 0), 'pickup', 'cancelled',
            null, [[$p('Bento Cake'), 2, 350, 'sponge', 'Vanilla', '3x2 (bento cake)', 'Minimalist', 'Happy Anniversary']]);
        $this->pay($o6, 'full_payment', 700, 'cash', null, $today->copy()->subDays(6)->setTime(15, 30));
        $o6->update(['cancel_reason' => 'Customer moved the celebration to next month.', 'refund_amount' => 350, 'refunded_at' => $today->copy()->subDays(5)->setTime(10, 0)]);

        // 7. Big order later this month: job order scheduled, not started. Delivery fee ₱150: total 6,650, down payment 3,325.
        $o7 = $this->order($c['Juan'], $today->copy()->subDay(), $today->copy()->addDays(20)->setTime(14, 0), 'delivery', 'confirmed',
            'Wedding reception venue. Deliver by 12 noon.', [
                [$p('3-Tier Wedding Cake'), 1, 6500, 'chiffon', 'Mocha', '8x8', 'White and gold, fresh flowers, three tiers', 'Juan and Bea', 3],
            ], 150);
        // Delivered to the reception venue, not the customer's home.
        $o7->update(Address::withPrefix([
            'street' => 'J.P. Laurel Ave.', 'address_line2' => 'Function hall, 3rd floor', 'barangay' => 'Bajada',
            'city' => Address::DEFAULT_CITY, 'province' => Address::DEFAULT_PROVINCE, 'postal_code' => '8000',
            'latitude' => 7.0905, 'longitude' => 125.6130,
        ], 'delivery_'));
        $this->pay($o7, 'down_payment', 3325, 'gcash', '1009231177', $today->copy()->subDay()->setTime(17, 30));
        $this->job($o7, $today->copy()->addDays(18), 'pending', null, null);

        // 8. Out for delivery today, paid in full with the ₱60 delivery fee.
        $o8 = $this->order($c['Grace'], $today->copy()->subDays(2), $today->copy()->setTime(18, 0), 'delivery', 'confirmed',
            null, [[$p('Crinkles (box of 20)'), 3, 250, null, null, null, null, null]], 60);
        $this->pay($o8, 'full_payment', 810, 'gcash', 'GC-11029384', $today->copy()->subDays(2)->setTime(9, 15));
        $j8 = $this->job($o8, $today, 'completed', $today->copy()->setTime(7, 0), $today->copy()->setTime(10, 30));
        $this->used($j8, $today, ['All-Purpose Flour' => 0.9, 'Refined White Sugar' => 0.6, 'Cocoa Powder' => 0.3, 'Eggs' => 6]);
        $o8->update(['status' => 'out_for_delivery']);

        // Last night's physical count by Staff: eggs came up 2 short (not yet applied by the Owner).
        $count = PhysicalCount::create([
            'count_date' => $today->copy()->subDay(),
            'shift' => 'night',
            'counted_by' => $this->staff?->id,
            'notes' => 'Two eggs were cracked in the tray.',
        ]);
        foreach (Ingredient::active()->orderBy('name')->get() as $ingredient) {
            $system = Ingredient::currentStock($ingredient->id);
            $count->items()->create([
                'ingredient_id' => $ingredient->id,
                'system_qty' => $system,
                'counted_qty' => $ingredient->name === 'Eggs' ? $system - 2 : $system,
            ]);
        }
    }

    private function order(Customer $customer, Carbon $orderDate, Carbon $due, string $type, string $status, ?string $notes, array $items, ?float $deliveryFee = null): Order
    {
        // Regular or rush comes from the dates; deliveries go to the customer's saved address
        // and pickups start at the shop (order 4 changes its spot afterwards).
        $kind = Order::kindFor($due, $orderDate);
        $order = Order::create([
            'order_number' => 'TMP-' . uniqid(),
            'customer_id' => $customer->id,
            'created_by' => $this->staff?->id,
            'order_date' => $orderDate->toDateString(),
            'scheduled_at' => $due,
            'order_type' => $type,
            'order_kind' => $kind,
            'rush_fee_percent' => $kind === 'rush' ? config('sweetnest.rush_fee_default') : null,
            'delivery_fee' => $type === 'delivery' ? $deliveryFee : null,
            ...Address::withPrefix($type === 'delivery' ? $customer->address_parts : Order::shopAddress(), 'delivery_'),
            'status' => $status,
            'notes' => $notes,
        ]);
        $order->update(['order_number' => sprintf('SN-%s-%04d', $orderDate->format('Y'), $order->id)]);

        foreach ($items as $row) {
            [$product, $qty, $price, $cakeType, $flavor, $size, $theme, $inscription] = $row;
            // A listed product, or ['custom' => name, 'category' => key] for an item typed in.
            $custom = is_array($product);
            $category = $custom ? $product['category'] : $product->category;

            // Only cakes carry details; cleanSpecs() drops them for pastries, breads and souvenirs.
            $order->items()->create(OrderItem::cleanSpecs([
                'cake_type' => $cakeType, 'flavor' => $flavor, 'size' => $size, 'tiers' => $row[8] ?? 1, 'design_theme' => $theme, 'inscription' => $inscription,
            ], $category) + [
                'product_id' => $custom ? null : $product->id,
                'custom_name' => $custom ? $product['custom'] : null,
                'custom_category' => $custom ? $category : null,
                'quantity' => $qty,
                'unit_price' => $price,
            ]);
        }

        return $order;
    }

    private function pay(Order $order, string $type, float $amount, string $method, ?string $ref, Carbon $at): void
    {
        $order->payments()->create([
            'received_by' => $this->staff?->id,
            'payment_type' => $type,
            'payment_method' => $method,
            'amount' => $amount,
            'reference_no' => $ref,
            'paid_at' => $at,
        ]);
    }

    private function job(Order $order, Carbon $date, string $status, ?Carbon $started, ?Carbon $completed): JobOrder
    {
        $job = JobOrder::create([
            'order_id' => $order->id,
            'assigned_to' => $this->staff?->id,
            'production_date' => $date->toDateString(),
            'status' => $status,
            'started_at' => $started,
            'completed_at' => $completed,
        ]);

        $orderStatus = ['in_progress' => 'in_production', 'completed' => 'ready_for_release'][$status] ?? null;
        if ($orderStatus) {
            $order->update(['status' => $orderStatus]);
        }

        return $job;
    }

    private function used(JobOrder $job, Carbon $date, array $lines): void
    {
        $stockOut = $job->stockOuts()->create([
            'out_date' => $date->toDateString(),
            'recorded_by' => $this->staff?->id,
        ]);

        foreach ($lines as $name => $qty) {
            $stockOut->items()->create([
                'ingredient_id' => Ingredient::where('name', $name)->value('id'),
                'quantity' => $qty,
            ]);
        }
    }
}
