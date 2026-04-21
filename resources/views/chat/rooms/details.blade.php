@extends('layouts.app')

@section('content')
<div class="flex min-h-[calc(100vh-4rem)] bg-gray-100">
    <aside class="w-80 bg-white border-r border-gray-200 flex flex-col">
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Chat</h1>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('chat.rooms.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition">+ Sala</a>
                @endif
            </div>
        </div>

        <div class="flex-1 overflow-y-auto">
            <div class="p-2 space-y-1">
                @foreach($rooms as $r)
                    @php
                        $isMember = $r->users->contains('id', auth()->id());
                    @endphp
                    <div class="block px-4 py-3 rounded-lg hover:bg-gray-50 transition group border border-transparent hover:border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0">
                                @if($r->avatar)
                                    <img src="{{ asset('storage/' . $r->avatar) }}" alt="{{ $r->name }}" class="h-11 w-11 rounded-full object-cover border border-gray-200">
                                @else
                                    <div class="h-11 w-11 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold border border-blue-100">
                                        {{ strtoupper(mb_substr($r->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-gray-900 truncate">{{ $r->name }}</p>
                                <p class="text-xs text-gray-500 truncate mt-1">{{ $isMember ? 'Já és membro' : 'Sala disponível para pedido de entrada' }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </aside>

    <main class="flex-1 bg-white overflow-y-auto">
        <div class="max-w-xl w-full mx-auto px-6 py-10 text-center">
            <div class="mb-6 text-left">
                <a href="{{ route('chat.rooms.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>Voltar</span>
                </a>
            </div>
            @if($room->avatar)
                <img src="{{ asset('storage/' . $room->avatar) }}" alt="{{ $room->name }}" class="mx-auto h-24 w-24 rounded-full object-cover border border-gray-200 shadow-sm">
            @else
                <div class="mx-auto h-24 w-24 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-3xl font-bold shadow-sm">
                    {{ strtoupper(mb_substr($room->name, 0, 1)) }}
                </div>
            @endif

            <h2 class="mt-6 text-3xl font-bold text-gray-900">{{ $room->name }}</h2>
            <p class="mt-4 text-gray-600 leading-8 whitespace-pre-line">{{ $room->description ?: 'Sem descrição disponível.' }}</p>

            <div class="mt-6 flex items-center justify-center gap-2 text-sm text-gray-500">
                <span>{{ $room->users->count() }} membro{{ $room->users->count() !== 1 ? 's' : '' }}</span>
                <span>•</span>
                <span>Criada por {{ $room->creator?->name ?? 'Sistema' }}</span>
            </div>

            @php
                $isMember = $room->users->contains('id', auth()->id());
                $hasPendingRequest = in_array($room->id, $pendingJoinRequestRoomIds ?? [], true);
            @endphp

            <div class="mt-8 flex items-center justify-center gap-3">
                @if($isMember)
                    <a href="{{ route('chat.rooms.show', $room) }}" class="px-5 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">Abrir sala</a>
                @else
                    @if($hasPendingRequest)
                        <span class="px-5 py-3 rounded-xl bg-amber-100 text-amber-800 font-semibold">Pedido pendente</span>
                    @else
                        <form method="POST" action="{{ route('chat.rooms.join-request', $room) }}">
                            @csrf
                            <button type="submit" class="px-5 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">Pedir permissão para entrar</button>
                        </form>
                    @endif
                @endif
            </div>

        </div>
    </main>
</div>
@endsection
