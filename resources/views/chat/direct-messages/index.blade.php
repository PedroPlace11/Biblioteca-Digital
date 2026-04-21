@extends('layouts.app')

@section('content')
<div class="flex min-h-[calc(100vh-4rem)] bg-gray-100">
    <!-- Sidebar com conversas -->
    <div class="w-80 bg-white border-r border-gray-200 flex flex-col">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200">
            <h1 class="text-2xl font-bold text-gray-900">Mensagens</h1>
        </div>

        <!-- Lista de conversas -->
        <div class="flex-1 overflow-y-auto">
            <div class="p-2 space-y-1">
                @forelse($conversations as $user)
                    @php
                        $lastMessage = $user->sentDirectMessages
                            ->concat($user->receivedDirectMessages)
                            ->sortByDesc('created_at')
                            ->first();

                        $isUnread = $lastMessage &&
                                   !$lastMessage->read_at &&
                                   $lastMessage->sender_id !== auth()->user()->id;
                    @endphp

                    <a href="{{ route('chat.direct-messages.show', $user) }}"
                       class="block px-4 py-3 rounded-lg hover:bg-gray-50 transition group {{ request()->route('user') && request()->route('user')->id === $user->id ? 'bg-blue-50 border-l-4 border-blue-600' : '' }}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 flex items-center space-x-3">
                                @if($user->profile_photo_path)
                                    <img src="{{ asset('storage/' . $user->profile_photo_path) }}"
                                         alt="{{ $user->name }}"
                                         class="h-10 w-10 rounded-full object-cover">
                                @else
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold text-sm">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                @endif

                                <div class="flex-1 min-w-0">
                                    <h3 class="font-medium text-gray-900 group-hover:text-blue-600 {{ $isUnread ? 'font-bold' : '' }}">{{ $user->name }}</h3>
                                    @if($lastMessage)
                                        <p class="text-xs text-gray-500 truncate {{ $isUnread ? 'font-semibold text-gray-700' : '' }}">
                                            {{ $lastMessage->sender_id === auth()->user()->id ? 'Você: ' : '' }}{{ Str::limit($lastMessage->content, 30) }}
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

    <!-- Chat Area -->
    <div class="flex-1 flex flex-col bg-white overflow-hidden">
        @if(isset($recipient))
            @livewire('chat.direct-message-chat', ['recipient' => $recipient])
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

@endsection
