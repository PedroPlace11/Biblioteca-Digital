<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Envia uma mensagem para uma sala
     */
    public function store(Request $request, Room $room)
    {
        // Autoriza que apenas membros enviem mensagens
        $this->authorize('view', $room);

        $validated = $request->validate([
            'content' => 'nullable|string|max:5000',
            'file' => 'nullable|file|max:10240', // 10MB
        ]);

        $messageData = [
            'user_id' => Auth::id(),
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
            $path = $file->store('messages', 'public');
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

        $message = $room->messages()->create($messageData);

        return response()->json([
            'message' => $message->load('user'),
            'success' => true,
        ]);
    }

    /**
     * Obtém mensagens de uma sala (para Livewire polling)
     */
    public function getMessages(Room $room)
    {
        $this->authorize('view', $room);

        $messages = $room->messages()
            ->with('user:id,name,profile_photo_path')
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->reverse();

        return response()->json($messages);
    }

    /**
     * Obtém mensagens após um certo timestamp (para real-time)
     */
    public function getNewMessages(Request $request, Room $room)
    {
        $this->authorize('view', $room);

        $since = $request->query('since');

        $messages = $room->messages()
            ->with('user:id,name,profile_photo_path')
            ->when($since, function($query) use ($since) {
                $query->where('created_at', '>', $since);
            })
            ->latest('created_at')
            ->get();

        return response()->json($messages);
    }

    /**
     * Edita uma mensagem
     */
    public function update(Request $request, Room $room, Message $message)
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
     * Apaga uma mensagem
     */
    public function destroy(Room $room, Message $message)
    {
        // Autoriza que apenas o autor ou admin apague
        $this->authorize('delete', $message);

        // Se houver arquivo, remove do storage
        if ($message->file_path) {
            \Storage::disk('public')->delete($message->file_path);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mensagem eliminada',
        ]);
    }
}
