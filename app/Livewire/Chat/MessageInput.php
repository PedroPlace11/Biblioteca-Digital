<?php

namespace App\Livewire\Chat;

use App\Models\Room;
use App\Models\Message;
use App\Services\RoomMessageNotificationService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class MessageInput extends Component
{
    use WithFileUploads;

    public Room $room;
    public $content = '';
    public $file = null;
    public $isTyping = false;
    public $searchOpen = false;
    public $searchQuery = '';

    protected $rules = [
        'content' => 'nullable|string|max:5000',
        'file' => 'nullable|file|max:10240',
    ];

    public function sendMessage()
    {
        // Autoriza
        $this->authorize('view', $this->room);

        // Validação
        if (!$this->content && !$this->file) {
            return;
        }

        try {
            $messageData = [
                'user_id' => Auth::id(),
                'type' => 'text',
            ];

            // Se houver arquivo
            if ($this->file) {
                $mimeType = $this->file->getMimeType();

                if (str_starts_with($mimeType, 'image/')) {
                    $messageData['type'] = 'image';
                } else {
                    $messageData['type'] = 'file';
                }

                $path = $this->file->store('messages', 'public');
                $messageData['file_path'] = $path;
                $messageData['file_name'] = $this->file->getClientOriginalName();
                $messageData['mime_type'] = $mimeType;

                if (!$this->content) {
                    $messageData['content'] = $this->file->getClientOriginalName();
                } else {
                    $messageData['content'] = $this->content;
                }
            } else {
                $messageData['content'] = $this->content;
            }

            $message = $this->room->messages()->create($messageData);

            app(RoomMessageNotificationService::class)->notifyRoomMembers($message);

            // Reset
            $this->content = '';
            $this->file = null;
            $this->isTyping = false;

            // Dispara evento para atualizar lista
            $this->dispatch('message-sent');

            // Broadcast para outros utilizadores (se Reverb configurado)
            // broadcast(new \App\Events\MessageSent($message));

        } catch (\Exception $e) {
            $this->addError('content', 'Erro ao enviar mensagem: ' . $e->getMessage());
        }
    }

    public function updatedContent()
    {
        $this->isTyping = strlen($this->content) > 0;
        // Pode disparar evento de "está a escrever"
        // $this->dispatch('user-typing', userId: Auth::id());
    }

    public function updatedSearchQuery($value)
    {
        $this->dispatch('search-updated', searchQuery: $value);
    }

    public function openSearch(): void
    {
        $this->searchOpen = true;
    }

    public function closeSearch(): void
    {
        $this->searchOpen = false;
        $this->searchQuery = '';
        $this->dispatch('search-updated', searchQuery: '');
    }

    public function toggleSearch(): void
    {
        $this->searchOpen = ! $this->searchOpen;

        if (! $this->searchOpen) {
            $this->searchQuery = '';
            $this->dispatch('search-updated', searchQuery: '');
        }
    }

    public function render()
    {
        return view('livewire.chat.message-input');
    }
}
