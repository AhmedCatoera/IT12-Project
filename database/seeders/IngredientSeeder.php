<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\StockAdjustment;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Ingredients have no stored quantity: their stock comes from these
 * stock-in receipts (and later stock outs and adjustments).
 */
class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('role', 'owner')->first();

        // name => [unit, reorder level, opening qty, unit cost]
        $ingredients = [
            'All-Purpose Flour' => ['kg', 15, 50, 65],
            'Refined White Sugar' => ['kg', 10, 30, 78],
            'Unsalted Butter' => ['kg', 5, 12, 280],
            'Eggs' => ['pcs', 30, 180, 8.5],
            'Fresh Milk' => ['L', 6, 15, 95],
            'Baking Powder' => ['kg', 1, 3, 120],
            'Cocoa Powder' => ['kg', 2, 8, 340],
            'Vanilla Extract' => ['mL', 250, 1000, 0.65],
            'Cream Cheese' => ['kg', 2, 5, 320],
            'Fondant' => ['kg', 3, 4, 210],
            'Sintra Board (A4)' => ['sheet', 25, 100, 45],
            'Cake Boxes (8-inch)' => ['pcs', 10, 12, 35],
        ];

        $opening = StockIn::create([
            'receipt_no' => 'OR-10231',
            'supplier_name' => 'Davao Baking Supplies',
            'received_date' => today()->subDays(10),
            'received_by' => $owner?->id,
            'remarks' => 'Opening stock when the system started.',
        ]);

        foreach ($ingredients as $name => [$unit, $reorder, $qty, $cost]) {
            $ingredient = Ingredient::updateOrCreate(['name' => $name], [
                'unit' => $unit,
                'reorder_level' => $reorder,
                'is_active' => true,
            ]);

            $opening->items()->create([
                'ingredient_id' => $ingredient->id,
                'quantity' => $qty,
                'unit_cost' => $cost,
                'expiration_date' => in_array($unit, ['sheet', 'pcs'], true) && $name !== 'Eggs' ? null : today()->addMonths($name === 'Eggs' ? 0 : 4)->addDays($name === 'Eggs' ? 18 : 0),
            ]);
        }

        // A second, smaller receipt three days ago.
        $restock = StockIn::create([
            'receipt_no' => 'OR-10388',
            'supplier_name' => 'Bankerohan Market',
            'received_date' => today()->subDays(3),
            'received_by' => $owner?->id,
            'remarks' => null,
        ]);
        $restock->items()->create(['ingredient_id' => Ingredient::where('name', 'Eggs')->value('id'), 'quantity' => 60, 'unit_cost' => 8.5, 'expiration_date' => today()->addDays(21)]);
        $restock->items()->create(['ingredient_id' => Ingredient::where('name', 'Unsalted Butter')->value('id'), 'quantity' => 5, 'unit_cost' => 285, 'expiration_date' => today()->addMonths(3)]);

        // Spoilage recorded by the Owner (signed quantity: negative removes stock).
        $spoilage = StockAdjustment::create([
            'reason' => 'spoilage',
            'adjusted_date' => today()->subDays(2),
            'adjusted_by' => $owner?->id,
            'remarks' => 'One liter of milk was left out overnight and spoiled.',
        ]);
        $spoilage->items()->create(['ingredient_id' => Ingredient::where('name', 'Fresh Milk')->value('id'), 'quantity' => -1]);
    }
}
