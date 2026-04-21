<?php

namespace Database\Factories;

use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DirectMessageFactory extends Factory
{
    protected $model = DirectMessage::class;

    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'recipient_id' => User::factory(),
            'content' => $this->faker->paragraph(),
            'type' => 'text',
            'file_path' => null,
            'file_name' => null,
            'mime_type' => null,
            'read_at' => null,
        ];
    }

    public function read()
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    public function unread()
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    public function withFile()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'file',
            'file_path' => 'direct-messages/file.pdf',
            'file_name' => 'documento.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }

    public function withImage()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'image',
            'file_path' => 'direct-messages/image.png',
            'file_name' => 'imagem.png',
            'mime_type' => 'image/png',
        ]);
    }
}
