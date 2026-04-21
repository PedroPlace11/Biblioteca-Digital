<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'user_id' => User::factory(),
            'content' => $this->faker->paragraph(),
            'type' => 'text',
            'file_path' => null,
            'file_name' => null,
            'mime_type' => null,
        ];
    }

    public function withFile()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'file',
            'file_path' => 'messages/file.pdf',
            'file_name' => 'documento.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }

    public function withImage()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'image',
            'file_path' => 'messages/image.png',
            'file_name' => 'imagem.png',
            'mime_type' => 'image/png',
        ]);
    }
}
