<div class="h-full overflow-y-auto px-4 md:px-8 py-4" wire:poll.2s="loadMessages">
    <div class="max-w-3xl mx-auto">
    @forelse($messages as $message)
        @php
            $isMine = auth()->id() === $message->user_id;
        @endphp

        <div class="mb-5 flex {{ $isMine ? 'justify-end' : 'justify-start' }} group" id="message-{{ $message->id }}">
            <div class="max-w-[78%] flex {{ $isMine ? 'flex-row-reverse' : 'flex-row' }} items-end gap-2">
                @if($message->user->profile_photo_path)
                    <img src="{{ asset('storage/' . $message->user->profile_photo_path) }}"
                         alt="{{ $message->user->name }}"
                         class="h-8 w-8 rounded-full object-cover flex-shrink-0">
                @else
                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-indigo-500 to-blue-500 flex items-center justify-center text-white font-bold flex-shrink-0 text-xs">
                        {{ substr($message->user->name, 0, 1) }}
                    </div>
                @endif

                <div class="{{ $isMine ? 'items-end' : 'items-start' }} flex flex-col">
                    <span class="text-[11px] text-slate-500 mb-1 px-1">{{ $message->user->name }}</span>

                    <div class="rounded-2xl px-4 py-2.5 shadow-sm {{ $isMine ? 'bg-blue-600 text-white rounded-br-md shadow-blue-200/50' : 'bg-white text-slate-800 border border-slate-200 rounded-bl-md shadow-slate-200/70' }}">
                        @if($message->type === 'text')
                            <p class="break-words text-sm leading-6">{{ $message->content }}</p>
                        @elseif($message->type === 'image')
                            <div class="max-w-xs">
                                <img src="{{ asset('storage/' . $message->file_path) }}"
                                     alt="{{ $message->file_name }}"
                                     class="rounded-lg max-h-80 w-auto">
                                @if($message->content)
                                    <p class="text-sm mt-2 break-words {{ $isMine ? 'text-blue-50' : 'text-slate-700' }}">{{ $message->content }}</p>
                                @endif
                            </div>
                        @elseif($message->type === 'file')
                            <div class="flex items-center gap-2 max-w-xs">
                                <svg class="w-4 h-4 {{ $isMine ? 'text-blue-100' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <a href="{{ asset('storage/' . $message->file_path) }}"
                                   download
                                   class="text-sm underline {{ $isMine ? 'text-white' : 'text-blue-600' }}">
                                    {{ $message->file_name }}
                                </a>
                            </div>
                            @if($message->content)
                                <p class="text-sm mt-2 break-words {{ $isMine ? 'text-blue-50' : 'text-slate-700' }}">{{ $message->content }}</p>
                            @endif
                        @endif
                    </div>

                    <div class="mt-1.5 px-1 flex items-center gap-2 {{ $isMine ? 'flex-row-reverse' : '' }}">
                        <span class="text-[10px] text-slate-400">{{ $message->created_at->format('H:i') }}</span>
                        @if($message->updated_at && $message->updated_at->gt($message->created_at))
                            <span class="text-[10px] text-slate-400">editada</span>
                        @endif
                        @if($isMine && $message->type === 'text')
                            <button wire:click="startEditing({{ $message->id }})"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity text-[10px] text-slate-400 hover:text-blue-600">
                                editar
                            </button>
                        @endif
                        @if(auth()->user()->id === $message->user_id || auth()->user()->isAdmin())
                            <button wire:click="deleteMessage({{ $message->id }})"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity text-[10px] text-slate-400 hover:text-red-600">
                                apagar
                            </button>
                        @endif
                    </div>

                    @if($isMine && $message->type === 'text' && $editingMessageId === $message->id)
                        <div class="mt-2 w-full min-w-[280px] rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
                            <textarea wire:model.defer="editingContent"
                                      rows="2"
                                      class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                      placeholder="Editar mensagem..."></textarea>

                            @if($errors->has('editingContent'))
                                <p class="mt-1 text-xs text-red-600">{{ $errors->first('editingContent') }}</p>
                            @endif

                            <div class="mt-2 flex items-center justify-end gap-2">
                                <button type="button"
                                        wire:click="cancelEditing"
                                        class="px-2.5 py-1 text-xs font-medium rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50">
                                    Cancelar
                                </button>
                                <button type="button"
                                        wire:click="saveEditedMessage({{ $message->id }})"
                                        class="px-2.5 py-1 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                    Guardar
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center justify-center h-full min-h-[360px] text-gray-500">
            <svg class="w-12 h-12 mb-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm">Nenhuma mensagem ainda. Comece a conversa!</p>
        </div>
    @endforelse
    </div>
</div>

@push('scripts')
<script>
    // Auto-scroll para última mensagem
    Livewire.on('messages-loaded', () => {
        setTimeout(() => {
            const container = document.querySelector('[wire\\:poll]');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }, 100);
    });
</script>
@endpush
