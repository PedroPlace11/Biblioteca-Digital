<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomInvitation;
use App\Models\User;
use App\Notifications\RoomInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomInvitationController extends Controller
{
    /**
     * Lista convites pendentes para o utilizador
     */
    public function index()
    {
        $invitations = Auth::user()->roomInvitationsReceived()
            ->with(['room', 'invitedBy'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('chat.invitations.index', compact('invitations'));
    }

    /**
     * Cria um novo convite (admin only)
     */
    public function store(Request $request, Room $room)
    {
        // Apenas admins podem convidar
        $this->authorize('update', $room);

        $validated = $request->validate([
            'users' => 'required|array',
            'users.*' => 'exists:users,id',
        ]);

        $createdCount = 0;

        foreach ($validated['users'] as $userId) {
            // Verifica se utilizador já é membro
            if ($room->hasMember($userId)) {
                continue;
            }

            // Verifica se já existe convite pendente
            $existing = RoomInvitation::where('room_id', $room->id)
                ->where('invited_user_id', $userId)
                ->where('status', 'pending')
                ->exists();

            if ($existing) {
                continue;
            }

            // Cria o convite
            $invitation = RoomInvitation::create([
                'room_id' => $room->id,
                'invited_user_id' => $userId,
                'invited_by_id' => Auth::id(),
            ]);

            // Envia notificação
            $user = User::find($userId);
            $user->notify(new RoomInvitationNotification($invitation));

            $createdCount++;
        }

        return redirect()->route('chat.rooms.show', $room)
            ->with('success', "Convites enviados com sucesso! ({$createdCount} utilizadores)");
    }

    /**
     * Aceita um convite
     */
    public function accept(RoomInvitation $invitation)
    {
        // Autoriza que apenas o utilizador convidado aceite
        if (Auth::id() !== $invitation->invited_user_id) {
            return response()->json([
                'message' => 'Não autorizado',
            ], 403);
        }

        $invitation->accept();

        return redirect()->route('chat.rooms.show', $invitation->room)
            ->with('success', 'Você entrou na sala com sucesso!');
    }

    /**
     * Recusa um convite
     */
    public function decline(RoomInvitation $invitation)
    {
        // Autoriza que apenas o utilizador convidado recuse
        if (Auth::id() !== $invitation->invited_user_id) {
            return response()->json([
                'message' => 'Não autorizado',
            ], 403);
        }

        $invitation->decline();

        return redirect()->route('chat.invitations.index')
            ->with('success', 'Convite recusado');
    }

    /**
     * Remove um convite (por admin)
     */
    public function destroy(Room $room, RoomInvitation $invitation)
    {
        // Autoriza que apenas admin remova
        $this->authorize('update', $room);

        if ($invitation->room_id !== $room->id) {
            return response()->json([
                'message' => 'Convite não pertence a esta sala',
            ], 422);
        }

        $invitation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Convite removido',
        ]);
    }
}
