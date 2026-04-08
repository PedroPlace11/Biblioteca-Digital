<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto">
        {{-- Cabeçalho da página com título e data atual --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Requisição</h1>
                <p class="text-gray-500 mt-1">Escolha um livro para fazer requisição.</p>
            </div>
            <div class="text-sm text-gray-400">
                {{ now()->locale('pt_PT')->translatedFormat('d \d\e F \d\e Y') }}
            </div>
        </div>

        {{-- Bloco de dashboard com estatísticas, exibido diferente para admin e usuário comum --}}
        @if ($isAdmin)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                {{-- Total de requisições ativas --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Requisições Ativas</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalRequisicoesAtivas }}</p>
                </div>
                {{-- Total de requisições nos últimos 30 dias --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Requisições nos últimos 30 dias</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalRequisicoesUltimos30Dias }}</p>
                </div>
                {{-- Total de livros entregues hoje --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Livros entregues hoje</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalLivrosEntreguesHoje }}</p>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                {{-- Total de livros requisitados pelo usuário --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Livros que requisitou</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalLivrosRequisitadosPorMim }}</p>
                </div>
                {{-- Total de requisições feitas pelo usuário nos últimos 30 dias --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Requisições nos últimos 30 dias</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalRequisicoesUltimos30DiasPorMim }}</p>
                </div>
                {{-- Total de livros entregues pelo usuário --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Livros entregues</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalLivrosEntreguesPorMim }}</p>
                </div>
            </div>
        @endif

        {{-- Formulário de filtros para busca de livros por autor, editora, disponibilidade e ordenação --}}
        <form method="GET" action="{{ route('requisicoes.index') }}" class="mb-6 p-4 rounded-xl border border-gray-100 bg-gray-50/60">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                {{-- Filtro por autor --}}
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Autor</label>
                    <select name="autor_id" class="select select-bordered w-full bg-white">
                            <option value="">Todos os autores</option>
                            @foreach ($autores as $autor)
                                <option value="{{ $autor->id }}" {{ (string) $autorId === (string) $autor->id ? 'selected' : '' }}>
                                    {{ $autor->nome }}
                                </option>
                            @endforeach
                    </select>
                </div>

                {{-- Filtro por editora --}}
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Editora</label>
                    <select name="editora_id" class="select select-bordered w-full bg-white">
                            <option value="">Todas as editoras</option>
                            @foreach ($editoras as $editora)
                                <option value="{{ $editora->id }}" {{ (string) $editoraId === (string) $editora->id ? 'selected' : '' }}>
                                    {{ $editora->nome }}
                                </option>
                            @endforeach
                    </select>
                </div>

                {{-- Filtro por disponibilidade --}}
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Disponibilidade</label>
                    <select name="disponibilidade" class="select select-bordered w-full bg-white">
                            <option value="todas" {{ $disponibilidade === 'todas' ? 'selected' : '' }}>Todas</option>
                            <option value="disponivel" {{ $disponibilidade === 'disponivel' ? 'selected' : '' }}>Disponível</option>
                            <option value="indisponivel" {{ $disponibilidade === 'indisponivel' ? 'selected' : '' }}>Indisponível</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                {{-- Filtro de ordenação por campo --}}
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Ordenar por</label>
                    <select name="sort_by" class="select select-bordered w-full bg-white">
                            <option value="nome" {{ $sortBy === 'nome' ? 'selected' : '' }}>Livro</option>
                            <option value="autor" {{ $sortBy === 'autor' ? 'selected' : '' }}>Autor</option>
                            <option value="editora" {{ $sortBy === 'editora' ? 'selected' : '' }}>Editora</option>
                    </select>
                </div>

                {{-- Filtro de ordenação crescente/decrescente --}}
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Ordem</label>
                    <select name="sort_order" class="select select-bordered w-full bg-white">
                            <option value="asc" {{ $sortOrder === 'asc' ? 'selected' : '' }}>Crescente ↑</option>
                            <option value="desc" {{ $sortOrder === 'desc' ? 'selected' : '' }}>Decrescente ↓</option>
                    </select>
                </div>
            </div>

            <div class="mt-3 flex items-center gap-2">
                <button type="submit" class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white">Aplicar filtros</button>
                <a href="{{ route('requisicoes.index') }}" class="btn btn-outline">Limpar</a>
            </div>
        </form>

        {{-- Tabela de livros disponíveis para requisição --}}
        @if ($livros->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="mb-4 flex justify-end">
                    <button
                        type="button"
                        id="requisicoes-view-toggle"
                        data-view="list"
                        class="btn btn-ghost btn-md px-3 min-h-11 border-0 shadow-none hover:bg-gray-100"
                        aria-label="Alternar visualizacao entre lista e cards"
                        title="Alternar visualizacao"
                    >
                        <span id="requisicoes-view-toggle-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </span>
                    </button>
                </div>

                <div id="requisicoes-list-view">
                <div class="overflow-x-auto">
                    <table class="table w-full text-sm">
                        <thead>
                            <tr class="text-gray-400 text-xs uppercase tracking-wide border-b border-gray-100">
                                <th class="pb-3 font-medium text-left">Capa</th>
                                <th class="pb-3 font-medium text-left">Livro</th>
                                <th class="pb-3 font-medium text-left">Autor(es)</th>
                                <th class="pb-3 font-medium text-left">Editora</th>
                                <th class="pb-3 font-medium text-left">Estado</th>
                                <th class="pb-3 font-medium text-left">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($livros as $livro)
                                <tr class="hover:bg-gray-50 transition">
                                    {{-- Coluna da capa do livro ou placeholder --}}
                                    <td class="py-3">
                                        @if ($livro->imagem_capa)
                                            <img src="{{ asset($livro->imagem_capa) }}" class="w-14 h-20 object-cover rounded">
                                        @else
                                            <div class="w-14 h-20 bg-gray-100 rounded flex items-center justify-center text-xs text-gray-400">—</div>
                                        @endif
                                    </td>
                                    {{-- Coluna do nome do livro com link para detalhes --}}
                                    <td class="py-3">
                                        <a href="{{ route('livros.show', $livro) }}" class="font-semibold text-gray-900 hover:underline">{{ $livro->nome }}</a>
                                    </td>
                                    {{-- Coluna dos autores do livro --}}
                                    <td class="py-3 text-gray-700">
                                        @foreach ($livro->autores as $autor)
                                            @if (!$loop->first)
                                                <span class="text-gray-300">|</span>
                                            @endif
                                            <span>{{ $autor->nome }}</span>
                                        @endforeach
                                    </td>
                                    {{-- Coluna da editora --}}
                                    <td class="py-3 text-gray-600">{{ $livro->editora?->nome ?? '-' }}</td>
                                    {{-- Coluna do estado de disponibilidade do livro --}}
                                    <td class="py-3">
                                        @if (($livro->requisicoes_count ?? 0) > 0)
                                            <span class="badge badge-error badge-outline">Indisponível</span>
                                        @else
                                            <span class="badge badge-success badge-outline">Disponível</span>
                                        @endif
                                    </td>
                                    {{-- Coluna de ação para ver detalhes do livro --}}
                                    <td class="py-3">
                                         <a href="{{ route('livros.show', $livro) }}" class="btn btn-sm bg-black text-white border-black hover:bg-gray-900 hover:text-white">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                </div>

                <div id="requisicoes-cards-view" class="hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        @foreach ($livros as $livro)
                            @php
                                $indisponivel = ($livro->requisicoes_count ?? 0) > 0;
                            @endphp
                            <article class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-lg">
                                <div class="flex gap-4">
                                    <div class="shrink-0">
                                        @if ($livro->imagem_capa)
                                            <img src="{{ asset($livro->imagem_capa) }}" class="w-20 h-28 object-cover rounded-md border border-gray-100">
                                        @else
                                            <div class="w-20 h-28 bg-gray-100 rounded-md flex items-center justify-center text-xs text-gray-400">—</div>
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('livros.show', $livro) }}" class="text-xl leading-tight text-gray-900 font-semibold hover:underline line-clamp-2">
                                            {{ $livro->nome }}
                                        </a>
                                        <p class="text-sm text-gray-700 mt-1">
                                            @foreach ($livro->autores as $autor)
                                                {{ $autor->nome }}@if (!$loop->last), @endif
                                            @endforeach
                                        </p>
                                        <p class="text-sm text-gray-600 mt-2">{{ $livro->editora?->nome ?? '-' }}</p>
                                        <div class="mt-3">
                                            @if ($indisponivel)
                                                <span class="badge badge-error badge-outline">Indisponível</span>
                                            @else
                                                <span class="badge badge-success badge-outline">Disponível</span>
                                            @endif
                                        </div>
                                        <div class="mt-4">
                                            <a href="{{ route('livros.show', $livro) }}" class="btn btn-sm bg-black text-white border-black hover:bg-gray-900 hover:text-white">Ver</a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            {{-- Mensagem exibida quando nenhum livro é encontrado --}}
            <div class="text-center py-8 bg-white rounded-xl border border-gray-100">
                <p class="text-gray-500 text-lg">Nenhum livro encontrado.</p>
            </div>
        @endif
        {{-- Paginação customizada --}}
        @if ($livros->hasPages())
        <div class="pagination-custom mt-6">
            <div class="join grid grid-cols-2 w-56 mx-auto">
                @if ($livros->onFirstPage())
                    <button class="join-item btn bg-black text-white font-semibold w-full py-1 px-2 text-sm" disabled>Página anterior</button>
                @else
                    <a href="{{ $livros->previousPageUrl() }}" class="join-item btn bg-black text-white font-semibold w-full py-1 px-2 text-sm">Página anterior</a>
                @endif
                @if ($livros->hasMorePages())
                    <a href="{{ $livros->nextPageUrl() }}" class="join-item btn btn-outline font-semibold w-full py-1 px-2 text-sm">Próxima página</a>
                @else
                    <button class="join-item btn btn-outline font-semibold w-full py-1 px-2 text-sm" disabled>Próxima página</button>
                @endif
            </div>
        </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggleBtn = document.getElementById('requisicoes-view-toggle');
            var toggleIcon = document.getElementById('requisicoes-view-toggle-icon');
            var listView = document.getElementById('requisicoes-list-view');
            var cardsView = document.getElementById('requisicoes-cards-view');

            if (!toggleBtn || !toggleIcon || !listView || !cardsView) {
                return;
            }

            function iconFor(view) {
                if (view === 'cards') {
                    return '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>';
                }

                return '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z" /></svg>';
            }

            function setView(view) {
                if (view === 'cards') {
                    listView.classList.add('hidden');
                    cardsView.classList.remove('hidden');
                    toggleBtn.dataset.view = 'cards';
                    toggleBtn.setAttribute('aria-label', 'Mudar para modo lista');
                    toggleBtn.setAttribute('title', 'Mudar para modo lista');
                    toggleIcon.innerHTML = iconFor('cards');
                } else {
                    cardsView.classList.add('hidden');
                    listView.classList.remove('hidden');
                    toggleBtn.dataset.view = 'list';
                    toggleBtn.setAttribute('aria-label', 'Mudar para modo cards');
                    toggleBtn.setAttribute('title', 'Mudar para modo cards');
                    toggleIcon.innerHTML = iconFor('list');
                }

                window.localStorage.setItem('requisicoes_view_mode', view);
            }

            var savedView = window.localStorage.getItem('requisicoes_view_mode');
            setView(savedView === 'cards' ? 'cards' : 'list');

            toggleBtn.addEventListener('click', function () {
                var currentView = toggleBtn.dataset.view;
                setView(currentView === 'list' ? 'cards' : 'list');
            });
        });
    </script>
</x-app-layout>



