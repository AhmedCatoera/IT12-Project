<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/** The price list. Staff can still change the price per order (custom cakes vary). */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['cake', 'Custom Birthday Cake (8-inch)', 1200],
            ['cake', '2-Tier Celebration Cake', 2500],
            ['cake', '3-Tier Wedding Cake', 6500],
            ['cake', 'Chocolate Moist Cake', 850],
            ['cake', 'Bento Cake', 350],
            ['pastry', 'Red Velvet Cupcakes (box of 12)', 600],
            ['pastry', 'Fudge Brownies (box of 12)', 450],
            ['pastry', 'Ensaymada (box of 6)', 300],
            ['pastry', 'Crinkles (box of 20)', 250],
            ['souvenir', 'Sintra Board Standee', 50],
            ['souvenir', 'Personalized Mug', 180],
            ['bread', 'Pandesal (bag of 10)', 50],
            ['bread', 'Spanish Bread (box of 6)', 90],
            ['bread', 'Cheese Bread (box of 6)', 95],
            ['bread', 'Pan de Coco (box of 6)', 85],
            ['souvenir', 'Refrigerator Magnet', 35],
            ['souvenir', 'Shot Glass', 60],
            ['souvenir', 'Keychain', 30],
        ];

        foreach ($products as [$category, $name, $price]) {
            Product::updateOrCreate(['name' => $name], [
                'category' => $category,
                'base_price' => $price,
                'is_active' => true,
            ]);
        }
    }
}
