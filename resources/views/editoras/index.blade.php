<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto">
        {{-- Verifica se o usuário autenticado é admin para liberar ações administrativas --}}
        @php
            $isAdmin = auth()->check() && auth()->user()->role == 'admin';
        @endphp

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-8">
            {{-- Cabeçalho da página de editoras --}}
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Editoras</h1>
                <p class="text-gray-500 mt-1">Veja editoras, logótipos e os livros publicados por cada uma.</p>
            </div>
            {{-- Exibe a data atual formatada em português --}}
                @auth
                    <div class="text-sm text-gray-400">
                        {{ now()->locale('pt_PT')->translatedFormat('d \\d\\e F \\d\\e Y') }}
                    </div>
                @endauth
        </div>

        {{-- Botão para criar nova editora, visível apenas para admin --}}
        @if ($isAdmin)
            <div class="mb-6">
                <a href="{{ route('editoras.create') }}" class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white">Nova Editora</a>
            </div>
        @endif

        {{-- Filtros de pesquisa e ordenação de editoras --}}
        <form method="GET" action="{{ route('editoras.index') }}" class="mb-6 p-4 rounded-xl border border-gray-100 bg-gray-50/60">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-3">
                    <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Pesquisa</label>
                    <input type="text" name="search" placeholder="Pesquisar por nome da editora" value="{{ $search }}" class="input input-bordered w-full bg-white">
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
                <a href="{{ route('editoras.index') }}" class="btn btn-outline">Limpar</a>
            </div>
        </form>

        {{-- Exibe a tabela de editoras se houver resultados --}}
        @if ($editoras->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="mb-4 flex justify-end">
                    <button
                        type="button"
                        id="editoras-view-toggle"
                        data-view="list"
                        class="btn btn-ghost btn-md px-3 min-h-11 border-0 shadow-none hover:bg-gray-100"
                        aria-label="Alternar visualizacao entre lista e cards"
                        title="Alternar visualizacao"
                    >
                        <span id="editoras-view-toggle-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </span>
                    </button>
                </div>

                <div id="editoras-list-view">
                <div class="overflow-x-auto">
                    <table class="table w-full text-sm">
                        <thead>
                            {{-- Cabeçalho da tabela de editoras --}}
                            <tr class="text-gray-400 text-xs uppercase tracking-wide border-b border-gray-100">
                                <th class="pb-3 font-medium text-left">Logótipo</th>
                                <th class="pb-3 font-medium text-left">Editora</th>
                                <th class="pb-3 font-medium text-left">Livros publicados</th>
                                @if ($isAdmin)
                                    <th class="pb-3 font-medium text-left">Ação</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            {{-- Itera sobre cada editora e exibe suas informações --}}

                            @foreach ($editoras as $editora)
                                <tr class="hover:bg-gray-50 transition">
                                    {{-- Logotipo da editora ou inicial do nome se não houver logotipo --}}
                                    <td class="py-3">
                                        @if ($editora->logotipo)
                                            <img src="{{ asset($editora->logotipo) }}" class="w-14 h-14 object-contain rounded border border-gray-200 bg-white p-1">
                                        @else
                                            <div class="w-14 h-14 rounded bg-gray-100 flex items-center justify-center text-gray-500 font-semibold">
                                                {{ strtoupper(substr($editora->nome, 0, 1)) }}
                                            </div>
                                        @endif
                                    </td>
                                    {{-- Nome da editora e quantidade de livros publicados --}}
                                    <td class="py-3">
                                        <a href="{{ route('editoras.show', $editora) }}" class="font-semibold text-gray-900 hover:underline">{{ $editora->nome }}</a>
                                        <p class="text-xs text-gray-400 mt-1">{{ $editora->livros->count() }} livro(s) publicado(s)</p>
                                    </td>
                                    {{-- Lista até 4 livros publicados, com link, e indica se há mais --}}
                                    <td class="py-3 text-gray-700">
                                        @if ($editora->livros->count() > 0)
                                            @foreach ($editora->livros->take(4) as $livro)
                                                @if (!$loop->first)
                                                    <span class="text-gray-300">|</span>
                                                @endif
                                                <a href="{{ route('livros.show', $livro) }}" class="hover:underline">{{ $livro->nome }}</a>
                                            @endforeach
                                            @if ($editora->livros->count() > 4)
                                                <span class="text-xs text-gray-400">+{{ $editora->livros->count() - 4 }}</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">Sem livros vinculados</span>
                                        @endif
                                    </td>
                                    {{-- Ações administrativas: editar editora (apenas para admin) --}}
                                    @if ($isAdmin)
                                        <td class="py-3">
                                            <a href="{{ route('editoras.edit', $editora) }}" class="btn btn-sm btn-outline">Editar</a>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                </div>

                <div id="editoras-cards-view" class="hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        @foreach ($editoras as $editora)
                            <article class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-lg">
                                <div class="flex gap-4">
                                    <div class="shrink-0">
                                        @if ($editora->logotipo)
                                            <img src="{{ asset($editora->logotipo) }}" class="w-16 h-16 object-contain rounded-md border border-gray-200 bg-white p-1">
                                        @else
                                            <div class="w-16 h-16 rounded-md bg-gray-100 flex items-center justify-center text-gray-500 font-semibold text-lg">
                                                {{ strtoupper(substr($editora->nome, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('editoras.show', $editora) }}" class="text-xl leading-tight text-gray-900 font-semibold hover:underline line-clamp-2">
                                            {{ $editora->nome }}
                                        </a>
                                        <p class="text-sm text-gray-500 mt-1">{{ $editora->livros->count() }} livro(s) publicado(s)</p>

                                        <div class="mt-3 text-sm text-gray-700">
                                            @if ($editora->livros->count() > 0)
                                                @foreach ($editora->livros->take(4) as $livro)
                                                    @if (!$loop->first)
                                                        <span class="text-gray-300">|</span>
                                                    @endif
                                                    <a href="{{ route('livros.show', $livro) }}" class="hover:underline">{{ $livro->nome }}</a>
                                                @endforeach
                                                @if ($editora->livros->count() > 4)
                                                    <span class="text-xs text-gray-400">+{{ $editora->livros->count() - 4 }}</span>
                                                @endif
                                            @else
                                                <span class="text-gray-400">Sem livros vinculados</span>
                                            @endif
                                        </div>

                                        @if ($isAdmin)
                                            <div class="mt-4">
                                                <a href="{{ route('editoras.edit', $editora) }}" class="btn btn-sm btn-outline">Editar</a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        {{-- Caso não haja editoras, exibe mensagem amigável --}}
        @else
            <div class="text-center py-8 bg-white rounded-xl border border-gray-100">
                <p class="text-gray-500 text-lg">Nenhuma editora encontrada.</p>
            </div>
        @endif
        {{-- Paginação customizada --}}
        @if ($editoras->hasPages())
        <div class="pagination-custom mt-6">
            <div class="join grid grid-cols-2 w-56 mx-auto">
                @if ($editoras->onFirstPage())
                    <button class="join-item btn bg-black text-white font-semibold w-full py-1 px-2 text-sm" disabled>Página anterior</button>
                @else
                    <a href="{{ $editoras->previousPageUrl() }}" class="join-item btn bg-black text-white font-semibold w-full py-1 px-2 text-sm">Página anterior</a>
                @endif
                @if ($editoras->hasMorePages())
                    <a href="{{ $editoras->nextPageUrl() }}" class="join-item btn btn-outline font-semibold w-full py-1 px-2 text-sm">Próxima página</a>
                @else
                    <button class="join-item btn btn-outline font-semibold w-full py-1 px-2 text-sm" disabled>Próxima página</button>
                @endif
            </div>
        </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggleBtn = document.getElementById('editoras-view-toggle');
            var toggleIcon = document.getElementById('editoras-view-toggle-icon');
            var listView = document.getElementById('editoras-list-view');
            var cardsView = document.getElementById('editoras-cards-view');

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

                window.localStorage.setItem('editoras_view_mode', view);
            }

            var savedView = window.localStorage.getItem('editoras_view_mode');
            setView(savedView === 'cards' ? 'cards' : 'list');

            toggleBtn.addEventListener('click', function () {
                var currentView = toggleBtn.dataset.view;
                setView(currentView === 'list' ? 'cards' : 'list');
            });
        });
    </script>
</x-app-layout>



