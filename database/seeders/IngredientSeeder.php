<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = User::where('role', 'owner')->first();
        $ownerId = $owner ? $owner->id : null;

        $items = [
            [
                'name' => 'All-Purpose Flour',
                'unit' => 'kg',
                'current_stock' => 50.00,
                'reorder_level' => 15.00,
                'unit_cost' => 65.00,
                'expiration_date' => now()->addMonths(6)->toDateString(),
            ],
            [
                'name' => 'Refined White Sugar',
                'unit' => 'kg',
                'current_stock' => 30.00,
                'reorder_level' => 10.00,
                'unit_cost' => 78.00,
                'expiration_date' => now()->addMonths(12)->toDateString(),
            ],
            [
                'name' => 'Unsalted Butter',
                'unit' => 'kg',
                'current_stock' => 12.00,
                'reorder_level' => 5.00,
                'unit_cost' => 280.00,
                'expiration_date' => now()->addMonths(2)->toDateString(),
            ],
            [
                'name' => 'Fresh Farm Eggs',
                'unit' => 'pcs',
                'current_stock' => 120.00,
                'reorder_level' => 30.00,
                'unit_cost' => 8.50,
                'expiration_date' => now()->addDays(21)->toDateString(),
            ],
            [
                'name' => 'Fresh Whole Milk',
                'unit' => 'liters',
                'current_stock' => 15.00,
                'reorder_level' => 6.00,
                'unit_cost' => 95.00,
                'expiration_date' => now()->addMonths(3)->toDateString(),
            ],
            [
                'name' => 'Baking Powder',
                'unit' => 'kg',
                'current_stock' => 5.00,
                'reorder_level' => 2.00,
                'unit_cost' => 120.00,
                'expiration_date' => now()->addMonths(10)->toDateString(),
            ],
            [
                'name' => 'Dark Cocoa Powder',
                'unit' => 'kg',
                'current_stock' => 8.00,
                'reorder_level' => 3.00,
                'unit_cost' => 340.00,
                'expiration_date' => now()->addMonths(8)->toDateString(),
            ],
            [
                'name' => 'Pure Vanilla Extract',
                'unit' => 'ml',
                'current_stock' => 1000.00,
                'reorder_level' => 250.00,
                'unit_cost' => 0.65,
                'expiration_date' => now()->addMonths(18)->toDateString(),
            ],
            [
                'name' => 'Sintra Board Blank Sheets (A4)',
                'unit' => 'pcs',
                'current_stock' => 100.00,
                'reorder_level' => 25.00,
                'unit_cost' => 45.00,
                'expiration_date' => null,
            ],
            [
                'name' => 'Cake Packaging Boxes (8-inch)',
                'unit' => 'pcs',
                'current_stock' => 4.00, // Low stock example to demonstrate alerts!
                'reorder_level' => 10.00,
                'unit_cost' => 35.00,
                'expiration_date' => null,
            ],
        ];

        foreach ($items as $data) {
            $ingredient = Ingredient::updateOrCreate(
                ['name' => $data['name']],
                [
                    'unit' => $data['unit'],
                    'current_stock' => $data['current_stock'],
                    'reorder_level' => $data['reorder_level'],
                    'is_active' => true,
                ]
            );

            // Log initial stock-in purchase transaction
            InventoryTransaction::firstOrCreate(
                [
                    'ingredient_id' => $ingredient->id,
                    'reference' => 'INITIAL-STOCK',
                ],
                [
                    'created_by' => $ownerId,
                    'transaction_type' => 'stock_in',
                    'quantity' => $data['current_stock'],
                    'unit_cost' => $data['unit_cost'],
                    'total_cost' => $data['current_stock'] * $data['unit_cost'],
                    'expiration_date' => $data['expiration_date'],
                    'remarks' => 'Initial stock intake recorded upon system setup',
                ]
            );
        }
    }
}
