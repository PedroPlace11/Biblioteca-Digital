<x-app-layout>
    {{-- Área de edição com o mesmo padrão visual do formulário de criação. --}}
    <div class="p-6 max-w-5xl mx-auto">
        <div class="mb-6 text-left">
            {{-- Botão para voltar ao perfil do autor. --}}
            <a href="{{ route('autores.show', $autor) }}" class="btn btn-outline text-xl px-4 py-2 min-h-0 h-auto leading-none" aria-label="Voltar ao Autor" title="Voltar">&larr;</a>
        </div>

        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-6 md:p-8">
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-base-content">Editar Autor</h1>
                        <p class="text-sm text-base-content/70 mt-2">Atualize os dados do autor e mantenha o perfil completo.</p>
                    </div>
                    {{-- Botão para apagar o autor, com confirmação. --}}
                    <form action="{{ route('autores.destroy', $autor) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja apagar este autor?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white text-red-600 border border-red-300 text-sm font-semibold hover:bg-red-50 hover:border-red-400 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                            Apagar
                        </button>
                    </form>
                </div>

                @if ($errors->any())
                    {{-- Alerta exibido quando existem erros de validação no formulário. --}}
                    <div class="alert alert-error mb-6">
                        <span>Existem campos por corrigir. Verifique o formulário antes de guardar.</span>
                    </div>
                @endif

                {{-- Formulário principal para edição dos dados do autor. --}}
                <form method="POST" action="{{ route('autores.update', $autor) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        {{-- Campo obrigatório: nome do autor. --}}
                        <div class="form-control lg:col-span-2">
                            <label class="label">
                                <span class="label-text font-semibold">Nome</span>
                            </label>
                            <input type="text" name="nome" class="input input-bordered w-full @error('nome') input-error @enderror" value="{{ old('nome', $autor->nome) }}" required>
                            @error('nome')
                                {{-- Erro de validação retornado para o nome atualizado. --}}
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-control lg:col-span-2">
                            <label class="label">
                                <span class="label-text font-semibold">Biografia</span>
                            </label>
                            {{-- Campo opcional: biografia do autor. --}}
                            <textarea name="bibliografia" class="textarea textarea-bordered w-full @error('bibliografia') textarea-error @enderror" rows="6">{{ old('bibliografia', $autor->bibliografia) }}</textarea>
                            @error('bibliografia')
                                {{-- Erro exibido quando a biografia não cumpre validação. --}}
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-control lg:col-span-2">
                            <label class="label">
                                <span class="label-text font-semibold">Foto do Autor</span>
                                <span class="label-text-alt text-base-content/60">Opcional</span>
                            </label>
                            {{-- Upload opcional de foto do autor. --}}
                            <input type="file" name="foto" accept="image/*" class="file-input file-input-bordered w-full @error('foto') file-input-error @enderror">
                            @error('foto')
                                {{-- Erro de upload da nova foto (formato/tamanho). --}}
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror

                            {{-- Exibe a foto atual do autor, se existir. --}}
                            @if($autor->foto)
                                <div class="mt-4">
                                    <p class="text-sm font-semibold mb-2">Foto atual</p>
                                    <img src="{{ asset($autor->foto) }}" alt="Foto atual" class="w-20 h-20 rounded-full object-cover">
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="pt-2 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                        {{-- Botão para cancelar e voltar ao perfil, e botão para submeter as alterações. --}}
                        <a href="{{ route('autores.show', $autor) }}" class="btn btn-ghost">Cancelar</a>
                        <button class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white" type="submit">Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>



