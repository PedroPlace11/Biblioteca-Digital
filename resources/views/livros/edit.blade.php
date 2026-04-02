<x-app-layout>
    <div class="p-6 max-w-5xl mx-auto">
        <div class="mb-6 text-left">
            {{-- Botão para voltar ao perfil do livro --}}
            <a href="{{ route('livros.show', $livro) }}" class="btn btn-outline text-xl px-4 py-2 min-h-0 h-auto leading-none" aria-label="Voltar ao Livro" title="Voltar">&larr;</a>
        </div>

        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-6 md:p-8">
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-base-content">Editar Livro</h1>
                        <p class="text-sm text-base-content/70 mt-2">Atualize metadados, relações e capa do livro.</p>
                    </div>
                    {{-- Botão para apagar o livro, com confirmação --}}
                    <form action="{{ route('livros.destroy', $livro) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja apagar este livro?');">
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

                <form method="POST" action="{{ route('livros.update', $livro) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')


                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Campo obrigatório: ISBN do livro --}}
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">ISBN</span>
                            </label>
                            <input type="text" name="isbn" value="{{ old('isbn', $livro->isbn) }}" class="input input-bordered w-full @error('isbn') input-error @enderror">
                            @error('isbn')
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Campo opcional: preço do livro --}}
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Preço (EUR)</span>
                            </label>
                            <input type="number" step="0.01" min="0" name="preco" value="{{ old('preco', $livro->preco) }}" class="input input-bordered w-full @error('preco') input-error @enderror">
                            @error('preco')
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Campo obrigatório: nome do livro --}}
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Nome</span>
                        </label>
                        <input type="text" name="nome" value="{{ old('nome', $livro->nome) }}" class="input input-bordered w-full @error('nome') input-error @enderror">
                        @error('nome')
                            <span class="text-error text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>


                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        {{-- Campo obrigatório: seleção da editora --}}
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Editora</span>
                            </label>
                            <select name="editora_id" class="select select-bordered w-full @error('editora_id') select-error @enderror">
                                @foreach($editoras as $editora)
                                    <option value="{{ $editora->id }}" {{ (string) old('editora_id', $livro->editora_id) === (string) $editora->id ? 'selected' : '' }}>
                                        {{ $editora->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @error('editora_id')
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Upload opcional da capa do livro --}}
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Capa do Livro</span>
                                <span class="label-text-alt text-base-content/60">Opcional</span>
                            </label>
                            <input type="file" name="imagem_capa" class="file-input file-input-bordered w-full @error('imagem_capa') file-input-error @enderror" accept="image/*">
                            @error('imagem_capa')
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror

                            {{-- Exibe a capa atual do livro, se existir --}}
                            @if($livro->imagem_capa)
                                <div class="mt-4">
                                    <p class="text-sm font-semibold mb-2">Capa atual</p>
                                    <img src="{{ asset($livro->imagem_capa) }}" alt="Capa atual" class="w-20 h-28 object-cover rounded">
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Campo obrigatório: seleção de autores (múltipla) --}}
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Autores</span>
                        </label>
                        <select name="autores[]" multiple class="select select-bordered w-full min-h-40 @error('autores') select-error @enderror">
                            @php
                                $autoresSelecionados = collect(old('autores', $livro->autores->pluck('id')->all()))->map(fn ($id) => (string) $id);
                            @endphp
                            @foreach($autores as $autor)
                                <option value="{{ $autor->id }}" {{ $autoresSelecionados->contains((string) $autor->id) ? 'selected' : '' }}>
                                    {{ $autor->nome }}
                                </option>
                            @endforeach
                        </select>
                        @error('autores')
                            <span class="text-error text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Campo opcional: bibliografia, sinopse ou notas do livro --}}
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Bibliografia</span>
                        </label>
                        <textarea name="bibliografia" rows="5" class="textarea textarea-bordered w-full @error('bibliografia') textarea-error @enderror">{{ old('bibliografia', $livro->bibliografia) }}</textarea>
                        @error('bibliografia')
                            <span class="text-error text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="pt-2 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                        {{-- Botão para cancelar e voltar, e botão para submeter as alterações --}}
                        <a href="{{ route('livros.show', $livro) }}" class="btn btn-ghost">Cancelar</a>
                        <button class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white" type="submit">Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>



