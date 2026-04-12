<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto">
        {{-- Verifica se o usuário autenticado é admin para liberar ações administrativas --}}
        @php
            $isAdmin = auth()->check() && auth()->user()->role == 'admin';
        @endphp

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-8">
            {{-- Cabeçalho da página de autores --}}
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Autores</h1>
                <p class="text-gray-500 mt-1">Explore autores, biografias e obras associadas.</p>
            </div>
                @auth
                    {{-- Exibe a data atual formatada em português --}}
                    <div class="text-sm text-gray-400">
                        {{ now()->locale('pt_PT')->translatedFormat('d \\d\\e F \\d\\e Y') }}
                    </div>
                @endauth
        </div>

        {{-- Botão para criar novo autor, visível apenas para admin --}}
        @if ($isAdmin)
            <div class="mb-6">
                <a href="{{ route('autores.create') }}" class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white">Novo Autor</a>
            </div>
        @endif

        {{-- Filtros de pesquisa e ordenação de autores --}}
        <form method="GET" action="{{ route('autores.index') }}" class="mb-6 p-4 rounded-xl border border-gray-100 bg-gray-50/60">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-3">
                    <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Pesquisa</label>
                    <input type="text" name="search" placeholder="Pesquisar por nome do autor" value="{{ $search }}" class="input input-bordered w-full bg-white">
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Ordem</label>
                    <select name="sort_order" class="select select-bordered w-full bg-white">
                        <option value="asc" {{ $sortOrder === 'asc' ? 'selected' : '' }}>A-Z (Crescente ↑)</option>
                        <option value="desc" {{ $sortOrder === 'desc' ? 'selected' : '' }}>Z-A (Decrescente ↓)</option>
                    </select>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                <button type="submit" class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white">Aplicar filtros</button>
                <a href="{{ route('autores.index') }}" class="btn btn-outline">Limpar</a>
            </div>
        </form>

        {{-- Exibe a tabela de autores se houver resultados --}}
        @if ($autores->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="mb-4 flex justify-end">
                    {{-- Botão alterna entre visualização em tabela e cartões. --}}
                    <button
                        type="button"
                        id="autores-view-toggle"
                        data-view="list"
                        class="btn btn-ghost btn-md px-3 min-h-11 border-0 shadow-none hover:bg-gray-100"
                        aria-label="Alternar visualizacao entre lista e cards"
                        title="Alternar visualizacao"
                    >
                        <span id="autores-view-toggle-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </span>
                    </button>
                </div>

                <div id="autores-list-view">
                {{-- Vista em lista/tabular para leitura rápida de muitos autores. --}}
                <div class="overflow-x-auto">
                    <table class="table w-full text-sm">
                        <thead>
                            {{-- Cabeçalho da tabela de autores --}}
                            <tr class="text-gray-400 text-xs uppercase tracking-wide border-b border-gray-100">
                                <th class="pb-3 font-medium text-left">Foto</th>
                                <th class="pb-3 font-medium text-left">Autor</th>
                                <th class="pb-3 font-medium text-left">Livros</th>
                                @if ($isAdmin)
                                    <th class="pb-3 font-medium text-left">Ação</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            {{-- Itera sobre cada autor e exibe suas informações --}}

                            @foreach ($autores as $autor)
                                <tr class="hover:bg-gray-50 transition">
                                    {{-- Foto do autor ou inicial do nome se não houver foto --}}
                                    <td class="py-3">
                                        @if ($autor->foto)
                                            <img src="{{ asset($autor->foto) }}" class="w-12 h-12 rounded-full object-cover border border-gray-200">
                                        @else
                                            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 font-semibold">
                                                {{ strtoupper(substr($autor->nome, 0, 1)) }}
                                            </div>
                                        @endif
                                    </td>
                                    {{-- Nome do autor e quantidade de livros associados --}}
                                    <td class="py-3">
                                        <a href="{{ route('autores.show', $autor) }}" class="font-semibold text-gray-900 hover:underline">{{ $autor->nome }}</a>
                                        <p class="text-xs text-gray-400 mt-1">{{ $autor->livros->count() }} livro(s) associado(s)</p>
                                    </td>
                                    {{-- Lista até 4 livros do autor, com link, e indica se há mais --}}
                                    <td class="py-3 text-gray-700">
                                        @if ($autor->livros->count() > 0)
                                            @foreach ($autor->livros->take(4) as $livro)
                                                @if (!$loop->first)
                                                    <span class="text-gray-300">|</span>
                                                @endif
                                                <a href="{{ route('livros.show', $livro) }}" class="hover:underline">{{ $livro->nome }}</a>
                                            @endforeach
                                            @if ($autor->livros->count() > 4)
                                                <span class="text-xs text-gray-400">+{{ $autor->livros->count() - 4 }}</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">Sem livros vinculados</span>
                                        @endif
                                    </td>
                                    {{-- Ações administrativas: editar autor (apenas para admin) --}}
                                    @if ($isAdmin)
                                        <td class="py-3">
                                            <a href="{{ route('autores.edit', $autor) }}" class="btn btn-sm btn-outline">Editar</a>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                </div>

                <div id="autores-cards-view" class="hidden">
                    {{-- Vista em cartões para navegação mais visual por autor. --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        @foreach ($autores as $autor)
                            <article class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-lg">
                                <div class="flex gap-4">
                                    <div class="shrink-0">
                                        @if ($autor->foto)
                                            <img src="{{ asset($autor->foto) }}" class="w-16 h-16 rounded-full object-cover border border-gray-200">
                                        @else
                                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 font-semibold text-lg">
                                                {{ strtoupper(substr($autor->nome, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('autores.show', $autor) }}" class="text-xl leading-tight text-gray-900 font-semibold hover:underline line-clamp-2">
                                            {{ $autor->nome }}
                                        </a>
                                        <p class="text-sm text-gray-500 mt-1">{{ $autor->livros->count() }} livro(s) associado(s)</p>

                                        <div class="mt-3 text-sm text-gray-700">
                                            {{-- Repete regra de até 4 livros também no modo cards. --}}
                                            @if ($autor->livros->count() > 0)
                                                @foreach ($autor->livros->take(4) as $livro)
                                                    @if (!$loop->first)
                                                        <span class="text-gray-300">|</span>
                                                    @endif
                                                    <a href="{{ route('livros.show', $livro) }}" class="hover:underline">{{ $livro->nome }}</a>
                                                @endforeach
                                                @if ($autor->livros->count() > 4)
                                                    <span class="text-xs text-gray-400">+{{ $autor->livros->count() - 4 }}</span>
                                                @endif
                                            @else
                                                <span class="text-gray-400">Sem livros vinculados</span>
                                            @endif
                                        </div>

                                        @if ($isAdmin)
                                            <div class="mt-4">
                                                <a href="{{ route('autores.edit', $autor) }}" class="btn btn-sm btn-outline">Editar</a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        {{-- Caso não haja autores, exibe mensagem amigável --}}
        @else
            <div class="text-center py-8 bg-white rounded-xl border border-gray-100">
                <p class="text-gray-500 text-lg">Nenhum autor encontrado.</p>
            </div>
        @endif
        {{-- Paginação customizada --}}
        @if ($autores->hasPages())
        <div class="pagination-custom mt-6">
            <div class="join grid grid-cols-2 w-56 mx-auto">
                @if ($autores->onFirstPage())
                    <button class="join-item btn bg-black text-white font-semibold w-full py-1 px-2 text-sm" disabled>Página anterior</button>
                @else
                    <a href="{{ $autores->previousPageUrl() }}" class="join-item btn bg-black text-white font-semibold w-full py-1 px-2 text-sm">Página anterior</a>
                @endif
                @if ($autores->hasMorePages())
                    <a href="{{ $autores->nextPageUrl() }}" class="join-item btn btn-outline font-semibold w-full py-1 px-2 text-sm">Próxima página</a>
                @else
                    <button class="join-item btn btn-outline font-semibold w-full py-1 px-2 text-sm" disabled>Próxima página</button>
                @endif
            </div>
        </div>
        @endif
    </div>

    <script>
        // Controla o modo de visualização da listagem e persiste preferência no navegador.
        document.addEventListener('DOMContentLoaded', function () {
            var toggleBtn = document.getElementById('autores-view-toggle');
            var toggleIcon = document.getElementById('autores-view-toggle-icon');
            var listView = document.getElementById('autores-list-view');
            var cardsView = document.getElementById('autores-cards-view');

            if (!toggleBtn || !toggleIcon || !listView || !cardsView) {
                return;
            }

            // Retorna SVG do botão conforme a vista que ficará disponível no próximo clique.
            function iconFor(view) {
                if (view === 'cards') {
                    return '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>';
                }

                return '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z" /></svg>';
            }

            // Aplica classes/atributos de acessibilidade e guarda escolha no localStorage.
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

                window.localStorage.setItem('autores_view_mode', view);
            }

            var savedView = window.localStorage.getItem('autores_view_mode');
            // Inicializa com preferência anterior; padrão é modo lista.
            setView(savedView === 'cards' ? 'cards' : 'list');

            // Alterna entre vistas mantendo o mesmo conjunto de dados carregado.
            toggleBtn.addEventListener('click', function () {
                var currentView = toggleBtn.dataset.view;
                setView(currentView === 'list' ? 'cards' : 'list');
            });
        });
    </script>
</x-app-layout>



