<x-app-layout>
    {{-- Container principal com largura controlada para formulário de criação. --}}
    <div class="p-6 max-w-5xl mx-auto">
        <div class="mb-6 text-left">
            {{-- Botão para voltar à listagem de autores. --}}
            <a href="{{ route('autores.index') }}" class="btn btn-outline text-xl px-4 py-2 min-h-0 h-auto leading-none" aria-label="Voltar aos Autores" title="Voltar">&larr;</a>
        </div>

        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-6 md:p-8">
                <div class="mb-6">
                    <h1 class="text-2xl md:text-3xl font-bold text-base-content">Criar Novo Autor</h1>
                    <p class="text-sm text-base-content/70 mt-2">Preencha os dados para registar um novo autor no sistema.</p>
                </div>

                {{-- Alerta exibido quando existem erros de validação no formulário. --}}
                @if ($errors->any())
                    <div class="alert alert-error mb-6">
                        <span>Existem campos por corrigir. Verifique o formulário antes de guardar.</span>
                    </div>
                @endif

                {{-- Formulário principal para cadastro de autor. --}}
                <form method="POST" action="{{ route('autores.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        {{-- Campo obrigatório: nome do autor. --}}
                        <div class="form-control lg:col-span-2">
                            <label class="label">
                                <span class="label-text font-semibold">Nome do Autor</span>
                            </label>
                            <input
                                type="text"
                                name="nome"
                                value="{{ old('nome') }}"
                                placeholder="Nome completo do autor"
                                class="input input-bordered w-full @error('nome') input-error @enderror"
                                required>
                            @error('nome')
                                {{-- Mensagem específica devolvida pela validação do campo nome. --}}
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-control lg:col-span-2">
                            <label class="label">
                                <span class="label-text font-semibold">Biografia</span>
                            </label>
                            {{-- Campo opcional: biografia do autor. --}}
                            <textarea
                                name="bibliografia"
                                rows="6"
                                placeholder="Escreva a biografia do autor..."
                                class="textarea textarea-bordered w-full @error('bibliografia') textarea-error @enderror">{{ old('bibliografia') }}</textarea>
                            @error('bibliografia')
                                {{-- Mensagem de erro para tamanho/formato da biografia. --}}
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-control lg:col-span-2">
                            <label class="label">
                                <span class="label-text font-semibold">Foto do Autor</span>
                                <span class="label-text-alt text-base-content/60">Opcional</span>
                            </label>
                            {{-- Upload opcional de foto do autor. --}}
                            <input
                                type="file"
                                name="foto"
                                accept="image/*"
                                class="file-input file-input-bordered w-full @error('foto') file-input-error @enderror">
                            @error('foto')
                                {{-- Feedback para ficheiro inválido (tipo/tamanho). --}}
                                <span class="text-error text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-2 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                        {{-- Botão para cancelar e voltar à listagem, e botão para submeter o formulário. --}}
                        <a href="{{ route('autores.index') }}" class="btn btn-ghost">Cancelar</a>
                        <button class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white" type="submit">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>



