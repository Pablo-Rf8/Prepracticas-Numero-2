<?php

namespace Database\Factories;

use App\Models\Product;
use App\DemoCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->bothify('TEST-????-########'),
            ...DemoCatalog::product(fake()->numberBetween(1, 1000)),
            'price_cents' => fake()->numberBetween(100, 100000),
            'active' => true,
        ];
    }
}
