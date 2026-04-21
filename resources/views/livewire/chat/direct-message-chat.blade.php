<div x-data="{
    emojiOpen: false,
    emojiGroups: {
        'Rostos': ['😀','😃','😄','😁','😆','😅','😂','🤣','😊','🙂','😉','😍','😘','😗','😙','😚','😋','😜','🤪','🤨','🧐','🤓','😎','🥳','😤','😢','😭','😡','🤯','😱','😴','🤗','🤔','🫡','🤝'],
        'Gestos': ['👍','👎','👏','🙌','🙏','🤲','👌','✌️','🤞','🤟','🫶','👊','🤛','🤜','💪','🫡','👋','🙋','🙆','🙅','🤦','🤷','💁','🙇'],
        'Coracoes': ['❤️','🩷','🧡','💛','💚','🩵','💙','💜','🤎','🖤','🤍','💔','❣️','💕','💞','💓','💗','💖','💘','💝'],
        'Objetos': ['🔥','⭐','✨','🎉','🎊','🎯','🏆','💡','💻','📚','📌','📎','✏️','📝','📷','🎵','🔒','🔓','✅','❌','⚠️','📣']
    },
    addEmoji(emoji) {
        const input = this.$refs.contentInput;
        if (!input) return;
        input.value = `${input.value || ''}${emoji}`;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.focus();
    }
}" class="flex-1 flex flex-col min-h-0 h-full overflow-hidden">
<!-- Header -->
<div class="border-b border-gray-200 p-4 flex items-center justify-between">
    <div class="flex items-center space-x-4">
        @if($recipient->profile_photo_path)
            <img src="{{ asset('storage/' . $recipient->profile_photo_path) }}"
                 alt="{{ $recipient->name }}"
                 class="h-12 w-12 rounded-full object-cover">
        @else
            <div class="h-12 w-12 rounded-full bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold">
                {{ substr($recipient->name, 0, 1) }}
            </div>
        @endif
        <div>
            <h2 class="text-xl font-bold text-gray-900">{{ $recipient->name }}</h2>
            <p class="text-sm text-gray-500">{{ $recipient->email }}</p>
        </div>
    </div>
</div>

<!-- Messages -->
<div class="flex-1 overflow-y-scroll px-4 md:px-8 py-4 min-h-0">
    <div class="max-w-3xl mx-auto space-y-4">
    @forelse($messages as $message)
        @if($message->sender_id === auth()->user()->id)
            <!-- Mensagem enviada -->
            <div class="flex justify-end">
                <div class="max-w-xs group">
                    @if($message->type === 'text')
                        <div class="px-4 py-2.5 bg-blue-600 text-white rounded-2xl rounded-br-md shadow-sm shadow-blue-200/50 break-words text-sm leading-6">
                            {{ $message->content }}
                        </div>
                    @elseif($message->type === 'image')
                        <div class="relative">
                            <img src="{{ asset('storage/' . $message->file_path) }}"
                                 alt="{{ $message->file_name }}"
                                 class="rounded-lg rounded-br-none max-h-80">
                        </div>
                    @elseif($message->type === 'file')
                        <div class="flex items-center space-x-2 bg-blue-600 text-white px-4 py-2.5 rounded-2xl rounded-br-md shadow-sm shadow-blue-200/50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <a href="{{ asset('storage/' . $message->file_path) }}"
                               download
                               class="hover:underline text-sm">
                                {{ $message->file_name }}
                            </a>
                        </div>
                    @endif

                    <div class="mt-1 px-1 flex items-center gap-2 flex-row-reverse">
                        <span class="text-[10px] text-slate-400">{{ $message->created_at->format('H:i') }}</span>
                        @if($message->updated_at && $message->updated_at->gt($message->created_at))
                            <span class="text-[10px] text-slate-400">editada</span>
                        @endif
                        @if($message->sender_id === auth()->user()->id && $message->type === 'text')
                            <button wire:click="startEditing({{ $message->id }})"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity text-[10px] text-slate-400 hover:text-blue-600">
                                editar
                            </button>
                        @endif
                        <button wire:click="deleteMessage({{ $message->id }})"
                                class="opacity-0 group-hover:opacity-100 transition-opacity text-[10px] text-slate-400 hover:text-red-600">
                            apagar
                        </button>
                    </div>

                    @if($message->sender_id === auth()->user()->id && $message->type === 'text' && $editingMessageId === $message->id)
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
        @else
            <!-- Mensagem recebida -->
            <div class="flex justify-start">
                <div class="max-w-xs group">
                    @if($message->type === 'text')
                        <div class="px-4 py-2.5 bg-white text-slate-800 border border-slate-200 rounded-2xl rounded-bl-md shadow-sm shadow-slate-200/70 break-words text-sm leading-6">
                            {{ $message->content }}
                        </div>
                    @elseif($message->type === 'image')
                        <img src="{{ asset('storage/' . $message->file_path) }}"
                             alt="{{ $message->file_name }}"
                             class="rounded-lg rounded-bl-none max-h-80">
                    @elseif($message->type === 'file')
                        <div class="flex items-center space-x-2 bg-white border border-slate-200 px-4 py-2.5 rounded-2xl rounded-bl-md shadow-sm shadow-slate-200/70">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <a href="{{ asset('storage/' . $message->file_path) }}"
                               download
                               class="hover:underline text-sm text-gray-900">
                                {{ $message->file_name }}
                            </a>
                        </div>
                    @endif

                    <span class="text-xs text-gray-500 mt-1 inline-block">
                        {{ $message->created_at->format('H:i') }}
                        @if($message->read_at)
                            ✓✓
                        @endif
                    </span>
                </div>
            </div>
        @endif
    @empty
        <div class="flex flex-col items-center justify-center min-h-[260px] text-gray-500">
            <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm">Comece uma conversa!</p>
        </div>
    @endforelse
    </div>
