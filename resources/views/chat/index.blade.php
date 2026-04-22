@extends('layouts.app')

@section('content')
<div x-data="{ activeTab: @js($initialTab ?? 'rooms'), roomsView: @js($initialRoomsView ?? 'mine') }" class="flex h-[calc(100vh-8rem)] overflow-hidden bg-slate-100">
    <!-- Sidebar -->
    <div class="w-80 bg-white border-r border-slate-200/80 flex flex-col shadow-sm">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200">
            @php
                $pendingInvitations = auth()->user()->roomInvitationsReceived()
                    ->where('status', 'pending')
                    ->count();

                $hasRoomRequestsNotice = auth()->user()->isAdmin()
                    && isset($roomRequestsForAdmin)
                    && $roomRequestsForAdmin->isNotEmpty();
            @endphp

            <div class="flex items-center justify-between {{ ($pendingInvitations > 0 || $hasRoomRequestsNotice) ? 'mb-4' : '' }}">
                <h1 class="text-2xl font-bold text-gray-900">Chat</h1>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('chat.rooms.create') }}" class="bg-black hover:bg-slate-900 text-white px-3 py-2 rounded-xl text-sm font-semibold transition shadow-sm shadow-black/20">
                        + Sala
                    </a>
                @endif
            </div>

            <!-- Notificações de convites -->
            @if($pendingInvitations > 0)
                <a href="{{ route('chat.invitations.index') }}" class="block w-full mb-3 px-3 py-2 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-800 text-sm hover:bg-yellow-100 transition">
                    📬 {{ $pendingInvitations }} convite{{ $pendingInvitations > 1 ? 's' : '' }} pendente{{ $pendingInvitations > 1 ? 's' : '' }}
                </a>
            @endif

            @if(auth()->user()->isAdmin() && isset($roomRequestsForAdmin) && $roomRequestsForAdmin->isNotEmpty())
                <div class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-xs font-semibold text-blue-800 mb-2">Pedidos para entrar</p>
                    <div class="space-y-2 max-h-40 overflow-y-auto">
                        @foreach($roomRequestsForAdmin as $joinRequest)
                            <div class="bg-white rounded p-2 border border-blue-100">
                                <p class="text-xs text-gray-800">
                                    <span class="font-semibold">{{ $joinRequest->user->name }}</span>
                                    quer entrar em <span class="font-semibold">{{ $joinRequest->room->name }}</span>
                                </p>
                                <div class="mt-2 flex items-center gap-2">
                                    <form method="POST" action="{{ route('chat.rooms.join-request.approve', [$joinRequest->room, $joinRequest]) }}">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 text-xs rounded bg-green-600 text-white hover:bg-green-700">Aceitar</button>
                                    </form>
                                    <form method="POST" action="{{ route('chat.rooms.join-request.decline', [$joinRequest->room, $joinRequest]) }}">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 text-xs rounded bg-red-600 text-white hover:bg-red-700">Recusar</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-slate-200 bg-slate-50/70">
            <button type="button" @click="activeTab = 'rooms'" :class="activeTab === 'rooms' ? 'text-blue-700 border-blue-600 bg-white' : 'text-gray-500 border-transparent hover:text-gray-900 hover:border-gray-300'" class="flex-1 px-4 py-3 text-sm font-semibold border-b-2 transition">
                Salas
            </button>
            <button type="button" @click="activeTab = 'messages'" :class="activeTab === 'messages' ? 'text-blue-700 border-blue-600 bg-white' : 'text-gray-500 border-transparent hover:text-gray-900 hover:border-gray-300'" class="flex-1 px-4 py-3 text-sm font-semibold border-b-2 transition text-center">
                Mensagens
            </button>
        </div>

        <!-- Lista de Salas -->
        <div x-show="activeTab === 'rooms'" class="flex-1 overflow-y-auto">
            <div class="p-2 space-y-3">
                <div class="flex items-center justify-between px-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500" x-text="roomsView === 'mine' ? 'As minhas salas' : 'Outras salas'"></p>
                        <button type="button"
                            @click="roomsView = roomsView === 'mine' ? 'browse' : 'mine'"
                            class="px-3 py-1.5 text-xs rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                        <span x-show="roomsView === 'mine'">Ver outras salas</span>
                        <span x-show="roomsView === 'browse'">Voltar às minhas salas</span>
                    </button>
                </div>

                <div x-show="roomsView === 'mine'" class="space-y-1">
                @forelse($rooms as $room)
                    @php
                        $isMember = $room->users->contains('id', auth()->id());
                    @endphp
                    <div class="block px-4 py-3 rounded-xl transition group border {{ isset($selectedRoom) && $selectedRoom->id === $room->id ? 'bg-blue-50 border-blue-200 shadow-sm' : 'bg-white border-transparent hover:bg-slate-50 hover:border-slate-200' }}">
                        <div class="flex items-start gap-3">
                            <div class="shrink-0">
                                @if($room->avatar)
                                    <img src="{{ asset('storage/' . $room->avatar) }}" alt="{{ $room->name }}" class="h-11 w-11 rounded-full object-cover border border-gray-200">
                                @else
                                    <div class="h-11 w-11 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold border border-blue-100">
                                        {{ strtoupper(mb_substr($room->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        @if($isMember)
                                            <a href="{{ route('chat.rooms.index', ['tab' => 'rooms', 'room' => $room->id]) }}" class="font-semibold text-gray-900 group-hover:text-blue-600 block truncate {{ isset($selectedRoom) && $selectedRoom->id === $room->id ? 'text-blue-700' : '' }}">{{ $room->name }}</a>
                                        @else
                                            <p class="font-semibold text-gray-900 truncate">{{ $room->name }}</p>
                                        @endif

                                        @if($room->last_message)
                                            <p class="text-xs text-gray-500 truncate mt-1">{{ Str::limit($room->last_message->content, 30) }}</p>
                                        @endif
                                    </div>
                                    @if($isMember)
                                        @php
                                            $roomNotificationMode = $room->pivot->notification_mode ?? 'all';
                                            $roomNotificationLabel = $roomNotificationMode === 'mentions'
                                                ? 'Só meu nome'
                                                : ($roomNotificationMode === 'none' ? 'Nenhuma' : 'Todas');
                                            $roomBellClass = $roomNotificationMode === 'none'
                                                ? 'text-slate-400 border-slate-200 bg-white'
                                                : ($roomNotificationMode === 'mentions'
                                                    ? 'text-amber-600 border-amber-200 bg-amber-50'
                                                    : 'text-sky-700 border-sky-200 bg-sky-50');
                                        @endphp
                                        <details class="relative shrink-0" onclick="event.stopPropagation()">
                                            <summary class="list-none inline-flex items-center justify-center w-8 h-8 rounded-lg border transition cursor-pointer {{ $roomBellClass }}" title="Notificações desta sala: {{ $roomNotificationLabel }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9a6 6 0 1 0-12 0v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                                </svg>
                                            </summary>
                                            <div class="absolute right-0 top-9 z-20 w-44 rounded-lg border border-slate-200 bg-white p-2 shadow-lg">
                                                <p class="px-2 pb-1 text-[10px] uppercase tracking-wide text-slate-500">Sininho da sala</p>
                                                <form method="POST" action="{{ route('chat.rooms.notification-preference', $room) }}" class="space-y-1">
                                                    @csrf
                                                    <button type="submit" name="notification_mode" value="all" class="w-full rounded-md px-2 py-1.5 text-left text-xs {{ $roomNotificationMode === 'all' ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">Todas</button>
                                                    <button type="submit" name="notification_mode" value="mentions" class="w-full rounded-md px-2 py-1.5 text-left text-xs {{ $roomNotificationMode === 'mentions' ? 'bg-amber-50 text-amber-700 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">Só meu nome</button>
                                                    <button type="submit" name="notification_mode" value="none" class="w-full rounded-md px-2 py-1.5 text-left text-xs {{ $roomNotificationMode === 'none' ? 'bg-slate-100 text-slate-700 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">Nenhuma</button>
                                                </form>
                                            </div>
                                        </details>
                                    @endif
                                    @if($room->is_archived)
                                        <span class="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded shrink-0">Arquivada</span>
                                    @endif
                                </div>

                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-gray-500">
                        <p class="text-sm">Nenhuma sala ainda</p>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('chat.rooms.create') }}" class="text-blue-600 hover:underline text-sm mt-2 inline-block">
                                Criar primeira sala
                            </a>
                        @endif
                    </div>
                @endforelse
                </div>

                <div x-show="roomsView === 'browse'" class="space-y-1" x-cloak>
                    @forelse($otherRooms as $room)
                        <div class="block px-4 py-3 rounded-xl hover:bg-slate-50 transition group border border-transparent hover:border-slate-200 bg-white">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0">
                                    @if($room->avatar)
                                        <img src="{{ asset('storage/' . $room->avatar) }}" alt="{{ $room->name }}" class="h-11 w-11 rounded-full object-cover border border-gray-200">
                                    @else
                                        <div class="h-11 w-11 rounded-full bg-gradient-to-br from-slate-400 to-slate-600 flex items-center justify-center text-white font-bold border border-slate-100">
                                            {{ strtoupper(mb_substr($room->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 truncate">{{ $room->name }}</p>
                                        </div>
                                        @if($room->is_archived)
                                            <span class="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded shrink-0">Arquivada</span>
                                        @endif
                                    </div>

                                    <div class="mt-3 flex items-center gap-2">
                                        <a href="{{ route('chat.rooms.index', ['tab' => 'rooms', 'rooms_view' => 'browse', 'room_details' => $room->id]) }}" class="px-3 py-1.5 text-xs rounded bg-slate-900 text-white hover:bg-slate-700 transition">Ver detalhes</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-gray-500">
                            <p class="text-sm">Não há outras salas disponíveis</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Lista de Mensagens Diretas -->
        <div x-show="activeTab === 'messages'" x-cloak class="flex-1 overflow-y-auto">
            <div class="p-2 space-y-1">
                @forelse($conversations ?? collect() as $user)
                    @php
                        $lastMessage = $user->sentDirectMessages
                            ->concat($user->receivedDirectMessages)
                            ->sortByDesc('created_at')
                            ->first();

                        $isUnread = $lastMessage &&
                                   !$lastMessage->read_at &&
                                   $lastMessage->sender_id !== auth()->id();
                    @endphp

                          <a href="{{ route('chat.rooms.index', ['tab' => 'messages', 'dm' => $user->id]) }}"
                              class="block px-4 py-3 rounded-xl hover:bg-slate-50 transition group border {{ isset($selectedRecipient) && $selectedRecipient->id === $user->id ? 'bg-blue-50 border-blue-200 shadow-sm' : 'border-transparent' }}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 flex items-center space-x-3">
                                <div class="relative shrink-0">
                                    @if($user->profile_photo_path)
                                        <img src="{{ asset('storage/' . $user->profile_photo_path) }}"
                                             alt="{{ $user->name }}"
                                             class="h-10 w-10 rounded-full object-cover">
                                    @else
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold text-sm">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <span class="absolute -bottom-0.5 -right-0.5 block h-3 w-3 rounded-full border-2 border-white shadow-sm {{ $user->is_online ? 'bg-green-500' : 'bg-gray-500' }}" title="{{ $user->is_online ? 'Online' : 'Offline' }}"></span>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <h3 class="font-medium text-gray-900 group-hover:text-blue-600 {{ $isUnread ? 'font-bold' : '' }}">{{ $user->name }}</h3>
                                    @if($lastMessage)
                                        <p class="text-xs text-gray-500 truncate {{ $isUnread ? 'font-semibold text-gray-700' : '' }}">
                                            {{ $lastMessage->sender_id === auth()->id() ? 'Você: ' : '' }}{{ Str::limit($lastMessage->content, 30) }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @if($isUnread)
                                <div class="w-2 h-2 bg-blue-600 rounded-full ml-2"></div>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="p-4 text-center text-gray-500">
                        <p class="text-sm">Nenhuma conversa ainda</p>
                        <p class="text-xs mt-2">Comece uma conversa direta com alguém</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col bg-slate-50">
        <div x-show="activeTab === 'rooms'" x-cloak class="flex-1 flex flex-col overflow-hidden bg-white">
            @if(isset($selectedRoom) && $selectedRoom)
                <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between bg-gradient-to-r from-white to-slate-50">
                    <div class="flex items-center space-x-4">
                        @if($selectedRoom->avatar)
                            <img src="{{ asset('storage/' . $selectedRoom->avatar) }}" alt="{{ $selectedRoom->name }}" class="h-12 w-12 rounded-full object-cover">
                        @else
                            <div class="h-12 w-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold">
                                {{ substr($selectedRoom->name, 0, 1) }}
                            </div>
                        @endif
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                                <span>{{ $selectedRoom->name }}</span>
                                <a href="{{ route('chat.rooms.index', ['tab' => 'rooms', 'rooms_view' => 'mine', 'room_details' => $selectedRoom->id]) }}" class="inline-flex items-center justify-center w-5 h-5 rounded-full border border-slate-300 text-slate-500 hover:text-blue-600 hover:border-blue-300 transition" title="Ver descrição da sala">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </a>
                            </h2>
                            <p class="text-sm text-gray-500">{{ $selectedRoom->users->count() }} membro{{ $selectedRoom->users->count() !== 1 ? 's' : '' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        @if(auth()->user()->isAdmin() || auth()->user()->id === $selectedRoom->creator_id)
                            <a href="{{ route('chat.rooms.edit', $selectedRoom) }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="flex-1 px-5 py-4 bg-gradient-to-b from-slate-50 to-slate-100/70 overflow-hidden">
                    @livewire('chat.message-list', ['room' => $selectedRoom])
                </div>

                <div class="border-t border-slate-200 px-5 py-4 bg-white/95 backdrop-blur-sm shrink-0">
                    @livewire('chat.message-input', ['room' => $selectedRoom])
                </div>
            @elseif(isset($selectedRoomDetails) && $selectedRoomDetails)
                <div class="flex-1 overflow-y-auto bg-gray-50 px-6 py-8" x-data="{ showMembers: false }">
                    <div class="max-w-4xl mx-auto bg-white border border-gray-200 rounded-2xl shadow-sm p-6 md:p-8">
                        <div class="flex items-center justify-between mb-6">
                            <a href="{{ ($selectedRoomDetailsIsMember ?? false)
                                ? route('chat.rooms.index', ['tab' => 'rooms', 'room' => $selectedRoomDetails->id])
                                : route('chat.rooms.index', ['tab' => 'rooms', 'rooms_view' => 'browse']) }}" class="inline-flex items-center justify-center p-2 rounded-lg border border-gray-200 bg-white text-gray-600 hover:text-gray-900 hover:border-gray-300 transition" title="Voltar" aria-label="Voltar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </a>
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Detalhes da sala</span>
                        </div>

                        <div class="flex flex-col items-center text-center gap-4 pb-6 border-b border-gray-100">
                            @if($selectedRoomDetails->avatar)
                                <img src="{{ asset('storage/' . $selectedRoomDetails->avatar) }}" alt="{{ $selectedRoomDetails->name }}" class="h-16 w-16 rounded-full object-cover border border-gray-200 shadow-sm">
                            @else
                                <div class="h-16 w-16 rounded-full bg-gradient-to-br from-slate-400 to-slate-600 flex items-center justify-center text-white text-xl font-bold shadow-sm">
                                    {{ strtoupper(mb_substr($selectedRoomDetails->name, 0, 1)) }}
                                </div>
                            @endif

                            <div class="min-w-0">
                                <h2 class="text-3xl font-bold text-gray-900">{{ $selectedRoomDetails->name }}</h2>
                                <div class="relative mt-1">
                                    <button type="button" @click="showMembers = !showMembers" class="text-sm text-gray-500 hover:text-blue-600 transition cursor-pointer inline-flex items-center gap-1">
                                        <span>{{ $selectedRoomDetails->users->count() }} membro{{ $selectedRoomDetails->users->count() !== 1 ? 's' : '' }}</span>
                                        <svg :class="showMembers && 'rotate-180'" class="transition-transform w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                        </svg>
                                    </button>

                                    <div x-show="showMembers" x-transition.origin.top @click.away="showMembers = false" class="absolute top-full left-1/2 transform -translate-x-1/2 mt-2 w-72 bg-white border border-gray-200 rounded-2xl shadow-xl z-10 overflow-hidden">
                                        <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/70">
                                            <h4 class="text-xs font-semibold uppercase tracking-[0.08em] text-gray-500">Membros ({{ $selectedRoomDetails->users->count() }})</h4>
                                        </div>
                                        <div class="p-3">
                                            <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                                                @forelse($selectedRoomDetails->users as $member)
                                                    @php
                                                        $memberPivotRole = $member->pivot->role ?? 'member';
                                                        $isRoomAdminMember = $memberPivotRole === 'admin' || $selectedRoomDetails->creator_id === $member->id;
                                                        $isCurrentUser = $member->id === auth()->id();
                                                        $canOpenPrivate = ! $isCurrentUser && $member->role !== 'admin';
                                                        $canManageThisMember = ($selectedRoomDetailsCanManageMembers ?? false)
                                                            && ! $isCurrentUser
                                                            && $selectedRoomDetails->creator_id !== $member->id;
                                                    @endphp

                                                    <div class="p-2.5 rounded-xl border {{ $isCurrentUser ? 'bg-gray-50 border-gray-100' : 'bg-white border-gray-100 hover:border-blue-200 hover:bg-blue-50/40' }} transition">
                                                        <div class="flex items-start gap-3">
                                                            <div class="relative">
                                                                @if($member->profile_photo_path)
                                                                    <img src="{{ asset('storage/' . $member->profile_photo_path) }}" alt="{{ $member->name }}" class="h-9 w-9 rounded-full object-cover border border-gray-200 shadow-sm">
                                                                @else
                                                                    <div class="h-9 w-9 rounded-full {{ $isRoomAdminMember ? 'bg-gradient-to-br from-red-400 to-red-600' : 'bg-gradient-to-br from-blue-400 to-blue-600' }} flex items-center justify-center text-white text-xs font-bold shadow-sm">
                                                                        {{ strtoupper(mb_substr($member->name, 0, 1)) }}
                                                                    </div>
                                                                @endif
                                                                <!-- Status Online/Offline Icon -->
                                                                <div class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2 border-white {{ $member->is_online ? 'bg-green-500' : 'bg-gray-400' }}" title="{{ $member->is_online ? 'Online' : 'Offline' }}"></div>
                                                            </div>

                                                            <div class="flex-1 min-w-0 text-left">
                                                                <div class="flex items-center gap-2">
                                                                    @if($canOpenPrivate)
                                                                        <a href="{{ route('chat.rooms.index', ['tab' => 'messages', 'dm' => $member->id]) }}" class="text-sm font-semibold text-gray-900 truncate hover:text-blue-600">{{ $member->name }}</a>
                                                                    @else
                                                                        <p class="text-sm font-semibold text-gray-700 truncate">{{ $member->name }}{{ $isCurrentUser ? ' (você)' : '' }}</p>
                                                                    @endif
                                                                    @if($isRoomAdminMember)
                                                                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-semibold">Admin</span>
                                                                    @endif
                                                                </div>
                                                                <p class="text-xs text-gray-500 truncate">{{ $member->email }}</p>

                                                                @if($canManageThisMember)
                                                                    <div class="mt-2.5 flex items-center gap-2">
                                                                        @if(! $isRoomAdminMember)
                                                                            <form method="POST" action="{{ route('chat.rooms.update-member-role', [$selectedRoomDetails, $member]) }}">
                                                                                @csrf
                                                                                @method('PATCH')
                                                                                <input type="hidden" name="role" value="admin">
                                                                                <button type="submit" class="px-2.5 py-1 text-[11px] rounded-md bg-indigo-600 text-white font-medium hover:bg-indigo-700">Tornar admin</button>
                                                                            </form>
                                                                        @else
                                                                            <form method="POST" action="{{ route('chat.rooms.update-member-role', [$selectedRoomDetails, $member]) }}">
                                                                                @csrf
                                                                                @method('PATCH')
                                                                                <input type="hidden" name="role" value="member">
                                                                                <button type="submit" class="px-2.5 py-1 text-[11px] rounded-md bg-amber-600 text-white font-medium hover:bg-amber-700">Remover admin</button>
                                                                            </form>
                                                                        @endif

                                                                        <form method="POST" action="{{ route('chat.rooms.remove-member', [$selectedRoomDetails, $member]) }}" onsubmit="return confirm('Expulsar este membro da sala?');">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="px-2.5 py-1 text-[11px] rounded-md bg-red-600 text-white font-medium hover:bg-red-700">Expulsar</button>
                                                                        </form>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="text-sm text-gray-500 text-center py-3">Nenhum membro</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <h3 class="text-base font-semibold text-gray-700 mb-3">Descrição</h3>
                            <div class="bg-gray-50 border border-gray-100 rounded-xl p-5">
                                <p class="text-base text-gray-700 leading-8 whitespace-pre-line">
                                    {{ $selectedRoomDetails->description ?: 'Esta sala ainda não possui descrição.' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center gap-3">
                            @if($selectedRoomDetailsIsMember ?? false)
                                <a href="{{ route('chat.rooms.index', ['tab' => 'rooms', 'room' => $selectedRoomDetails->id]) }}" class="px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">Abrir sala</a>
                            @elseif(in_array($selectedRoomDetails->id, $pendingJoinRequestRoomIds ?? [], true))
                                <span class="px-3 py-2 text-sm rounded-lg bg-yellow-50 text-yellow-800 border border-yellow-200">Pedido pendente</span>
                            @else
                                <form method="POST" action="{{ route('chat.rooms.join-request', $selectedRoomDetails) }}">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">Pedir permissao para entrar</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="flex-1 flex items-center justify-center bg-gray-50">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma sala selecionada</h3>
                        <p class="mt-1 text-sm text-gray-500">Selecione uma sala ou crie uma nova</p>
                    </div>
                </div>
            @endif
        </div>

        <div x-show="activeTab === 'messages'" x-cloak class="flex-1 flex flex-col overflow-hidden bg-white">
            @if(isset($selectedRecipient) && $selectedRecipient)
                <livewire:chat.direct-message-chat :recipient="$selectedRecipient" :key="'chat-index-dm-' . $selectedRecipient->id" />
            @else
                <div class="flex-1 flex items-center justify-center bg-gray-50">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma conversa selecionada</h3>
                        <p class="mt-1 text-sm text-gray-500">Selecione uma conversa ou comece uma nova</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
