<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\DirectMessage;
use App\Models\RoomJoinRequest;
use App\Models\RoomInvitation;
use App\Models\User;
use App\Notifications\RoomJoinRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    private function canManageRoomMembers(User $user, Room $room): bool
    {
        return $user->isAdmin() || $room->isRoomAdmin($user->id);
    }

    /**
     * Lista todas as salas em que o utilizador é membro
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $rooms = $user->rooms()
            ->with(['creator', 'users', 'messages' => function($query) {
                $query->latest()->limit(1);
            }])
            ->orderByDesc('updated_at')
            ->get();

        $otherRooms = Room::query()
            ->whereDoesntHave('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with(['creator', 'users', 'messages' => function($query) {
                $query->latest()->limit(1);
            }])
            ->orderByDesc('updated_at')
            ->get();

        $pendingJoinRequestRoomIds = RoomJoinRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->pluck('room_id')
            ->all();

        $roomRequestsForAdmin = collect();
        if ($user->isAdmin()) {
            $roomRequestsForAdmin = RoomJoinRequest::query()
                ->with(['room', 'user'])
                ->where('status', 'pending')
                ->latest()
                ->get();
        }

        $memberRoomIds = $user->rooms()->pluck('rooms.id');

        $conversations = User::query()
            ->where('id', '!=', $user->id)
            ->whereHas('rooms', function ($query) use ($memberRoomIds) {
                $query->whereIn('rooms.id', $memberRoomIds);
            })
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

        $conversations = $conversations
            ->sortByDesc(function ($conversationUser) {
                $lastMessage = $conversationUser->sentDirectMessages
                    ->concat($conversationUser->receivedDirectMessages)
                    ->sortByDesc('created_at')
                    ->first();

                return $lastMessage?->created_at?->timestamp ?? 0;
            })
            ->values();

        $selectedRecipient = null;
        $selectedRecipientId = (int) $request->query('dm');
        $selectedRoom = null;
        $selectedRoomId = (int) $request->query('room');
        $selectedRoomDetails = null;
        $selectedRoomDetailsId = (int) $request->query('room_details');
        $selectedRoomDetailsIsMember = false;
        $selectedRoomDetailsCanManageMembers = false;

        if ($selectedRecipientId > 0) {
            $candidateRecipient = User::with([
                'sentDirectMessages' => function ($query) use ($user) {
                    $query->where('recipient_id', $user->id)->latest();
                },
                'receivedDirectMessages' => function ($query) use ($user) {
                    $query->where('sender_id', $user->id)->latest();
                },
            ])->find($selectedRecipientId);

            $isMemberOfSameRoom = $candidateRecipient
                ? $user->rooms()->whereHas('users', function ($query) use ($candidateRecipient) {
                    $query->where('users.id', $candidateRecipient->id);
                })->exists()
                : false;

            if ($candidateRecipient && $candidateRecipient->id !== $user->id && $isMemberOfSameRoom) {
                $selectedRecipient = $candidateRecipient;

                if (! $conversations->contains('id', $selectedRecipient->id)) {
                    $conversations = collect([$selectedRecipient])->merge($conversations)->values();
                }

                DirectMessage::where('sender_id', $selectedRecipient->id)
                    ->where('recipient_id', $user->id)
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);
            }
        }

        if ($selectedRoomId > 0) {
            $candidateRoom = Room::with([
                'creator',
                'users',
                'messages' => function ($query) {
                    $query->latest()->limit(1);
                },
            ])->find($selectedRoomId);

            if ($candidateRoom && $user->rooms()->where('rooms.id', $candidateRoom->id)->exists()) {
                $selectedRoom = $candidateRoom;
            }
        }

        if ($selectedRoomDetailsId > 0) {
            $candidateRoomDetails = Room::with(['creator', 'users'])
                ->find($selectedRoomDetailsId);

            if ($candidateRoomDetails) {
                $selectedRoomDetails = $candidateRoomDetails;
                $selectedRoomDetailsIsMember = $user->rooms()->where('rooms.id', $candidateRoomDetails->id)->exists();
                $selectedRoomDetailsCanManageMembers = $this->canManageRoomMembers($user, $candidateRoomDetails);
            }
        }

        $initialTab = ($request->query('tab') === 'messages' || $selectedRecipient) ? 'messages' : 'rooms';
        $initialRoomsView = ($request->query('rooms_view') === 'browse'
            || ($selectedRoomDetails && ! $selectedRoomDetailsIsMember))
            ? 'browse'
            : 'mine';

        return view('chat.index', compact(
            'rooms',
            'otherRooms',
            'pendingJoinRequestRoomIds',
            'roomRequestsForAdmin',
            'conversations',
            'selectedRecipient',
            'selectedRoom',
            'selectedRoomDetails',
            'selectedRoomDetailsIsMember',
            'selectedRoomDetailsCanManageMembers',
            'initialTab'
            , 'initialRoomsView'
        ));
    }

    /**
     * Mostra uma sala específica
     */
    public function show(Room $room)
    {
        // Autoriza que apenas membros vejam a sala
        $this->authorize('view', $room);

        $rooms = Auth::user()->rooms()
            ->with(['creator', 'users', 'messages' => function($query) {
                $query->latest()->limit(1);
            }])
            ->orderByDesc('updated_at')
            ->get();

        return view('chat.rooms.show', compact('room', 'rooms'));
    }

    /**
     * Mostra detalhes públicos da sala e permite pedir entrada
     */
    public function details(Room $room)
    {
        $user = Auth::user();

        $rooms = $user->rooms()
            ->with(['creator', 'users', 'messages' => function($query) {
                $query->latest()->limit(1);
            }])
            ->orderByDesc('updated_at')
            ->get();

        $pendingJoinRequestRoomIds = RoomJoinRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->pluck('room_id')
            ->all();

        return view('chat.rooms.details', compact('room', 'rooms', 'pendingJoinRequestRoomIds'));
    }

    /**
     * Mostra o formulário de criar sala (apenas admin)
     */
    public function create()
    {
        $this->authorize('create', Room::class);

        return view('chat.rooms.create');
    }

    /**
     * Armazena uma nova sala
     */
    public function store(Request $request)
    {
        // Apenas admins podem criar salas
        $this->authorize('create', Room::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:rooms',
            'description' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048',
        ]);

        // Processa avatar
        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('rooms', 'public');
        }

        // Cria a sala com o utilizador como criador
        $room = Auth::user()->createdRooms()->create($validated);

        // Adiciona o criador como admin da sala
        $room->addMember(Auth::id(), 'admin');

        return redirect()->route('chat.rooms.show', $room)
            ->with('success', 'Sala criada com sucesso!');
    }

    /**
     * Mostra o formulário de editar sala
     */
    public function edit(Room $room)
    {
        $this->authorize('update', $room);

        return view('chat.rooms.edit', compact('room'));
    }

    /**
     * Atualiza uma sala
     */
    public function update(Request $request, Room $room)
    {
        $this->authorize('update', $room);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:rooms,name,' . $room->id,
            'description' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048',
        ]);

        // Processa novo avatar se enviado
        if ($request->hasFile('avatar')) {
            // Remove avatar antigo se existir
            if ($room->avatar) {
                Storage::disk('public')->delete($room->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('rooms', 'public');
        }

        $room->update($validated);

        return redirect()->route('chat.rooms.show', $room)
            ->with('success', 'Sala atualizada com sucesso!');
    }

    /**
     * Arquiva uma sala
     */
    public function archive(Room $room)
    {
        $this->authorize('delete', $room);

        $room->update(['is_archived' => true]);

        return redirect()->route('chat.rooms.index')
            ->with('success', 'Sala arquivada com sucesso!');
    }

    /**
     * Exclui uma sala
     */
    public function destroy(Room $room)
    {
        $this->authorize('delete', $room);

        $room->delete();

        return redirect()->route('chat.rooms.index')
            ->with('success', 'Sala eliminada com sucesso!');
    }

    /**
     * Adiciona um membro a uma sala (por admin)
     */
    public function addMember(Request $request, Room $room)
    {
        if (! $this->canManageRoomMembers(Auth::user(), $room)) {
            abort(403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);

        if ($room->hasMember($user->id)) {
            return response()->json([
                'message' => 'Utilizador já é membro da sala',
            ], 409);
        }

        $room->addMember($user->id, 'member');

        return response()->json([
            'message' => 'Utilizador adicionado com sucesso!',
        ]);
    }

    /**
     * Remove um membro de uma sala
     */
    public function removeMember(Room $room, User $user)
    {
        $currentUser = Auth::user();

        // Utilizador pode sair da sala; gestão de terceiros apenas por admin global/admin da sala.
        if ($currentUser->id !== $user->id && ! $this->canManageRoomMembers($currentUser, $room)) {
            abort(403);
        }

        if (! $room->hasMember($user->id)) {
            if (! request()->expectsJson()) {
                return redirect()->route('chat.rooms.index', ['tab' => 'rooms', 'room_details' => $room->id])
                    ->with('error', 'Utilizador não é membro da sala.');
            }

            return response()->json([
                'message' => 'Utilizador não é membro da sala',
            ], 404);
        }

        if ($room->creator_id === $user->id) {
            if (! request()->expectsJson()) {
                return redirect()->route('chat.rooms.index', ['tab' => 'rooms', 'room_details' => $room->id])
                    ->with('error', 'Não é possível expulsar o criador da sala.');
            }

            return response()->json([
                'message' => 'Não é possível expulsar o criador da sala',
            ], 422);
        }

        $room->removeMember($user->id);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Utilizador removido da sala',
            ]);
        }

        return redirect()->route('chat.rooms.index', ['tab' => 'rooms', 'room_details' => $room->id])
            ->with('success', 'Membro expulso da sala.');
    }

    /**
     * Promove/rebaixa membro para admin da sala
     */
    public function updateMemberRole(Request $request, Room $room, User $user)
    {
        $currentUser = Auth::user();

        if (! $this->canManageRoomMembers($currentUser, $room)) {
            abort(403);
        }

        if (! $room->hasMember($user->id)) {
            return redirect()->route('chat.rooms.index', ['tab' => 'rooms', 'room_details' => $room->id])
                ->with('error', 'Utilizador não é membro da sala.');
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,member',
        ]);

        if ($room->creator_id === $user->id && $validated['role'] !== 'admin') {
            return redirect()->route('chat.rooms.index', ['tab' => 'rooms', 'room_details' => $room->id])
                ->with('error', 'O criador da sala deve permanecer admin da sala.');
        }

        $room->setMemberRole($user->id, $validated['role']);

        return redirect()->route('chat.rooms.index', ['tab' => 'rooms', 'room_details' => $room->id])
            ->with('success', $validated['role'] === 'admin'
                ? 'Membro promovido a admin da sala.'
                : 'Admin da sala alterado para membro.');
    }

    /**
     * Lista utilizadores para convidar
     */
    public function availableUsers(Room $room)
    {
        $this->authorize('view', $room);

        $users = User::whereNotIn('id', $room->users()->pluck('id'))
            ->where('id', '!=', Auth::id())
            ->select('id', 'name', 'email')
            ->get();

        return response()->json($users);
    }

    /**
     * Utilizador pede permissão para entrar numa sala
     */
    public function requestJoin(Room $room)
    {
        $user = Auth::user();

        if ($room->hasMember($user->id)) {
            return redirect()->route('chat.rooms.show', $room)
                ->with('success', 'Já és membro desta sala.');
        }

        $joinRequest = RoomJoinRequest::updateOrCreate(
            [
                'room_id' => $room->id,
                'user_id' => $user->id,
            ],
            [
                'status' => 'pending',
                'handled_by_id' => null,
                'handled_at' => null,
            ]
        );

        $admins = User::query()->where('role', 'admin')->get();
        foreach ($admins as $admin) {
            /** @var User $admin */
            $admin->notify(new RoomJoinRequestNotification($joinRequest));
        }

        return redirect()->route('chat.rooms.index')
            ->with('success', 'Pedido enviado. Aguarda aprovação do admin.');
    }

    /**
     * Admin aprova pedido para entrar numa sala
     */
    public function approveJoinRequest(Room $room, RoomJoinRequest $joinRequest)
    {
        $this->authorize('update', $room);

        if ($joinRequest->room_id !== $room->id) {
            abort(404);
        }

        if (! $joinRequest->isPending()) {
            return redirect()->route('chat.rooms.index')
                ->with('success', 'Este pedido já foi tratado.');
        }

        $joinRequest->accept(Auth::id());

        return redirect()->route('chat.rooms.index')
            ->with('success', 'Pedido aprovado com sucesso.');
    }

    /**
     * Admin recusa pedido para entrar numa sala
     */
    public function declineJoinRequest(Room $room, RoomJoinRequest $joinRequest)
    {
        $this->authorize('update', $room);

        if ($joinRequest->room_id !== $room->id) {
            abort(404);
        }

        if (! $joinRequest->isPending()) {
            return redirect()->route('chat.rooms.index')
                ->with('success', 'Este pedido já foi tratado.');
        }

        $joinRequest->decline(Auth::id());

        return redirect()->route('chat.rooms.index')
            ->with('success', 'Pedido recusado.');
    }
}
