<?php

namespace Modules\Manufacturing\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ManufacturingDummySeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        $business_id = DB::table('business')->pluck('id')->first();
        $variation_id = DB::table('variations')->join('products', 'products.id', '=', 'variations.product_id')->where('products.business_id', $business_id)->pluck('variations.id')->first();

        if (!$business_id || !$variation_id) {
            return;
        }

        // Recipes
        for ($i = 0; $i < 10; $i++) {
            $recipe_id = DB::table('mfg_recipes')->insertGetId([
                'variation_id' => $variation_id,
                'business_id' => $business_id,
                'ingredients_cost' => $faker->randomFloat(2, 50, 500),
                'extra_cost' => $faker->randomFloat(2, 10, 50),
                'total_quantity' => 1,
                'instructions' => $faker->paragraph,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Ingredients (assuming another variation exists or using the same for dummy purposes)
            DB::table('mfg_recipe_ingredients')->insert([
                'mfg_recipe_id' => $recipe_id,
                'variation_id' => $variation_id,
                'quantity' => $faker->randomFloat(2, 1, 10),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
