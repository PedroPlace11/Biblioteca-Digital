<?php

namespace App\Http\Controllers;

use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DirectMessageController extends Controller
{
    private function getConversationsFor(User $user): Collection
    {
        $conversations = User::query()
            ->where('id', '!=', $user->id)
            ->where(function ($query) use ($user) {
                $query->whereHas('sentDirectMessages', function ($q) use ($user) {
                    $q->where('recipient_id', $user->id);
                })->orWhereHas('receivedDirectMessages', function ($q) use ($user) {
                    $q->where('sender_id', $user->id);
                });
            })
            ->with([
                'sentDirectMessages' => function ($query) use ($user) {
                    $query->where('recipient_id', $user->id)->latest();
                },
                'receivedDirectMessages' => function ($query) use ($user) {
                    $query->where('sender_id', $user->id)->latest();
                },
            ])
            ->get();

        if ($user->role === 'cidadao') {
            $conversations = $conversations->where('role', 'cidadao')->values();
        }

        return $conversations
            ->sortByDesc(function ($conversationUser) {
                $lastMessage = $conversationUser->sentDirectMessages
                    ->concat($conversationUser->receivedDirectMessages)
                    ->sortByDesc('created_at')
                    ->first();

                return $lastMessage?->created_at?->timestamp ?? 0;
            })
            ->values();
    }

    private function cidadaoCannotMessageAdmin(User $currentUser, User $targetUser): bool
    {
        return $currentUser->role === 'cidadao' && $targetUser->role === 'admin';
    }

    /**
     * Lista todas as conversas diretas do utilizador
     */
    public function index()
    {
        $user = Auth::user();

        $conversations = $this->getConversationsFor($user);

        return view('chat.direct-messages.index', compact('conversations'));
    }

    /**
     * Mostra conversa com um utilizador específico
     */
    public function show(User $user)
    {
        $currentUser = Auth::user();

        if ($user->id === $currentUser->id) {
            return redirect()->route('chat.direct-messages.index');
        }

        if ($this->cidadaoCannotMessageAdmin($currentUser, $user)) {
            return redirect()->route('chat.direct-messages.index')
                ->with('error', 'Cidadão só pode enviar mensagem para cidadão.');
        }

        $conversations = $this->getConversationsFor($currentUser);

        if (! $conversations->contains('id', $user->id) && ! $this->cidadaoCannotMessageAdmin($currentUser, $user)) {
            $user->load(['sentDirectMessages' => function($query) use ($currentUser) {
                $query->where('recipient_id', $currentUser->id)->latest();
            }, 'receivedDirectMessages' => function($query) use ($currentUser) {
                $query->where('sender_id', $currentUser->id)->latest();
            }]);

            $conversations->prepend($user);
        }

        // Marca mensagens recebidas como lidas
        DirectMessage::where('sender_id', $user->id)
            ->where('recipient_id', $currentUser->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('chat.direct-messages.index', [
            'recipient' => $user,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Envia uma mensagem direta
     */
    public function store(Request $request, User $user)
    {
        $currentUser = Auth::user();

        if ($this->cidadaoCannotMessageAdmin($currentUser, $user)) {
            return response()->json([
                'message' => 'Cidadão só pode enviar mensagem para cidadão',
            ], 403);
        }

        if ($user->id === $currentUser->id) {
            return response()->json([
                'message' => 'Não pode enviar mensagens para si próprio',
            ], 422);
        }

        $validated = $request->validate([
            'content' => 'nullable|string|max:5000',
            'file' => 'nullable|file|max:10240', // 10MB
        ]);

        $messageData = [
            'sender_id' => $currentUser->id,
            'recipient_id' => $user->id,
            'type' => 'text',
        ];

        // Se houver arquivo
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $mimeType = $file->getMimeType();

            // Determina o tipo de mensagem
            if (str_starts_with($mimeType, 'image/')) {
                $messageData['type'] = 'image';
            } else {
                $messageData['type'] = 'file';
            }

            // Armazena o arquivo
            $path = $file->store('direct-messages', 'public');
            $messageData['file_path'] = $path;
            $messageData['file_name'] = $file->getClientOriginalName();
            $messageData['mime_type'] = $mimeType;

            // Se não houver conteúdo, usa nome do arquivo
            if (!$validated['content']) {
                $messageData['content'] = $file->getClientOriginalName();
            } else {
                $messageData['content'] = $validated['content'];
            }
        } else {
            // Se não houver arquivo, conteúdo é obrigatório
            if (!$validated['content']) {
                return response()->json([
                    'message' => 'Mensagem vazia ou arquivo inválido',
                ], 422);
            }
            $messageData['content'] = $validated['content'];
        }

        $message = DirectMessage::create($messageData);

        return response()->json([
            'message' => $message->load('sender', 'recipient'),
            'success' => true,
        ]);
    }

    /**
     * Obtém mensagens diretas após um timestamp (para real-time)
     */
    public function getNewMessages(Request $request, User $user)
    {
        $currentUser = Auth::user();

        if ($this->cidadaoCannotMessageAdmin($currentUser, $user)) {
            return response()->json([], 403);
        }

        $since = $request->query('since');

        $messages = DirectMessage::where(function($query) use ($currentUser, $user) {
            $query->where('sender_id', $currentUser->id)
                ->where('recipient_id', $user->id);
        })
            ->orWhere(function($query) use ($currentUser, $user) {
                $query->where('sender_id', $user->id)
                    ->where('recipient_id', $currentUser->id);
            })
            ->with('sender', 'recipient')
            ->when($since, function($query) use ($since) {
                $query->where('created_at', '>', $since);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Edita uma mensagem direta
     */
    public function update(Request $request, User $user, DirectMessage $message)
    {
        // Autoriza que apenas o autor edite
        $this->authorize('update', $message);

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $message->update($validated);

        return response()->json([
            'message' => $message,
            'success' => true,
        ]);
    }

    /**
     * Apaga uma mensagem direta
     */
    public function destroy(User $user, DirectMessage $message)
    {
        // Autoriza que apenas o autor apague
        $this->authorize('delete', $message);

        // Se houver arquivo, remove do storage
        if ($message->file_path) {
            Storage::disk('public')->delete($message->file_path);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mensagem eliminada',
        ]);
    }

    /**
     * Marca mensagens como lidas
     */
    public function markAsRead(User $user)
    {
        $currentUser = Auth::user();

        if ($this->cidadaoCannotMessageAdmin($currentUser, $user)) {
            return response()->json(['success' => false], 403);
        }

        DirectMessage::where('sender_id', $user->id)
            ->where('recipient_id', $currentUser->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Obtém número de mensagens não lidas
     */
    public function getUnreadCount()
    {
        $count = DirectMessage::where('recipient_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['unread_count' => $count]);
    }
}
