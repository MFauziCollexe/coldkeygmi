<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->name();

        return [
            'name' => $name,
            'email' => $this->faker->unique()->safeEmail(),
            'account' => Str::upper($this->faker->unique()->bothify('ACC###??')),
            'password' => static::$password ??= Hash::make('password'),
            'status' => 'active',
            'department_id' => null,
            'position_id' => null,
            'is_admin' => false,
            'user_created' => 'factory',
            'user_updated' => 'factory',
        ];
    }

    /**
     * Indicate that the user is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the user is deactivated.
     */
    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'deactivated',
        ]);
    }

    /**
     * Indicate that the user is an administrator.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
            'status' => 'active',
        ]);
    }
}
