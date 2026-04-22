@extends('layouts.app')

@section('content')
<div class="flex min-h-[calc(100vh-4rem)] bg-gray-100">
    <!-- Sidebar com salas -->
    <div class="w-80 bg-white border-r border-gray-200 flex flex-col">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Chat</h1>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('chat.rooms.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition">
                        + Sala
                    </a>
                @endif
            </div>
        </div>

        <!-- Lista de Salas -->
        <div class="flex-1 overflow-y-auto">
            <div class="p-2 space-y-1">
                @forelse($rooms as $r)
                    <a href="{{ route('chat.rooms.show', $r) }}"
                       class="block px-4 py-3 rounded-lg hover:bg-gray-50 transition group {{ $r->id === $room->id ? 'bg-blue-50 border-l-4 border-blue-600' : '' }}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="font-medium text-gray-900 group-hover:text-blue-600">{{ $r->name }}</h3>
                                @if($r->last_message)
                                    <p class="text-xs text-gray-500 truncate">{{ Str::limit($r->last_message->content, 30) }}</p>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-4 text-center text-gray-500 text-sm">Nenhuma sala</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Main Chat Area -->
    <div class="flex-1 flex flex-col bg-white overflow-hidden">
        <!-- Room Header -->
        <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between bg-white">
            <div class="flex items-center space-x-4">
                @if($room->avatar)
                    <img src="{{ asset('storage/' . $room->avatar) }}" alt="{{ $room->name }}" class="h-12 w-12 rounded-full object-cover">
                @else
                    <div class="h-12 w-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold">
                        {{ substr($room->name, 0, 1) }}
                    </div>
                @endif
                <div>
                    <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <span>{{ $room->name }}</span>
                        <a href="{{ route('chat.rooms.details', $room) }}" class="inline-flex items-center justify-center w-5 h-5 rounded-full border border-gray-300 text-gray-500 hover:text-blue-600 hover:border-blue-300 transition" title="Ver descrição da sala">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </a>
                    </h2>
                    <p class="text-sm text-gray-500">{{ $room->users->count() }} membro{{ $room->users->count() !== 1 ? 's' : '' }}</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                @if(auth()->user()->isAdmin() || auth()->user()->id === $room->creator_id)
                    <a href="{{ route('chat.rooms.edit', $room) }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                @endif

                <!-- Members Dropdown -->
                <div class="relative group">
                    <button class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-2a6 6 0 0112 0v2zm0 0h6v-2a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </button>

                    <!-- Members Popover -->
                    <div class="absolute right-0 mt-0 w-48 bg-white rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-10">
                        <div class="p-3 border-b border-gray-100">
                            <h3 class="font-semibold text-gray-900 text-sm">Membros ({{ $room->users->count() }})</h3>
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            @foreach($room->users as $member)
                                <div class="px-3 py-2 hover:bg-gray-50 flex items-center justify-between">
                                    <div class="flex items-center space-x-2 flex-1">
                                        <div class="relative">
                                            @if($member->profile_photo_path)
                                                <img src="{{ asset('storage/' . $member->profile_photo_path) }}" alt="{{ $member->name }}" class="h-8 w-8 rounded-full object-cover">
                                            @else
                                                <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center text-xs font-bold text-gray-700">
                                                    {{ substr($member->name, 0, 1) }}
                                                </div>
                                            @endif
                                            <!-- Status Online/Offline Icon -->
                                            <div class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2 border-white {{ $member->is_online ? 'bg-green-500' : 'bg-gray-400' }}" title="{{ $member->is_online ? 'Online' : 'Offline' }}"></div>
                                        </div>
                                        @if($member->id !== auth()->id() && !(auth()->user()->role === 'cidadao' && $member->role === 'admin'))
                                            <a href="{{ route('chat.direct-messages.show', $member) }}" class="text-sm text-gray-900 hover:text-blue-600 hover:underline">{{ $member->name }}</a>
                                        @else
                                            <span class="text-sm text-gray-900">{{ $member->name }}</span>
                                        @endif
                                    </div>
                                    @if(auth()->user()->isAdmin() || auth()->user()->id === $room->creator_id)
                                        @if($member->id !== auth()->user()->id)
                                            <form action="{{ route('chat.rooms.remove-member', [$room, $member]) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-700 text-xs">Remover</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if(auth()->user()->isAdmin() || auth()->user()->id === $room->creator_id)
                            <div class="border-t border-gray-100 p-2">
                                <button onclick="document.getElementById('invite-modal').classList.remove('hidden')" class="w-full px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded transition">
                                    + Convidar
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 px-5 py-4 bg-slate-50 overflow-hidden">
            @livewire('chat.message-list', ['room' => $room])
        </div>

        <!-- Input Area -->
        <div class="border-t border-gray-200 px-5 py-4 bg-white">
            @livewire('chat.message-input', ['room' => $room])
        </div>
    </div>
</div>

<!-- Invite Modal -->
<div id="invite-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full mx-4">
        <h2 class="text-xl font-bold mb-4 text-gray-900">Convidar Utilizadores</h2>

        <form action="{{ route('chat.invitations.store', $room) }}" method="POST">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Selecione utilizadores</label>
                <div id="users-list" class="space-y-2 max-h-64 overflow-y-auto">
                    <!-- Preenchido dinamicamente -->
                </div>
            </div>

            <div class="flex space-x-2">
                <button type="button" onclick="document.getElementById('invite-modal').classList.add('hidden')" class="flex-1 px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Convidar
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Carregar utilizadores disponíveis
    document.getElementById('invite-modal').addEventListener('click', function(e) {
        if (e.target.id === 'invite-modal') {
            // Se clicou fora, fecha
        }
    });

    // Quando modal abre, carregar utilizadores
    document.addEventListener('click', function(e) {
        if (e.target.closest('[onclick*="invite-modal"]')) {
            fetch('{{ route("chat.rooms.available-users", $room) }}')
                .then(r => r.json())
                .then(users => {
                    const list = document.getElementById('users-list');
                    list.innerHTML = users.map(u => `
                        <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
                            <input type="checkbox" name="users[]" value="${u.id}" class="w-4 h-4 text-blue-600 rounded">
                            <span class="ml-3 text-sm text-gray-700">${u.name} (${u.email})</span>
                        </label>
                    `).join('');
                });
        }
    });
</script>
@endpush

@endsection
