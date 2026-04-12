<x-app-layout>
    {{-- Lista principal do catálogo com filtros, vistas e ações administrativas. --}}
    {{-- Popup de aviso exibido quando há mensagem de informação na sessão --}}
    @if (session('popup_info'))
        <div id="livros-popup-info" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/45"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-gray-900">Aviso</h3>
                <p class="mt-2 text-sm text-gray-600">{{ session('popup_info') }}</p>
                <div class="mt-5 flex justify-end">
                    <button type="button" id="livros-popup-info-close" class="btn btn-outline">OK</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Popup de sucesso exibido quando há mensagem de sucesso na sessão --}}
    @if (session('popup_success'))
        <div id="livros-popup-success" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/45"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-gray-900">Sucesso</h3>
                <p class="mt-2 text-sm text-gray-600">{{ session('popup_success') }}</p>
                <div class="mt-5 flex justify-end">
                    <button type="button" id="livros-popup-success-close" class="btn btn-outline">OK</button>
                </div>
            </div>
        </div>
    @endif

    <div class="p-6 max-w-7xl mx-auto">
        {{-- Verifica se o usuário autenticado é admin para liberar ações administrativas --}}
        @php
            $isAdmin = auth()->check() && auth()->user()->role == 'admin';
        @endphp

        {{-- Alerta de sucesso exibido após operações bem-sucedidas --}}
        @if (session('success'))
            <div class="alert alert-success mb-4">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Alerta de informação exibido após operações informativas --}}
        @if (session('info'))
            <div class="alert alert-info mb-4">
                <span>{{ session('info') }}</span>
            </div>
        @endif

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-8">
            {{-- Cabeçalho da página de livros --}}
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Livros</h1>
                <p class="text-gray-500 mt-1">Consulte o catálogo e encontre rapidamente o livro pretendido.</p>
            </div>
            {{-- Exibe a data atual formatada em português --}}
            @auth
            <div class="text-sm text-gray-400">
                {{ now()->locale('pt_PT')->translatedFormat('d \d\e F \d\e Y') }}
            </div>
            @endauth
        </div>

        {{-- Botões de ações administrativas, visíveis apenas para admin --}}
        @if ($isAdmin)
            <div class="flex flex-wrap gap-2 mb-6">
                {{-- Ações rápidas disponíveis apenas para utilizadores administradores. --}}
                <a href="{{ route('livros.create') }}" class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white">Novo Livro</a>
                <a href="{{ route('livros.export') }}" class="btn btn-outline">Exportar para Excel</a>
                <a href="{{ route('livros.googlebooks') }}" class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white">Buscar na Google Books</a>
            </div>
        @endif

        {{-- Filtros de pesquisa e ordenação de livros --}}
        <form method="GET" action="{{ route('livros.index') }}" class="mb-6 p-4 rounded-xl border border-gray-100 bg-gray-50/60">
            {{-- Filtros de pesquisa e ordenação aplicados à listagem. --}}
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div class="md:col-span-3">
                    <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Pesquisa</label>
                    <input
                        type="text"
                        name="search"
                        placeholder="Pesquisar por nome, autor, editora, ISBN ou bibliografia"
                        value="{{ $search }}"
                        class="input input-bordered w-full bg-white"
                    >
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Ordenar por</label>
                    <select name="sort_by" class="select select-bordered w-full bg-white">
                        <option value="nome" {{ $sortBy === 'nome' ? 'selected' : '' }}>Livro</option>
                        <option value="editora" {{ $sortBy === 'editora' ? 'selected' : '' }}>Editora</option>
                        <option value="autor" {{ $sortBy === 'autor' ? 'selected' : '' }}>Autor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Ordem</label>
                    <select name="sort_order" class="select select-bordered w-full bg-white">
                        <option value="asc" {{ $sortOrder === 'asc' ? 'selected' : '' }}>Crescente ↑</option>
                        <option value="desc" {{ $sortOrder === 'desc' ? 'selected' : '' }}>Decrescente ↓</option>
                    </select>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                <button type="submit" class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white">Aplicar filtros</button>
                <a href="{{ route('livros.index') }}" class="btn btn-outline">Limpar</a>
            </div>
        </form>

        {{-- Exibe a tabela de livros se houver resultados --}}
        @if ($livros->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="mb-4 flex justify-end">
                    {{-- Botão alterna entre tabela e cartões sem recarregar a página. --}}
                    <button
                        type="button"
                        id="livros-view-toggle"
                        data-view="list"
                        class="btn btn-ghost btn-md px-3 min-h-11 border-0 shadow-none hover:bg-gray-100"
                        aria-label="Alternar visualizacao entre lista e cards"
                        title="Alternar visualizacao"
                    >
                        <span id="livros-view-toggle-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </span>
                    </button>
                </div>

                <div id="livros-list-view">
                {{-- Vista em tabela para análise rápida de muitos livros. --}}
                <div class="overflow-x-auto">
                    <table class="table w-full text-sm">
                        <thead>
                            {{-- Cabeçalho da tabela de livros --}}
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
                            {{-- Itera sobre cada livro e exibe suas informações --}}

                            @foreach ($livros as $livro)
                                @php
                                    // Calcula se o livro está indisponível com base nas requisições ativas.
                                    $indisponivel = ($livro->requisicoes_count ?? 0) > 0;
                                @endphp
                                <tr class="hover:bg-gray-50 transition">
                                    {{-- Exibe a capa do livro ou um placeholder se não houver --}}
                                    <td class="py-3">
                                        @if ($livro->imagem_capa)
                                            <img src="{{ asset($livro->imagem_capa) }}" class="w-14 h-20 object-cover rounded">
                                        @else
                                            <div class="w-14 h-20 bg-gray-100 rounded flex items-center justify-center text-xs text-gray-400">—</div>
                                        @endif
                                    </td>
                                    {{-- Nome do livro, ISBN e preço --}}
                                    <td class="py-3">
                                        <a href="{{ route('livros.show', $livro) }}" class="text-gray-900 font-semibold hover:underline">
                                            {{ $livro->nome }}
                                        </a>
                                        <p class="text-xs text-gray-400 mt-1">ISBN: {{ $livro->isbn ?: '-' }}</p>
                                        <p class="text-xs text-gray-400">Preço:
                                            @if (!is_null($livro->preco))
                                                {{ number_format((float) $livro->preco, 2, ',', '.') }} &euro;
                                            @else
                                                -
                                            @endif
                                        </p>
                                    </td>
                                    {{-- Lista de autores do livro --}}
                                    <td class="py-3 text-gray-700">
                                        @foreach ($livro->autores as $autor)
                                            @if (!$loop->first)
                                                <span class="text-gray-300">|</span>
                                            @endif
                                            <a href="{{ route('autores.show', $autor) }}" class="hover:underline">{{ $autor->nome }}</a>
                                        @endforeach
                                    </td>
                                    {{-- Nome da editora --}}
                                    <td class="py-3 text-gray-600">{{ $livro->editora?->nome ?? '-' }}</td>
                                    {{-- Estado de disponibilidade do livro --}}
                                    <td class="py-3">
                                        @if ($indisponivel)
                                            <span class="badge badge-error badge-outline">Indisponível</span>
                                        @else
                                            <span class="badge badge-success badge-outline">Disponível</span>
                                        @endif
                                    </td>
                                    {{-- Ações disponíveis para o livro: ver, requisitar, entrar --}}
                                    <td class="py-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <div class="flex flex-row gap-2">
                                                <a href="{{ route('livros.show', $livro) }}" class="btn btn-sm bg-black text-white border-black hover:bg-gray-900 hover:text-white">Ver</a>
                                                @if (auth()->check() && !$indisponivel)
                                                    <form action="{{ route('livros.requisitar', $livro) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline">Requisitar</button>
                                                    </form>
                                                @endif
                                            </div>
                                            @guest
                                                <a href="{{ route('login') }}" class="btn btn-sm btn-outline">Entrar</a>
                                            @endguest
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                </div>

                <div id="livros-cards-view" class="hidden">
                    {{-- Vista alternativa em cartões com ações contextuais. --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        @foreach ($livros as $livro)
                            @php
                                // Reutiliza a mesma regra de indisponibilidade da vista em tabela.
                                $indisponivel = ($livro->requisicoes_count ?? 0) > 0;
                            @endphp
                            <article class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-lg animate-[fadeIn_.35s_ease-out_both]" style="animation-delay: {{ $loop->index * 60 }}ms;">
                                <div class="flex gap-4">
                                    <div class="group/capa relative shrink-0">
                                        @if ($livro->imagem_capa)
                                            <img src="{{ asset($livro->imagem_capa) }}" class="w-28 h-40 object-cover rounded-md border border-gray-100">
                                        @else
                                            <div class="w-28 h-40 bg-gray-100 rounded-md flex items-center justify-center text-xs text-gray-400">—</div>
                                        @endif

                                        @if (!$indisponivel)
                                            <div class="absolute inset-0 rounded-md bg-black/55 p-2 flex flex-col justify-end gap-2 transition-all duration-300 md:opacity-0 md:translate-y-2 md:pointer-events-none md:group-hover/capa:opacity-100 md:group-hover/capa:translate-y-0 md:group-hover/capa:pointer-events-auto md:focus-within:opacity-100 md:focus-within:translate-y-0 md:focus-within:pointer-events-auto">
                                                @auth
                                                    <form action="{{ route('carrinho.adicionar', $livro) }}" method="POST" class="w-full">
                                                        @csrf
                                                        <button type="submit" class="btn btn-xs w-full bg-black text-white border-black hover:bg-gray-900 hover:text-white">Comprar</button>
                                                    </form>
                                                    <form action="{{ route('livros.requisitar', $livro) }}" method="POST" class="w-full">
                                                        @csrf
                                                        <button type="submit" class="btn btn-xs w-full btn-outline border-white text-white hover:bg-white hover:text-black">Requisitar</button>
                                                    </form>
                                                @else
                                                    <a href="{{ route('login') }}" class="btn btn-xs w-full btn-outline border-white text-white hover:bg-white hover:text-black">Entrar para comprar</a>
                                                @endauth
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1 flex flex-col">
                                        <a href="{{ route('livros.show', $livro) }}" class="text-2xl leading-tight text-gray-900 font-semibold hover:underline line-clamp-2">
                                            {{ $livro->nome }}
                                        </a>
                                        <p class="text-base text-gray-700 mt-1 line-clamp-1">
                                            @foreach ($livro->autores as $autor)
                                                {{ $autor->nome }}@if (!$loop->last), @endif
                                            @endforeach
                                        </p>
                                        <p class="text-xs uppercase tracking-wide text-gray-500 mt-3">Portes grátis</p>
                                        <p class="mt-1 text-3xl font-bold text-black">
                                            @if (!is_null($livro->preco))
                                                {{ number_format((float) $livro->preco, 2, ',', '.') }} &euro;
                                            @else
                                                -
                                            @endif
                                        </p>

                                        <div class="mt-3 flex items-center gap-2">
                                            <span class="text-xs text-gray-500">ISBN: {{ $livro->isbn ?: '-' }}</span>
                                            @if ($indisponivel)
                                                <span class="badge badge-error badge-outline">Indisponível</span>
                                            @else
                                                <span class="badge badge-success badge-outline">Disponível</span>
                                            @endif
                                        </div>

                                        <p class="text-sm text-gray-600 mt-2">{{ $livro->editora?->nome ?? '-' }}</p>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>

                {{-- Paginação --}}
                <div class="pagination-custom mt-6">
                    <div class="join grid grid-cols-2 w-56 mx-auto">
                        {{-- Botão página anterior --}}
                        @if ($livros->onFirstPage())
                            <button class="join-item btn bg-black text-white font-semibold w-full py-1 px-2 text-sm" disabled>Página anterior</button>
                        @else
                            <a href="{{ $livros->previousPageUrl() }}" class="join-item btn bg-black text-white font-semibold w-full py-1 px-2 text-sm">Página anterior</a>
                        @endif

                        {{-- Botão próxima página --}}
                        @if ($livros->hasMorePages())
                            <a href="{{ $livros->nextPageUrl() }}" class="join-item btn btn-outline font-semibold w-full py-1 px-2 text-sm">Próxima página</a>
                        @else
                            <button class="join-item btn btn-outline font-semibold w-full py-1 px-2 text-sm" disabled>Próxima página</button>
                        @endif
                    </div>
                </div>
            </div>
        {{-- Caso não haja livros, exibe mensagem amigável --}}
        @else
            <div class="text-center py-8 bg-white rounded-xl border border-gray-100">
                <p class="text-gray-500 text-lg">Nenhum livro encontrado.</p>
            </div>
        @endif
    </div>

    {{-- Script para fechar o popup de sucesso ao clicar em OK, fora do modal ou pressionar ESC --}}
    @if (session('popup_success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var popup = document.getElementById('livros-popup-success');
                var closeBtn = document.getElementById('livros-popup-success-close');

                if (!popup || !closeBtn) {
                    return;
                }

                function closePopup() {
                    popup.remove();
                }

                closeBtn.addEventListener('click', closePopup);

                popup.addEventListener('click', function (event) {
                    if (event.target === popup) {
                        closePopup();
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        closePopup();
                    }
                });
            });
        </script>
    @endif

    {{-- Script para fechar o popup de informação ao clicar em OK, fora do modal ou pressionar ESC --}}
    @if (session('popup_info'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var popup = document.getElementById('livros-popup-info');
                var closeBtn = document.getElementById('livros-popup-info-close');

                if (!popup || !closeBtn) {
                    return;
                }

                function closePopup() {
                    popup.remove();
                }

                closeBtn.addEventListener('click', closePopup);

                popup.addEventListener('click', function (event) {
                    if (event.target === popup) {
                        closePopup();
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        closePopup();
                    }
                });
            });
        </script>
    @endif

    <script>
        // Persiste a preferência de apresentação entre lista e cartões.
        document.addEventListener('DOMContentLoaded', function () {
            var toggleBtn = document.getElementById('livros-view-toggle');
            var toggleIcon = document.getElementById('livros-view-toggle-icon');
            var listView = document.getElementById('livros-list-view');
            var cardsView = document.getElementById('livros-cards-view');

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

                window.localStorage.setItem('livros_view_mode', view);
            }

            var savedView = window.localStorage.getItem('livros_view_mode');
            setView(savedView === 'cards' ? 'cards' : 'list');

            toggleBtn.addEventListener('click', function () {
                var currentView = toggleBtn.dataset.view;
                setView(currentView === 'list' ? 'cards' : 'list');
            });
        });
    </script>
</x-app-layout>



