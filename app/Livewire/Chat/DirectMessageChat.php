<?php

namespace App\Livewire\Chat;

use App\Models\User;
use App\Models\DirectMessage;
use App\Notifications\DirectMessageNotification;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DirectMessageChat extends Component
{
    use WithFileUploads;

    public User $recipient;
    public $messages = [];
    public $content = '';
    public $file = null;
    public $newMessageCount = 0;
    public ?int $editingMessageId = null;
    public string $editingContent = '';

    protected $rules = [
        'content' => 'nullable|string|max:5000',
        'file' => 'nullable|file|max:10240',
    ];

    public function mount()
    {
        $this->loadMessages();
        $this->markAsRead();
    }

    public function loadMessages()
    {
        $currentUser = Auth::user();

        $this->messages = DirectMessage::where(function($query) use ($currentUser) {
            $query->where('sender_id', $currentUser->id)
                ->where('recipient_id', $this->recipient->id);
        })
            ->orWhere(function($query) use ($currentUser) {
                $query->where('sender_id', $this->recipient->id)
                    ->where('recipient_id', $currentUser->id);
            })
            ->with('sender', 'recipient')
            ->orderBy('created_at', 'asc')
            ->limit(100)
            ->get()
            ->values();

            $this->dispatch('direct-messages-loaded');
    }

    public function sendMessage()
    {
        if (!$this->content && !$this->file) {
            return;
        }

        try {
            $messageData = [
                'sender_id' => Auth::id(),
                'recipient_id' => $this->recipient->id,
                'type' => 'text',
            ];

            if ($this->file) {
                $mimeType = $this->file->getMimeType();

                if (str_starts_with($mimeType, 'image/')) {
                    $messageData['type'] = 'image';
                } else {
                    $messageData['type'] = 'file';
                }

                $path = $this->file->store('direct-messages', 'public');
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

            DirectMessage::create($messageData);

            // Dispara notificação para o destinatário
            $this->recipient->notify(new DirectMessageNotification(DirectMessage::latest('id')->first()));

            $this->content = '';
            $this->file = null;
            $this->loadMessages();

        } catch (\Exception $e) {
            $this->addError('content', 'Erro ao enviar mensagem: ' . $e->getMessage());
        }
    }

    public function deleteMessage(DirectMessage $message)
    {
        // Autoriza
        $this->authorize('delete', $message);

        if ($message->file_path) {
            Storage::disk('public')->delete($message->file_path);
        }

        $message->delete();
        $this->loadMessages();
    }

    public function startEditing(int $messageId): void
    {
        $message = DirectMessage::find($messageId);

        if (! $message instanceof DirectMessage || $message->type !== 'text') {
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
        $message = DirectMessage::find($messageId);

        if (! $message instanceof DirectMessage || $message->type !== 'text') {
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
    }

    public function markAsRead()
    {
        DirectMessage::where('sender_id', $this->recipient->id)
            ->where('recipient_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function render()
    {
        return view('livewire.chat.direct-message-chat');
    }
}
