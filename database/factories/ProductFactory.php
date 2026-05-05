<?php

namespace Database\Factories;

use App\Enums\StorefrontProductCategory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 999),
            'storefront_category' => fake()->randomElement(StorefrontProductCategory::cases()),
        ];
    }
}
