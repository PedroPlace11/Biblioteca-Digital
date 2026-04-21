<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->paragraph(),
            'avatar' => null,
            'creator_id' => User::factory(),
            'is_archived' => false,
        ];
    }

    public function archived()
    {
        return $this->state(fn (array $attributes) => [
            'is_archived' => true,
        ]);
    }
}
