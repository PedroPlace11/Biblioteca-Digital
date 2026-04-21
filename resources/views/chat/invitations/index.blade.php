@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4">
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Convites para Salas</h1>

            @if($invitations->isEmpty())
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">Nenhum convite</h3>
                    <p class="mt-1 text-sm text-gray-500">Quando receber convites, aparecerão aqui</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($invitations as $invitation)
                        <div class="border border-gray-200 rounded-lg p-4 flex items-start justify-between hover:shadow-md transition">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    @if($invitation->room->avatar)
                                        <img src="{{ asset('storage/' . $invitation->room->avatar) }}" 
                                             alt="{{ $invitation->room->name }}" 
                                             class="h-12 w-12 rounded-lg object-cover">
                                    @else
                                        <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold">
                                            {{ substr($invitation->room->name, 0, 1) }}
                                        </div>
                                    @endif

                                    <div>
                                        <h3 class="font-semibold text-gray-900">{{ $invitation->room->name }}</h3>
                                        @if($invitation->room->description)
                                            <p class="text-sm text-gray-600">{{ Str::limit($invitation->room->description, 60) }}</p>
                                        @endif
                                        <p class="text-xs text-gray-500 mt-1">
                                            Convidado por <strong>{{ $invitation->invitedBy->name }}</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex space-x-2 ml-4">
                                <form action="{{ route('chat.invitations.accept', $invitation) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium">
                                        ✓ Aceitar
                                    </button>
                                </form>

                                <form action="{{ route('chat.invitations.decline', $invitation) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-medium">
                                        ✕ Recusar
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Histórico de Convites Antigos (aceites/recusados) -->
    @php
        $oldInvitations = auth()->user()->roomInvitationsReceived()
            ->whereIn('status', ['accepted', 'declined'])
            ->latest()
            ->limit(10)
            ->get();
    @endphp

    @if($oldInvitations->isNotEmpty())
        <div class="bg-white rounded-lg shadow mt-6">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Histórico</h2>
                <div class="space-y-2 text-sm">
                    @foreach($oldInvitations as $inv)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <div>
                                <span class="text-gray-900">{{ $inv->room->name }}</span>
                                <span class="text-gray-500 mx-2">•</span>
                                <span class="text-gray-500">{{ $inv->updated_at->diffForHumans() }}</span>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $inv->status === 'accepted' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $inv->status === 'accepted' ? 'Aceite' : 'Recusado' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
