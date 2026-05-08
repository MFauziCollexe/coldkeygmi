<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Position>
 */
class PositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->jobTitle(),
            'code' => Str::upper($this->faker->unique()->lexify('POS??')),
            'department_id' => Department::factory(),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'is_manager' => false,
        ];
    }

    /**
     * Indicate that the position is managerial.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_manager' => true,
        ]);
    }
}
