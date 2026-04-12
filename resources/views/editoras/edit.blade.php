<x-app-layout>
    {{-- Ecrã de edição de editora com ações de atualização e remoção. --}}
    <div class="p-6 max-w-5xl mx-auto">
        <div class="mb-6 text-left">
            {{-- Botão para voltar ao perfil da editora --}}
            <a href="{{ route('editoras.show', $editora) }}" class="btn btn-outline text-xl px-4 py-2 min-h-0 h-auto leading-none" aria-label="Voltar à Editora" title="Voltar">&larr;</a>
        </div>

        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-6 md:p-8">
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-base-content">Editar Editora</h1>
                        <p class="text-sm text-base-content/70 mt-2">Atualize os dados da editora e o logótipo institucional.</p>
                    </div>
                    {{-- Botão para apagar a editora, com confirmação --}}
                    <form action="{{ route('editoras.destroy', $editora) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja apagar esta editora?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white text-red-600 border border-red-300 text-sm font-semibold hover:bg-red-50 hover:border-red-400 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                            Apagar
                        </button>
                    </form>
                </div>

                @if ($errors->any())
                    {{-- Alerta exibido quando existem erros de validação no formulário --}}
                    <div class="alert alert-error mb-6">
                        <span>Existem campos por corrigir. Verifique o formulário antes de guardar.</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('editoras.update', $editora) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        {{-- Campo obrigatório: nome da editora --}}
                        <div class="form-control lg:col-span-2">
                            <label class="label">
                                <span class="label-text font-semibold">Nome</span>
                            </label>
                            <input type="text" name="nome" value="{{ old('nome', $editora->nome) }}" class="input input-bordered w-full @error('nome') input-error @enderror" required>
                            @error('nome')
                                {{-- Erro de validação do nome atualizado da editora. --}}
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Upload opcional do logotipo da editora --}}
                        <div class="form-control lg:col-span-2">
                            <label class="label">
                                <span class="label-text font-semibold">Logótipo</span>
                                <span class="label-text-alt text-base-content/60">Opcional</span>
                            </label>
                            <input type="file" name="logotipo" class="file-input file-input-bordered w-full @error('logotipo') file-input-error @enderror" accept="image/*">
                            @error('logotipo')
                                {{-- Erro de upload do novo logótipo. --}}
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror

                            {{-- Exibe o logotipo atual da editora, se existir --}}
                            @if ($editora->logotipo)
                                <div class="mt-4">
                                    <p class="text-sm font-semibold mb-2">Logótipo atual</p>
                                    <img src="{{ asset($editora->logotipo) }}" class="w-24 rounded" alt="Logótipo atual">
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="pt-2 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                        {{-- Botão para cancelar e voltar, e botão para submeter as alterações --}}
                        <a href="{{ route('editoras.show', $editora) }}" class="btn btn-ghost">Cancelar</a>
                        <button class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white" type="submit">Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>



