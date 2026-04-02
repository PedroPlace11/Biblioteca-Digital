<x-app-layout>
    <div class="p-8 max-w-5xl mx-auto">
        {{-- Botão de retorno para a lista de autores --}}
        <div class="mb-6 text-left">
            <a href="{{ route('autores.index') }}" class="btn btn-outline text-xl px-4 py-2 min-h-0 h-auto leading-none" aria-label="Voltar aos Autores" title="Voltar">&larr;</a>
        </div>

        {{-- Cabeçalho com foto do autor e biografia --}}
        <div class="flex gap-8 mb-10">
            @if ($autor->foto)
                {{-- Exibe a foto do autor, se houver --}}
                <img src="{{ asset($autor->foto) }}" class="w-40 h-40 object-cover rounded-lg">
            @endif
            <div>
                <h1 class="text-4xl font-bold">
                    {{ $autor->nome }}
                </h1>
                {{-- Exibe a biografia do autor ou mensagem padrão --}}
                <p class="mt-4 text-gray-600">
                    {{ $autor->bibliografia ?? 'Sem bibliografia disponível.' }}
                </p>
            </div>
        </div>

        {{-- Título da seção de livros --}}
        <h2 class="text-2xl font-bold mb-6 text-center">
            Livros
        </h2>

        {{-- Lista de livros escritos pelo autor, com visualização diferente para usuários autenticados e visitantes --}}
        <div class="flex flex-wrap justify-center gap-6">
            @foreach ($autor->livros as $livro)
                @auth
                    {{-- Usuário autenticado pode clicar para ver detalhes do livro --}}
                    <a href="{{ route('livros.show', $livro) }}"
                        class="bg-white rounded-xl shadow hover:shadow-lg transition-shadow overflow-hidden flex flex-col w-52">
                        @if ($livro->imagem_capa)
                            {{-- Exibe a capa do livro, se houver --}}
                            <img src="{{ asset($livro->imagem_capa) }}" alt="Capa {{ $livro->nome }}" class="w-full h-60 object-cover">
                        @else
                            {{-- Caso não tenha capa, mostra mensagem --}}
                            <div class="w-full h-60 bg-base-200 flex items-center justify-center text-sm opacity-60">Sem capa</div>
                        @endif
                        <div class="p-3 flex flex-col gap-1">
                            <span class="font-semibold text-sm leading-tight text-gray-800">{{ $livro->nome }}</span>
                        </div>
                    </a>
                @else
                    {{-- Visitante vê apenas informações básicas do livro --}}
                    <div class="bg-white rounded-xl shadow overflow-hidden flex flex-col w-52">
                        @if ($livro->imagem_capa)
                            <img src="{{ asset($livro->imagem_capa) }}" alt="Capa {{ $livro->nome }}" class="w-full h-60 object-cover">
                        @else
                            <div class="w-full h-60 bg-base-200 flex items-center justify-center text-sm opacity-60">Sem capa</div>
                        @endif
                        <div class="p-3 flex flex-col gap-1">
                            <span class="font-semibold text-sm leading-tight text-gray-800">{{ $livro->nome }}</span>
                        </div>
                    </div>
                @endauth
            @endforeach
        </div>

        @if ($editoras->isNotEmpty())
            {{-- Seção de editoras relacionadas aos livros deste autor --}}
            <h2 class="text-2xl font-bold mb-6 mt-10 text-center">Editoras</h2>

            <div class="flex flex-wrap justify-center gap-4">
                @foreach ($editoras as $editora)
                        <a href="{{ route('editoras.show', $editora) }}"
                        class="bg-white rounded-xl shadow hover:shadow-lg transition-shadow flex items-center gap-3 px-4 py-3"
                        style="width: 220px;">
                        @if ($editora->logotipo)
                            {{-- Exibe o logotipo da editora, se houver --}}
                            <img src="{{ asset($editora->logotipo) }}" class="w-12 h-12 object-contain flex-shrink-0">
                        @else
                            {{-- Caso não tenha logotipo, mostra inicial do nome --}}
                            <div class="w-12 h-12 rounded-full bg-base-200 flex items-center justify-center text-sm font-semibold flex-shrink-0">
                                {{ strtoupper(substr($editora->nome, 0, 1)) }}
                            </div>
                        @endif
                        <span class="font-semibold text-sm text-gray-800">{{ $editora->nome }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>



