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
                                        <a href="{{ route('autores.show', $autor->id) }}" class="font-semibold text-gray-900 hover:underline">{{ $autor->nome }}</a>
                                        <p class="text-xs text-gray-400 mt-1">{{ $autor->livros->count() }} livro(s) associado(s)</p>
                                    </td>
                                    {{-- Lista até 4 livros do autor, com link, e indica se há mais --}}
                                    <td class="py-3 text-gray-700">
                                        @if ($autor->livros->count() > 0)
                                            @foreach ($autor->livros->take(4) as $livro)
                                                @if (!$loop->first)
                                                    <span class="text-gray-300">|</span>
                                                @endif
                                                <a href="{{ route('livros.show', $livro->id) }}" class="hover:underline">{{ $livro->nome }}</a>
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
                                            <a href="{{ route('autores.edit', $autor->id) }}" class="btn btn-sm btn-outline">Editar</a>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
</x-app-layout>



