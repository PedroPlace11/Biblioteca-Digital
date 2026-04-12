<x-app-layout>
    {{-- Layout centralizado para a criação de uma nova editora. --}}
    <div class="p-6 max-w-5xl mx-auto">
        <div class="mb-6 text-left">
            {{-- Botão para voltar à lista de editoras --}}
            <a href="{{ route('editoras.index') }}" class="btn btn-outline text-xl px-4 py-2 min-h-0 h-auto leading-none" aria-label="Voltar às Editoras" title="Voltar">&larr;</a>
        </div>

        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-6 md:p-8">
                <div class="mb-6">
                    <h1 class="text-2xl md:text-3xl font-bold text-base-content">Criar Editora</h1>
                    <p class="text-sm text-base-content/70 mt-2">Registe uma nova editora e, se quiser, adicione o logótipo.</p>
                </div>

                @if ($errors->any())
                    {{-- Alerta exibido quando existem erros de validação no formulário --}}
                    <div class="alert alert-error mb-6">
                        <span>Existem campos por corrigir. Verifique o formulário antes de guardar.</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('editoras.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        {{-- Campo obrigatório: nome da editora --}}
                        <div class="form-control lg:col-span-2">
                            <label class="label">
                                <span class="label-text font-semibold">Nome da Editora</span>
                            </label>
                            <input
                                type="text"
                                name="nome"
                                value="{{ old('nome') }}"
                                placeholder="Nome da editora"
                                class="input input-bordered w-full @error('nome') input-error @enderror"
                                required>
                            @error('nome')
                                {{-- Mensagem de validação do campo nome da editora. --}}
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Upload opcional do logotipo da editora --}}
                        <div class="form-control lg:col-span-2">
                            <label class="label">
                                <span class="label-text font-semibold">Logótipo</span>
                                <span class="label-text-alt text-base-content/60">Opcional</span>
                            </label>
                            <input
                                type="file"
                                name="logotipo"
                                id="logoInput"
                                accept="image/*"
                                onchange="previewLogo(event)"
                                class="file-input file-input-bordered w-full @error('logotipo') file-input-error @enderror">
                            @error('logotipo')
                                {{-- Mensagem de validação para o ficheiro de logótipo. --}}
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Preview do logotipo selecionado --}}
                    {{-- Pré-visualização local do logótipo antes do envio. --}}
                    <div class="rounded-xl border border-dashed border-base-300 bg-base-100/60 p-4">
                        <p class="font-semibold mb-2 text-sm">Preview do logótipo</p>
                        <img id="logoPreview" class="w-24 h-24 rounded-full object-cover border shadow-sm" style="display:none" alt="Preview do logótipo selecionado">
                        <p id="logoPreviewPlaceholder" class="text-sm text-base-content/60">Sem imagem selecionada.</p>
                    </div>

                    <div class="pt-2 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                        {{-- Botão para cancelar e voltar, e botão para submeter o formulário --}}
                        <a href="{{ route('editoras.index') }}" class="btn btn-ghost">Cancelar</a>
                        <button class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white" type="submit">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Script responsável por mostrar preview do logotipo selecionado --}}
    <script>
        // Exibe a imagem escolhida no campo de upload antes do envio do formulário.
        function previewLogo(event) {
            const input = event.target;
            const preview = document.getElementById('logoPreview');
            const placeholder = document.getElementById('logoPreviewPlaceholder');

            // Garante que existe arquivo selecionado antes de gerar o preview.
            if (input.files && input.files[0]) {
                // Usa a imagem local apenas para visualização imediata na interface.
                preview.src = URL.createObjectURL(input.files[0]);
                preview.style.display = 'block';
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
            }
        }
    </script>
</x-app-layout>



