<div x-data="{
    emojiOpen: false,
    emojiGroups: {
        'Rostos': ['😀','😃','😄','😁','😆','😅','😂','🤣','😊','🙂','😉','😍','😘','😗','😙','😚','😋','😜','🤪','🤨','🧐','🤓','😎','🥳','😤','😢','😭','😡','🤯','😱','😴','🤗','🤔','🫡','🤝'],
        'Gestos': ['👍','👎','👏','🙌','🙏','🤲','👌','✌️','🤞','🤟','🫶','👊','🤛','🤜','💪','🫡','👋','🙋','🙆','🙅','🤦','🤷','💁','🙇'],
        'Coracoes': ['❤️','🩷','🧡','💛','💚','🩵','💙','💜','🤎','🖤','🤍','💔','❣️','💕','💞','💓','💗','💖','💘','💝'],
        'Objetos': ['🔥','⭐','✨','🎉','🎊','🎯','🏆','💡','💻','📚','📌','📎','✏️','📝','📷','🎵','🔒','🔓','✅','❌','⚠️','📣'],
        'Natureza': ['🌞','🌙','⭐','☁️','🌧️','⛈️','🌈','🌊','🌿','🌳','🌸','🌼','🌻','🌺','🍀','🍁','🐶','🐱','🦊','🐼','🦁','🦋'],
        'Comida': ['🍎','🍌','🍇','🍓','🍍','🥭','🍉','🥑','🍔','🍕','🌭','🍟','🌮','🍣','🍜','🍩','🍪','🎂','☕','🍵','🥤','🍫']
    },
    addEmoji(emoji) {
        const input = this.$refs.contentInput;
        if (!input) return;

        input.value = `${input.value || ''}${emoji}`;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.focus();
    }
}" class="max-w-3xl mx-auto w-full space-y-2">
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

        <!-- Preview de arquivo selecionado -->
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

        <!-- Erros -->
        @error('content')
            <div class="p-2 bg-red-50 text-red-600 rounded-lg text-sm border border-red-100">
                {{ $message }}
            </div>
        @enderror
    </form>

    <!-- Dica de digitação -->
    @if($isTyping)
        <div class="flex items-center space-x-2 text-xs text-gray-500">
            <div class="flex space-x-1">
                <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"></span>
                <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s;"></span>
                <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s;"></span>
            </div>
            <span>A escrever...</span>
        </div>
    @endif
</div>
