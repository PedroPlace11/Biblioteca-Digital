<?php

namespace Database\Factories;

use App\Models\RoomInvitation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomInvitationFactory extends Factory
{
    protected $model = RoomInvitation::class;

    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'invited_user_id' => User::factory(),
            'invited_by_id' => User::factory(),
            'status' => 'pending',
        ];
    }

    public function accepted()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
        ]);
    }

    public function declined()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'declined',
        ]);
    }

    public function pending()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