</div>

<!-- Input -->
<div class="shrink-0 border-t border-slate-200 px-5 py-4 bg-white/95 backdrop-blur-sm">
    <div class="max-w-3xl mx-auto w-full">
    <form wire:submit="sendMessage" class="space-y-2">
        <div class="group flex items-end gap-2 bg-white border border-slate-200 rounded-2xl p-2.5 shadow-sm shadow-slate-200/80 focus-within:border-blue-300 focus-within:shadow-md focus-within:shadow-blue-100 transition">
            <label class="p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl cursor-pointer transition" title="Anexar arquivo">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828a4 4 0 00-5.656-5.656L5.757 10.757a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
                <input type="file" wire:model="file" class="hidden" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">
            </label>

            <div class="relative">
                <button type="button"
                        @click="emojiOpen = !emojiOpen"
                        class="p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl cursor-pointer transition"
                        title="Abrir emojis"
                        aria-label="Abrir emojis">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828A4 4 0 0112 16a4 4 0 01-2.828-1.172M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>

                <div x-show="emojiOpen"
                     x-transition.origin.bottom.left
                     @click.away="emojiOpen = false"
                     class="absolute bottom-12 left-0 w-80 max-w-[86vw] bg-white border border-slate-200 rounded-2xl shadow-xl p-3 z-20">
                    <div class="max-h-72 overflow-y-auto pr-1 space-y-3">
                        <template x-for="(emojis, groupName) in emojiGroups" :key="groupName">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-2" x-text="groupName"></p>
                                <div class="grid grid-cols-8 gap-1">
                                    <template x-for="emoji in emojis" :key="emoji">
                                        <button type="button"
                                                @click="addEmoji(emoji)"
                                                class="h-8 w-8 inline-flex items-center justify-center text-lg rounded-lg hover:bg-slate-100 transition"
                                                x-text="emoji"></button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <input type="text"
                   wire:model="content"
                   x-ref="contentInput"
                   placeholder="Escreva uma mensagem..."
                   class="flex-1 bg-transparent px-2 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 border-0 focus:outline-none focus:ring-0"
                   wire:keydown.enter="sendMessage">

            <button type="submit"
                    class="h-10 w-10 inline-flex items-center justify-center bg-gradient-to-br from-slate-800 to-slate-900 text-white rounded-full hover:from-slate-900 hover:to-black transition shadow-sm shadow-slate-300 disabled:opacity-60"
                    title="Enviar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </button>
        </div>

        @if($file)
            <div class="flex items-center space-x-2 bg-blue-50 p-2.5 rounded-xl border border-blue-100">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="text-sm font-medium text-blue-900">{{ $file->getClientOriginalName() }}</span>
                <button type="button"
                        wire:click="$set('file', null)"
                        class="ml-auto text-blue-600 hover:text-blue-700 font-semibold">
                    ✕
                </button>
            </div>
        @endif

        @error('content')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </form>
    </div>
</div>
</div>
