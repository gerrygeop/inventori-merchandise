<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
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
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => str()->slug($name),
            'sku' => fake()->ean8(),
            'description' => fake()->paragraph(),
            'qty' => fake()->numberBetween(0, 100),
            'security_stock' => 5,
            'price' => fake()->numberBetween(10000, 100000),
            'photo_url' => fake()->imageUrl(),
        ];
    }
}
