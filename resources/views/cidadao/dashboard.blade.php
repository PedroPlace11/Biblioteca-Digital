<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto">
        {{-- Cabeçalho do painel do cidadão, com saudação e data atual --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    Bem-vindo, {{ auth()->user()->name }}!!!
                </h1>
                <p class="text-gray-500 mt-1">
                    Explore livros, autores e editoras disponíveis na biblioteca.
                </p>
            </div>


            <div class="text-sm text-gray-400">
                {{ now()->locale('pt_PT')->translatedFormat('d \d\e F \d\e Y') }}
            </div>
        </div>
        {{-- Cards principais de navegação: Livros, Autores e Editoras --}}
        <div class="grid grid-cols-3 gap-6 mb-10">
            <a href="{{ route('livros.index') }}" class="card bg-base-100 shadow hover:shadow-xl transition">
                <div class="card-body text-center">
                    <div class="text-4xl">&#128218;</div>
                    <h2 class="card-title justify-center">
                        Livros
                    </h2>
                    <p class="text-sm text-gray-500">
                        Explore todos os livros disponíveis
                    </p>
                </div>
            </a>
            <a href="{{ route('autores.index') }}" class="card bg-base-100 shadow hover:shadow-xl transition">
                <div class="card-body text-center">
                    <div class="text-4xl">&#9997;&#65039;</div>
                    <h2 class="card-title justify-center">
                        Autores
                    </h2>
                    <p class="text-sm text-gray-500">
                        Conheça os autores da biblioteca
                    </p>
                </div>
            </a>
            <a href="{{ route('editoras.index') }}" class="card bg-base-100 shadow hover:shadow-xl transition">
                <div class="card-body text-center">
                    <div class="text-4xl">&#127970;</div>
                    <h2 class="card-title justify-center">
                        Editoras
                    </h2>
                    <p class="text-sm text-gray-500">
                        Descubra as editoras
                    </p>
                </div>
            </a>
        </div>
        {{-- Seção com os últimos livros adicionados à biblioteca --}}
        <h2 class="text-2xl font-bold mb-4">
            Últimos Livros Adicionados
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-6 mb-10">
            @foreach ($ultimosLivros as $livro)
                {{-- Card de livro com capa, nome, autores e preço --}}
                <a href="{{ route('livros.show', $livro->id) }}" class="card bg-base-100 shadow hover:shadow-lg">
                    @if ($livro->imagem_capa)
                        <img src="{{ asset($livro->imagem_capa) }}" class="h-60 object-cover">
                    @endif
                    <div class="card-body p-4">
                        <h3 class="text-sm font-semibold">
                            {{ $livro->nome }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            @foreach ($livro->autores as $autor)
                                @if (!$loop->first)
                                    |
                                @endif
                                {{ $autor->nome }}
                            @endforeach
                        </p>
                        <p class="text-sm font-semibold text-gray-900">
                            @if (!is_null($livro->preco))
                                {{ number_format((float) $livro->preco, 2, ',', '.') }} &euro;
                            @else
                                -
                            @endif
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
        {{-- Seção do histórico de requisições do cidadão --}}
        <h2 class="text-2xl font-bold mb-2">
            Meu Histórico de Requisições
        </h2>
        <div class="text-gray-600 mb-4 flex flex-wrap items-center gap-3">
            {{-- Total de requisições, badges de ativas e encerradas --}}
            <span>Total: <span class="font-semibold">{{ $totalMinhasRequisicoes }}</span></span>
            <span class="badge badge-success badge-outline">Ativas: {{ $totalMinhasRequisicoesAtivas }}</span>
            <span class="badge badge-error badge-outline">Encerradas: {{ $totalMinhasRequisicoesEncerradas }}</span>
        </div>

        @if ($minhasRequisicoes->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($minhasRequisicoes as $requisicao)
                    {{-- Card de requisição com dados do cidadão, status, livro e datas --}}
                    <a href="{{ route('livros.show', $requisicao->livro->id) }}" class="card bg-base-100 shadow hover:shadow-lg">
                        <div class="card-body p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    @php
                                        $user = auth()->user();
                                        $foto = $user->profile_photo_path ? asset('storage/'.$user->profile_photo_path) : $user->profile_photo_url;
                                    @endphp
                                    <img src="{{ $foto }}" alt="{{ $requisicao->cidadao_nome ?? $user->name }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 shrink-0">
                                    <div class="min-w-0">
                                        <p class="text-xs uppercase tracking-wide text-gray-400">Cidadão</p>
                                        <p class="text-sm font-medium text-gray-700 truncate">{{ $requisicao->cidadao_nome ?? auth()->user()->name }}</p>
                                        <p class="text-xs text-gray-400">N.º requisição: {{ $requisicao->numero_requisicao ?? '-' }}</p>
                                        <p class="text-xs text-gray-400">N.º leitor: {{ $requisicao->cidadao_numero_leitor ?? auth()->user()->numero_leitor ?? '-' }}</p>
                                    </div>
                                </div>
                                {{-- Badge de status da requisição: ativa ou encerrada --}}
                                @if (is_null($requisicao->deleted_at))
                                    <span class="badge badge-success badge-outline">Ativa</span>
                                @else
                                    <span class="badge badge-error badge-outline">Encerrada</span>
                                @endif
                            </div>
                            <h3 class="text-base font-semibold mt-3">
                                {{ $requisicao->livro->nome }}
                            </h3>
                            <p class="text-sm text-gray-500">
                                Editora: {{ $requisicao->livro->editora?->nome ?? '-' }}
                            </p>
                            <p class="text-sm text-gray-500">
                                Autores:
                                @foreach ($requisicao->livro->autores as $autor)
                                    @if (!$loop->first)
                                        |
                                    @endif
                                    {{ $autor->nome }}
                                @endforeach
                            </p>
                            <p class="text-sm text-gray-500">
                                Data da requisição: {{ $requisicao->created_at?->format('d/m/Y H:i') ?? '-' }}
                            </p>
                            <p class="text-sm text-gray-500">
                                Data prevista de fim: {{ $requisicao->data_fim_prevista?->format('d/m/Y H:i') ?? '-' }}
                            </p>
                            <p class="text-sm text-gray-500">
                                Data de encerramento: {{ $requisicao->deleted_at?->format('d/m/Y H:i') ?? '-' }}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            {{-- Mensagem exibida quando não há requisições no histórico --}}
            <div class="text-center py-8 bg-base-100 rounded-xl">
                <p class="text-gray-500">Ainda não existem requisições no seu histórico.</p>
            </div>
        @endif
    </div>

    {{-- Script para atualizar automaticamente o histórico de requisições se houver mudanças no servidor --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let ultimaAtualizacaoLocal = Number(@json((int) ($ultimaAtualizacaoRequisicoesTs ?? 0)));
            const endpointAtualizacao = @json(route('requisicoes.last-update'));

            setInterval(async function () {
                if (document.hidden) {
                    return;
                }

                try {
                    const response = await fetch(endpointAtualizacao, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        cache: 'no-store'
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    const ultimaAtualizacaoServidor = Number(data.last_update_ts || 0);

                    if (ultimaAtualizacaoServidor > ultimaAtualizacaoLocal) {
                        window.location.reload();
                    }
                } catch (error) {
                    // Ignora falhas de rede pontuais e tenta novamente no próximo ciclo.
                }
            }, 4000);
        });
    </script>
</x-app-layout>



