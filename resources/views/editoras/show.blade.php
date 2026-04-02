<x-app-layout>
    <div class="p-6 max-w-6xl mx-auto text-center">
        {{-- Botão de retorno para a listagem de editoras --}}
        <div class="mb-6 text-left">
            <a href="{{ route('editoras.index') }}" class="btn btn-outline text-xl px-4 py-2 min-h-0 h-auto leading-none" aria-label="Voltar às Editoras" title="Voltar">&larr;</a>
        </div>

        {{-- Cabeçalho com logotipo e resumo da editora --}}
        <div class="flex flex-col items-center mb-10">
            @if ($editora->logotipo)
                {{-- Exibe o logotipo da editora, se houver --}}
                <img src="{{ asset($editora->logotipo) }}" class="w-32 h-32 rounded-full object-cover shadow mb-4">
            @endif
            <h1 class="text-3xl font-bold">
                {{ $editora->nome }}
            </h1>
            <p class="text-gray-500">
                {{ $livros->count() }} livros publicados
            </p>
        </div>
        {{-- Título da seção de livros publicados --}}
        <h2 class="text-2xl font-semibold mb-6">
            Livros Publicados
        </h2>

        {{-- Grade de livros publicados pela editora, com visualização diferente para usuários autenticados e visitantes --}}
        <div class="flex flex-wrap justify-center gap-4">
            @foreach ($livros as $livro)
                @auth
                    {{-- Usuário autenticado pode clicar para ver detalhes do livro --}}
                    <a href="{{ route('livros.show', $livro) }}"
                        class="bg-white rounded-xl shadow hover:shadow-lg transition-transform hover:scale-105 overflow-hidden flex flex-col"
                        style="width: 176px;">
                        @if ($livro->imagem_capa)
                            {{-- Exibe a capa do livro, se houver --}}
                            <img src="{{ asset($livro->imagem_capa) }}" alt="Capa {{ $livro->nome }}" class="w-full h-56 object-cover">
                        @else
                            {{-- Caso não tenha capa, mostra mensagem --}}
                            <div class="w-full h-56 bg-base-200 flex items-center justify-center text-sm opacity-60">Sem capa</div>
                        @endif
                        <div class="p-3 text-center flex flex-col gap-1">
                            <span class="font-semibold text-sm leading-tight text-gray-800">{{ $livro->nome }}</span>
                        </div>
                    </a>
                @else
                    {{-- Visitante vê apenas informações básicas do livro --}}
                    <div class="bg-white rounded-xl shadow overflow-hidden flex flex-col" style="width: 176px;">
                        @if ($livro->imagem_capa)
                            <img src="{{ asset($livro->imagem_capa) }}" alt="Capa {{ $livro->nome }}" class="w-full h-56 object-cover">
                        @else
                            <div class="w-full h-56 bg-base-200 flex items-center justify-center text-sm opacity-60">Sem capa</div>
                        @endif
                        <div class="p-3 text-center flex flex-col gap-1">
                            <span class="font-semibold text-sm leading-tight text-gray-800">{{ $livro->nome }}</span>
                        </div>
                    </div>
                @endauth
            @endforeach
        </div>
        {{-- Título da seção de autores publicados --}}
        <h2 class="text-2xl font-semibold mt-12 mb-4">
            Autores publicados
        </h2>

        {{-- Lista de autores vinculados aos livros da editora --}}
        <div class="flex flex-wrap justify-center gap-4">
            @foreach ($autores as $autor)
                <a href="{{ route('autores.show', $autor) }}"
                    class="bg-white rounded-xl shadow hover:shadow-lg transition-shadow flex items-center gap-3 px-4 py-3"
                    style="width: 220px;">
                    @if ($autor->foto)
                        {{-- Exibe a foto do autor, se houver --}}
                        <img src="{{ asset($autor->foto) }}" class="w-12 h-12 rounded-full object-cover flex-shrink-0">
                    @else
                        {{-- Caso não tenha foto, mostra inicial do nome --}}
                        <div class="w-12 h-12 rounded-full bg-base-200 flex items-center justify-center text-sm font-semibold flex-shrink-0">
                            {{ strtoupper(substr($autor->nome, 0, 1)) }}
                        </div>
                    @endif
                    <span class="font-semibold text-sm text-gray-800">{{ $autor->nome }}</span>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>



