<x-app-layout>
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
                <a href="{{ route('livros.create') }}" class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white">Novo Livro</a>
                <a href="{{ route('livros.export') }}" class="btn btn-outline">Exportar para Excel</a>
                <a href="{{ route('livros.googlebooks') }}" class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white">Buscar na Google Books</a>
            </div>
        @endif

        {{-- Filtros de pesquisa e ordenação de livros --}}
        <form method="GET" action="{{ route('livros.index') }}" class="mb-6 p-4 rounded-xl border border-gray-100 bg-gray-50/60">
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
                                        <a href="{{ route('livros.show', $livro->id) }}" class="text-gray-900 font-semibold hover:underline">
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
                                            <a href="{{ route('autores.show', $autor->id) }}" class="hover:underline">{{ $autor->nome }}</a>
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
                                                <a href="{{ route('livros.show', $livro->id) }}" class="btn btn-sm bg-black text-white border-black hover:bg-gray-900 hover:text-white">Ver</a>
                                                @if (auth()->check() && !$indisponivel)
                                                    <form action="{{ route('livros.requisitar', $livro->id) }}" method="POST">
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
</x-app-layout>



