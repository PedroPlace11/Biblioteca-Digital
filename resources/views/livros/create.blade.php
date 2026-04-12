<x-app-layout>
    {{-- Formulário de criação de livro com metadados, autores e capa. --}}
    <div class="p-6 max-w-5xl mx-auto">
        <div class="mb-6 text-left">
            {{-- Botão para voltar à lista de livros --}}
            <a href="{{ route('livros.index') }}" class="btn btn-outline text-xl px-4 py-2 min-h-0 h-auto leading-none" aria-label="Voltar aos Livros" title="Voltar">&larr;</a>
        </div>

        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-6 md:p-8">
                <div class="mb-6">
                    <h1 class="text-2xl md:text-3xl font-bold text-base-content">Criar Livro</h1>
                    <p class="text-sm text-base-content/70 mt-2">Preencha os dados abaixo para adicionar um novo livro ao acervo.</p>
                </div>

                @if ($errors->any())
                    {{-- Alerta exibido quando existem erros de validação no formulário --}}
                    <div class="alert alert-error mb-6">
                        <span>Existem campos por corrigir. Verifique o formulário antes de guardar.</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('livros.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf


                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Campo obrigatório: ISBN do livro --}}
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">ISBN</span>
                            </label>
                            <input
                                type="text"
                                name="isbn"
                                placeholder="Ex.: 9789720000000"
                                value="{{ old('isbn') }}"
                                maxlength="13"
                                required
                                class="input input-bordered w-full @error('isbn') input-error @enderror">
                            <span class="text-xs text-base-content/60 mt-1">Máximo de 13 caracteres.</span>
                            @error('isbn')
                                {{-- Mensagem de validação do ISBN informado. --}}
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Campo opcional: preço do livro --}}
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Preço (EUR)</span>
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="preco"
                                placeholder="Ex.: 19,90"
                                value="{{ old('preco') }}"
                                class="input input-bordered w-full @error('preco') input-error @enderror">
                            @error('preco')
                                {{-- Mensagem de validação do preço do livro. --}}
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Campo obrigatório: nome do livro --}}
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Nome do Livro</span>
                        </label>
                        <input
                            type="text"
                            name="nome"
                            placeholder="Título completo do livro"
                            value="{{ old('nome') }}"
                            class="input input-bordered w-full @error('nome') input-error @enderror">
                        @error('nome')
                            {{-- Mensagem de validação do título do livro. --}}
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
                                <option value="" disabled {{ old('editora_id') ? '' : 'selected' }}>Selecione uma editora</option>
                                @foreach($editoras as $editora)
                                    <option value="{{ $editora->id }}" {{ (string) old('editora_id') === (string) $editora->id ? 'selected' : '' }}>
                                        {{ $editora->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @error('editora_id')
                                {{-- Mensagem de validação para a editora selecionada. --}}
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Upload opcional da capa do livro --}}
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Capa do Livro</span>
                                <span class="label-text-alt text-base-content/60">Opcional</span>
                            </label>
                            <input
                                type="file"
                                name="imagem_capa"
                                accept="image/*"
                                class="file-input file-input-bordered w-full @error('imagem_capa') file-input-error @enderror">
                            @error('imagem_capa')
                                {{-- Mensagem de validação para a imagem da capa. --}}
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Campo obrigatório: seleção de autores (múltipla) --}}
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Autores</span>
                            <span class="label-text-alt text-base-content/60">Pode selecionar vários</span>
                        </label>
                        <select name="autor_id" class="select select-bordered w-full @error('autores') select-error @enderror">
                            <option disabled selected>Selecione um autor</option>
                            @foreach($autores as $autor)
                                <option value="{{ $autor->id }}" {{ old('autor_id') == $autor->id ? 'selected' : '' }}>{{ $autor->nome }}</option>
                            @endforeach
                        </select>
                        @error('autores')
                            {{-- Mensagem de validação para a seleção de autores. --}}
                            <span class="text-error text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Campo opcional: bibliografia, sinopse ou notas do livro --}}
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Bibliografia</span>
                        </label>
                        <textarea
                            name="bibliografia"
                            rows="5"
                            placeholder="Descrição, sinopse ou notas relevantes sobre o livro..."
                            class="textarea textarea-bordered w-full @error('bibliografia') textarea-error @enderror">{{ old('bibliografia') }}</textarea>
                        @error('bibliografia')
                            {{-- Mensagem de validação para a bibliografia/sinopse. --}}
                            <span class="text-error text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="pt-2 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                        {{-- Botão para cancelar e voltar, e botão para submeter o formulário --}}
                        <a href="{{ route('livros.index') }}" class="btn btn-ghost">Cancelar</a>
                        <button class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white" type="submit">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>



