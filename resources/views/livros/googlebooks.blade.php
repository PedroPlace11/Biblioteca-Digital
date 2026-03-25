<x-app-layout>
    <div class="w-full bg-gray-50 px-6 py-4 mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Buscar Livros na Google Books</h1>
            <p class="text-gray-500 mt-1">Pesquise livros pelo Google Books e importe para o catálogo.</p>
        </div>
        @auth
            <div class="text-sm text-gray-400 mt-2 md:mt-0">
                {{ now()->locale('pt_PT')->translatedFormat('d \d\e F \d\e Y') }}
            </div>
        @endauth
    </div>

    <div class="p-6 max-w-5xl mx-auto">
        {{-- Pesquisa centralizada sem card --}}
        <div class="mb-10 flex flex-col items-center justify-center w-full">
            <form method="GET" action="{{ route('livros.googlebooks') }}" class="w-full max-w-xl mx-auto">
                <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Pesquisa</label>
                <div class="flex w-full gap-2 mb-2">
                    <input type="text" name="q"
                        class="flex-1 px-2 py-1 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-600 focus:outline-none shadow-sm text-sm font-medium"
                        placeholder="Pesquisar por nome, autor, editora, ISBN ou bibliografia" value="{{ request('q', '') }}">
                    <button
                        class="px-3 py-1 rounded-md bg-black text-white font-bold shadow-sm flex items-center justify-center gap-2 border border-black hover:bg-gray-900 transition text-sm"
                        type="submit">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z" />
                        </svg>
                        Buscar
                    </button>
                    @if(!empty(request('q')))
                        <a href="{{ route('livros.googlebooks') }}"
                            class="px-3 py-1 rounded-md bg-white text-black font-bold shadow-sm flex items-center justify-center gap-2 border border-black hover:bg-gray-100 transition text-sm">
                            Limpar
                        </a>
                    @endif
                </div>
                @if(isset($pesquisasRecentes) && count($pesquisasRecentes) > 0)
                    <div class="flex flex-wrap gap-2 mb-2">
                        @foreach($pesquisasRecentes as $pesquisa)
                            <a href="{{ route('livros.googlebooks', ['q' => $pesquisa]) }}" class="px-3 py-1 rounded bg-gray-200 text-gray-700 text-xs font-medium hover:bg-gray-300 transition">{{ $pesquisa }}</a>
                        @endforeach
                    </div>
                @endif
            </form>
        </div>

        {{-- Últimos livros adicionados --}}
        @if((!request('q') || request('q') == '') && isset($ultimosLivros) && count($ultimosLivros) > 0)
            <div class="mb-8">
                <h2 class="text-2xl font-bold mb-4">Últimos Livros Adicionados</h2>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
                    @foreach($ultimosLivros as $livro)
                        <a href="{{ route('livros.show', isset($livro->encrypted_id) ? $livro->encrypted_id : encrypt($livro->id)) }}" class="card bg-base-100 shadow hover:shadow-lg transition block">
                            @if($livro->imagem_capa ?? $livro->capa_url)
                                <img src="{{ asset($livro->imagem_capa ?? $livro->capa_url) }}" alt="Capa de {{ $livro->nome }}" class="h-60 w-full object-cover rounded-t" />
                            @else
                                <div class="h-60 w-full bg-gray-100 rounded-t flex items-center justify-center text-4xl text-gray-300">📚</div>
                            @endif
                            <div class="card-body p-4">
                                <h3 class="text-sm font-semibold truncate" title="{{ $livro->nome }}">{{ $livro->nome }}</h3>
                                <p class="text-xs text-gray-500 truncate">
                                    @if($livro->autores && $livro->autores->count())
                                        {{ $livro->autores->pluck('nome')->join(', ') }}
                                    @else
                                        —
                                    @endif
                                </p>
                                <p class="text-sm font-semibold text-gray-900 mt-1">
                                    @if (!is_null($livro->preco))
                                        {{ number_format((float) $livro->preco, 2, ',', '.') }} €
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif


        @if (request('q') && $nenhumResultado)
            <div class="flex flex-col items-center justify-center my-20">
                <div class="text-6xl mb-3">🔍</div>
                <div
                    class="bg-yellow-50 border-l-4 border-yellow-400 text-yellow-800 p-5 rounded-xl text-center max-w-xl mx-auto font-medium shadow">
                    Nenhum resultado encontrado para <span class="font-bold">"{{ request('q') }}"</span>.
                </div>
            </div>
        @endif

        @if (request('q') && !$nenhumResultado && !empty($resultados))
            @if (!empty($mensagem))
                <div id="toast-success" class="fixed top-6 right-6 z-50 flex items-center w-full max-w-xs p-4 mb-4 text-gray-900 bg-green-50 rounded-lg shadow border border-green-400 animate-fade-in-up" role="alert" style="display: none;">
                    <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-green-500 bg-green-100 rounded-lg mr-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div class="text-sm font-medium">{{ $mensagem }}</div>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var toast = document.getElementById('toast-success');
                        if (toast) {
                            toast.style.display = 'flex';
                            setTimeout(function() {
                                toast.style.display = 'none';
                            }, 3500);
                        }
                    });
                </script>
                <style>
                    @keyframes fade-in-up {
                        0% { opacity: 0; transform: translateY(20px); }
                        100% { opacity: 1; transform: translateY(0); }
                    }
                    .animate-fade-in-up {
                        animation: fade-in-up 0.5s;
                    }
                </style>
            @endif
            <div class="mb-6 text-center">
                <span class="inline-block bg-blue-50 text-blue-800 px-4 py-2 rounded-full font-semibold text-base">Resultados para: <span class="font-bold">{{ request('q') }}</span></span>
            </div>
            <div class="w-full">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach ($resultados as $idx => $item)
                        @php
                            $info = $item['volumeInfo'] ?? [];
                            $isbn = $info['industryIdentifiers'][0]['identifier'] ?? '';
                            $autores = isset($info['authors']) ? implode(', ', $info['authors']) : '';
                            $editora = $info['publisher'] ?? '';
                            $ano = $info['publishedDate'] ?? '';
                            $descricao = $info['description'] ?? '';
                            $capa_url = $info['imageLinks']['thumbnail'] ?? '';
                            $saleInfo = $item['saleInfo'] ?? [];
                            $preco = isset($saleInfo['listPrice']['amount']) ? $saleInfo['listPrice']['amount'] : null;
                            $moeda = isset($saleInfo['listPrice']['currencyCode']) ? $saleInfo['listPrice']['currencyCode'] : 'EUR';
                        @endphp
                        <div class="bg-white rounded-xl shadow border border-gray-100 flex flex-col h-full">
                            <div class="flex justify-center items-center h-48 bg-gray-50 rounded-t-xl">
                                @if ($capa_url)
                                    <img src="{{ $capa_url }}" class="h-44 object-contain rounded" alt="Capa do livro">
                                @else
                                    <div class="w-24 h-32 bg-gray-100 rounded flex items-center justify-center text-4xl text-gray-300">📚</div>
                                @endif
                            </div>
                            <div class="flex-1 flex flex-col p-4">
                                <span class="text-gray-900 font-semibold text-base mb-1">{{ $info['title'] ?? 'Sem título' }}</span>
                                <p class="text-xs text-gray-400 mb-1">ISBN: {{ $isbn ?: '-' }}</p>
                                <p class="text-xs text-gray-700 mb-1">
                                    <span class="font-semibold">Autor(es):</span>
                                    {{ $autores ?: '—' }}
                                </p>
                                <p class="text-xs text-gray-600 mb-1"><span class="font-semibold">Editora:</span> {{ $editora ?: '-' }}</p>
                                <p class="text-xs text-gray-600 mb-2"><span class="font-semibold">Publicado em:</span> {{ $ano ?: '-' }}</p>
                                <p class="text-xs text-gray-900 mb-2">
                                    <span class="font-semibold">Preço:</span>
                                    @if($preco)
                                        {{ number_format($preco, 2, ',', '.') }} {{ $moeda }}
                                    @else
                                        —
                                    @endif
                                </p>
                                <div class="mt-auto flex flex-row flex-nowrap gap-2">
                                    <a href="{{ $info['infoLink'] ?? '#' }}" target="_blank"
                                        class="btn btn-sm bg-black text-white border-black hover:bg-gray-900 hover:text-white">Ver Livro</a>
                                    <form method="POST" action="{{ route('livros.googlebooks.salvar') }}">
                                        @csrf
                                        <input type="hidden" name="isbn" value="{{ $isbn }}">
                                        <input type="hidden" name="titulo" value="{{ $info['title'] ?? '' }}">
                                        <input type="hidden" name="autores" value="{{ $autores }}">
                                        <input type="hidden" name="editora" value="{{ $editora }}">
                                        <input type="hidden" name="ano" value="{{ $ano }}">
                                        <input type="hidden" name="descricao" value="{{ $descricao }}">
                                        <input type="hidden" name="capa_url" value="{{ $capa_url }}">
                                        <input type="hidden" name="preco" value="{{ $preco }}">
                                        <button type="submit"
                                            class="btn btn-sm bg-white text-black border-black hover:bg-gray-100 hover:text-black">Salvar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                {{-- Paginação Google Books --}}
                @if(isset($paginacaoGoogleBooks) && $paginacaoGoogleBooks)
                    <div class="flex justify-center mt-6">
                        {!! $paginacaoGoogleBooks !!}
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
