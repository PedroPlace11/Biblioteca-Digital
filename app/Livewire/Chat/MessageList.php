<?php

namespace App\Livewire\Chat;

use App\Models\Room;
use App\Models\Message;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Storage;

class MessageList extends Component
{
    public Room $room;
    public $messages;
    public $lastMessageId = null;
    public ?int $editingMessageId = null;
    public string $editingContent = '';
    public $searchQuery = '';

    public function mount()
    {
        $this->messages = collect();
        $this->loadMessages();
    }

    #[\Livewire\Attributes\On('search-updated')]
    public function onSearchUpdated($searchQuery)
    {
        $this->searchQuery = $searchQuery;
    }

    public function loadMessages()
    {
        $this->messages = $this->room->messages()
            ->with('user:id,name,profile_photo_path')
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        if ($this->messages->count() > 0) {
            $this->lastMessageId = $this->messages->last()->id;
        }

        $this->dispatch('messages-loaded');
    }

    #[On('message-sent')]
    public function refreshMessages()
    {
        $this->loadMessages();
    }

    #[On('message-deleted')]
    public function onMessageDeleted($messageId)
    {
        $this->messages = $this->messages->filter(fn($m) => $m->id != $messageId);
    }

    public function deleteMessage(Message $message)
    {
        // Autoriza
        $this->authorize('delete', $message);

        // Remove arquivo se existir
        if ($message->file_path) {
            Storage::disk('public')->delete($message->file_path);
        }

        $message->delete();
        $this->dispatch('message-deleted', messageId: $message->id);
    }

    public function startEditing(int $messageId): void
    {
        $message = $this->room->messages()->find($messageId);

        if (! $message instanceof Message || $message->type !== 'text') {
            return;
        }

        $this->authorize('update', $message);

        $this->editingMessageId = $message->id;
        $this->editingContent = (string) $message->content;
    }

    public function cancelEditing(): void
    {
        $this->editingMessageId = null;
        $this->editingContent = '';
    }

    public function saveEditedMessage(int $messageId): void
    {
        $message = $this->room->messages()->find($messageId);

        if (! $message instanceof Message || $message->type !== 'text') {
            return;
        }

        $this->authorize('update', $message);

        $this->validate([
            'editingContent' => 'required|string|max:5000',
        ]);

        $newContent = trim($this->editingContent);
        if ($newContent === '') {
            $this->addError('editingContent', 'A mensagem não pode estar vazia.');
            return;
        }

        $message->update([
            'content' => $newContent,
        ]);

        $this->cancelEditing();
        $this->loadMessages();
        $this->dispatch('message-updated', messageId: $message->id);
    }

    public function getFilteredMessages()
    {
        $filtered = collect($this->messages);

        if ($this->searchQuery) {
            $query = strtolower($this->searchQuery);
            $filtered = $filtered->filter(function($message) use ($query) {
                return stripos($message->content, $query) !== false;
            });
        }

        return $filtered->values();
    }

    public function render()
    {
        return view('livewire.chat.message-list', [
            'filteredMessages' => $this->getFilteredMessages(),
        ]);
    }
}
