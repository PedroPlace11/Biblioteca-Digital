<x-app-layout>
    {{-- Área de gestão de moradas com listagem e formulário no mesmo ecrã. --}}
    <div class="relative overflow-hidden py-8">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-br from-slate-50 via-white to-sky-50"></div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white/90 shadow-sm backdrop-blur p-6 sm:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Gerir Conta</p>
                <h1 class="mt-2 text-3xl sm:text-4xl font-semibold text-slate-900">Gerir Moradas</h1>
                <p class="mt-2 text-slate-600">Guarda várias moradas e escolhe rapidamente no checkout.</p>
            </section>

            @if ($errors->any())
                {{-- Lista erros de validação devolvidos pelo backend. --}}
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900 shadow-sm">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <section class="lg:col-span-2 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Moradas guardadas</h2>
                    <p class="mt-1 text-sm text-slate-500">Estas moradas ficam disponíveis para selecionar no checkout.</p>

                    <div class="mt-5 space-y-4">
                        @forelse ($moradas as $morada)
                            {{-- Cartão individual de morada guardada para edição/remoção. --}}
                            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $morada->titulo ?: 'Sem título' }}</p>
                                        <p class="font-semibold text-slate-900">{{ $morada->nome_destinatario }}</p>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('cidadao.moradas.edit', $morada) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">
                                            Editar
                                        </a>

                                        <form method="POST" action="{{ route('cidadao.moradas.destroy', $morada) }}">
                                            @csrf
                                            @method('DELETE')
                                            {{-- Remove a morada selecionada da conta do utilizador. --}}
                                            <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">
                                                Apagar
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <p class="text-sm text-slate-600 mt-1">{{ $morada->telemovel_destinatario }}</p>
                                <p class="text-sm text-slate-700 mt-2">{{ $morada->morada_linha_1 }}</p>
                                @if ($morada->morada_linha_2)
                                    <p class="text-sm text-slate-700">{{ $morada->morada_linha_2 }}</p>
                                @endif
                                <p class="text-sm text-slate-700">{{ $morada->codigo_postal }} {{ $morada->cidade }} ({{ $morada->pais }})</p>
                            </article>
                        @empty
                            {{-- Estado vazio para utilizadores sem moradas registadas. --}}
                            <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500">
                                Ainda não tens moradas guardadas.
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    @php
                        // Define modo do formulário (criação ou edição) com base na morada selecionada.
                        $modoEdicao = (bool) $moradaEmEdicao;
                        $moradaFormulario = $moradaEmEdicao;
                    @endphp

                    <h2 class="text-lg font-semibold text-slate-900">{{ $modoEdicao ? 'Editar morada' : 'Adicionar nova morada' }}</h2>

                    <form method="POST" action="{{ $modoEdicao ? route('cidadao.moradas.update', $moradaFormulario) : route('cidadao.moradas.store') }}" class="mt-5 space-y-3">
                        @csrf
                        @if ($modoEdicao)
                            {{-- Em edição, usa PATCH para atualizar a morada existente. --}}
                            @method('PATCH')
                        @endif
                        <input type="hidden" name="form_mode" value="{{ $modoEdicao ? 'update' : 'store' }}">
                        <input type="hidden" name="morada_id" value="{{ old('morada_id', $moradaFormulario?->id) }}">

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Título da morada</label>
                            <input type="text" name="titulo" class="input input-bordered w-full bg-white" value="{{ old('titulo', $moradaFormulario?->titulo) }}" placeholder="Ex.: Casa, Trabalho, Pais" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nome do destinatário</label>
                            <input type="text" name="nome_destinatario" class="input input-bordered w-full bg-white" value="{{ old('nome_destinatario', $moradaFormulario?->nome_destinatario ?? auth()->user()->name) }}" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Telemóvel</label>
                            <input type="text" name="telemovel_destinatario" class="input input-bordered w-full bg-white" value="{{ old('telemovel_destinatario', $moradaFormulario?->telemovel_destinatario) }}" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Morada (linha 1)</label>
                            <input type="text" name="morada_linha_1" class="input input-bordered w-full bg-white" value="{{ old('morada_linha_1', $moradaFormulario?->morada_linha_1) }}" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Morada (linha 2)</label>
                            <input type="text" name="morada_linha_2" class="input input-bordered w-full bg-white" value="{{ old('morada_linha_2', $moradaFormulario?->morada_linha_2) }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Código postal</label>
                            <input type="text" name="codigo_postal" class="input input-bordered w-full bg-white" value="{{ old('codigo_postal', $moradaFormulario?->codigo_postal) }}" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Cidade</label>
                            <input type="text" name="cidade" class="input input-bordered w-full bg-white" value="{{ old('cidade', $moradaFormulario?->cidade) }}" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">País (ISO)</label>
                            <input type="text" name="pais" maxlength="2" class="input input-bordered w-full bg-white uppercase" value="{{ old('pais', $moradaFormulario?->pais ?? 'PT') }}" required>
                        </div>

                        <div class="space-y-2">
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-black px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                                {{ $modoEdicao ? 'Guardar alterações' : 'Guardar morada' }}
                            </button>

                            @if ($modoEdicao)
                                {{-- Permite sair do modo edição e voltar ao estado padrão. --}}
                                <a href="{{ route('cidadao.moradas.index') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                    Cancelar edição
                                </a>
                            @endif
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
