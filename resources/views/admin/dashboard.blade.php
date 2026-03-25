<x-app-layout>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="p-6 max-w-7xl mx-auto">

        {{-- Cabeçalho do painel --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Bem-vindo, {{ auth()->user()->name }}!!!</h1>
                <p class="text-gray-500 mt-1">Aqui está o resumo da Biblioteca</p>
            </div>
            <div class="text-sm text-gray-400">
                {{ now()->locale('pt_PT')->translatedFormat('d \d\e F \d\e Y') }}
            </div>
        </div>

        {{-- Cartões de indicadores --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center text-2xl shrink-0">&#128218;</div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Livros</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $totalLivros }}</p>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center text-2xl shrink-0">&#9997;&#65039;</div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Autores</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $totalAutores }}</p>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center text-2xl shrink-0">&#127970;</div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Editoras</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $totalEditoras }}</p>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center text-2xl shrink-0">&#128203;</div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Requisição</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $totalRequisicoes }}</p>
                </div>
            </div>
        </div>

        {{-- Gráfico + autores em destaque --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {{-- Gráfico de livros por editora --}}
            <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-semibold text-gray-700 mb-4">Livros por Editora</h2>
                <canvas id="graficoEditoras" class="max-h-64"></canvas>
            </div>

            {{-- Autores em destaque --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-semibold text-gray-700 mb-4">Top Autores</h2>
                <ul class="space-y-3">
                    @foreach ($topAutores as $index => $autor)
                        <li class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-6 h-6 rounded-full bg-gray-100 text-gray-500 text-xs font-bold flex items-center justify-center shrink-0">
                                    {{ $index + 1 }}
                                </span>
                                <span class="text-sm text-gray-800 truncate">{{ $autor->nome }}</span>
                            </div>
                            <span class="badge badge-outline text-xs shrink-0">{{ $autor->livros_count }} livros</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Livros mais recentes --}}
        <h2 class="text-2xl font-bold mb-4">
            Últimos Livros Adicionados
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-6 mb-10">
            @foreach ($ultimosLivros as $livro)
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

        {{-- Todas as Requisições --}}
        <div id="secao-requisicoes" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                <h2 class="text-base font-semibold text-gray-700">Todas as Requisições</h2>
                <p class="text-xs text-gray-400">
                    A mostrar {{ $todasRequisicoes->count() }} de {{ $totalRequisicoesFiltradas }} resultado(s)
                </p>
            </div>

            <form id="filtros-requisicoes-form" method="GET" action="{{ route('dashboard') }}#secao-requisicoes" class="mb-5 p-4 rounded-xl border border-gray-100 bg-gray-50/60">
                {{-- Filtros para refinar o histórico geral de requisições exibido na tabela. --}}
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Estado</label>
                        <select name="estado" class="select select-bordered w-full bg-white">
                            <option value="todas" {{ $estado === 'todas' ? 'selected' : '' }}>Todas</option>
                            <option value="ativa" {{ $estado === 'ativa' ? 'selected' : '' }}>Ativas</option>
                            <option value="encerrada" {{ $estado === 'encerrada' ? 'selected' : '' }}>Encerradas</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Pesquisa</label>
                        <input
                            type="text"
                            name="q"
                            value="{{ $pesquisa }}"
                            placeholder="Utilizador, email, livro, ISBN, N.º leitor ou N.º requisição"
                            class="input input-bordered w-full bg-white"
                        >
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Data início</label>
                        <input type="date" name="data_inicio" value="{{ $dataInicio }}" class="input input-bordered w-full bg-white">
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Data fim</label>
                        <input type="date" name="data_fim" value="{{ $dataFim }}" class="input input-bordered w-full bg-white">
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="submit" class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white">Filtrar</button>
                    <a href="{{ route('dashboard') }}#secao-requisicoes" class="btn btn-outline" data-preserve-scroll="1">Limpar</a>
                </div>
            </form>

            @if ($todasRequisicoes->count() > 0)
                {{-- Tabela com requisições filtradas e dados resumidos do cidadão/livro/estado. --}}
                <div class="overflow-x-auto">
                    <table class="table w-full text-sm">
                        <thead>
                            <tr class="text-gray-400 text-xs uppercase tracking-wide border-b border-gray-100">
                                <th class="pb-3 font-medium text-left">N.º</th>
                                <th class="pb-3 font-medium text-left">Utilizador</th>
                                <th class="pb-3 font-medium text-left">Livro</th>
                                <th class="pb-3 font-medium text-left">Estado</th>
                                <th class="pb-3 font-medium text-left">Data da requisição</th>
                                <th class="pb-3 font-medium text-left">Fim previsto</th>
                                <th class="pb-3 font-medium text-left">Data de encerramento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($todasRequisicoes as $requisicao)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-3 pr-4">
                                        <span class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-xs font-semibold text-gray-700">
                                            {{ $requisicao->numero_requisicao ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="py-2">
                                        <div class="flex items-center gap-3">
                                            <img src="{{ $requisicao->cidadao_foto_url }}" alt="{{ $requisicao->cidadao_nome ?? $requisicao->user?->name ?? 'Cidadão' }}" class="w-10 h-10 rounded-full object-cover border border-gray-200 shrink-0">
                                            <div>
                                                <p class="text-gray-800 font-medium">{{ $requisicao->cidadao_nome ?? $requisicao->user?->name ?? '-' }}</p>
                                                <p class="text-xs text-gray-400">{{ $requisicao->cidadao_email ?? $requisicao->user?->email ?? '-' }}</p>
                                                <p class="text-xs text-gray-400">N.º leitor: {{ $requisicao->cidadao_numero_leitor ?? $requisicao->user?->numero_leitor ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-2 text-gray-600">{{ $requisicao->livro?->nome ?? '-' }}</td>
                                    <td class="py-2">
                                        @if (is_null($requisicao->deleted_at))
                                            <span class="badge badge-success badge-outline">Ativa</span>
                                        @else
                                            <span class="badge border-red-500 text-red-600 bg-red-50">Encerrada</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-gray-400 text-xs">{{ $requisicao->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="py-2 text-gray-400 text-xs">{{ $requisicao->data_fim_prevista?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="py-2 text-gray-400 text-xs">{{ $requisicao->deleted_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if (method_exists($todasRequisicoes, 'links'))
                    {{-- Paginação customizada --}}
                    <div class="pagination-custom mt-6">
                        <div class="join grid grid-cols-2 w-80 mx-auto">
                            @if ($todasRequisicoes->onFirstPage())
                                <button class="join-item btn bg-black text-white font-semibold w-full py-1 px-2 text-sm" disabled>Página anterior</button>
                            @else
                                <a href="{{ $todasRequisicoes->previousPageUrl() }}" class="join-item btn bg-black text-white font-semibold w-full py-1 px-2 text-sm">Página anterior</a>
                            @endif
                            @if ($todasRequisicoes->hasMorePages())
                                <a href="{{ $todasRequisicoes->nextPageUrl() }}" class="join-item btn btn-outline font-semibold w-full py-1 px-2 text-sm">Próxima página</a>
                            @else
                                <button class="join-item btn btn-outline font-semibold w-full py-1 px-2 text-sm" disabled>Próxima página</button>
                            @endif
                        </div>
                    </div>
                @endif
            @else
                <p class="text-gray-400 text-sm">Nenhuma requisição encontrada.</p>
            @endif
        </div>

        {{-- Meu histórico de requisições --}}
        <h2 class="text-2xl font-bold mb-2">
            Meu Histórico de Requisições
        </h2>
        <div class="text-gray-600 mb-4 flex flex-wrap items-center gap-3">
            <span>Total: <span class="font-semibold">{{ $totalMinhasRequisicoesAdmin }}</span></span>
            <span class="badge badge-success badge-outline">Ativas: {{ $totalMinhasRequisicoesAdminAtivas }}</span>
            <span class="badge badge-error badge-outline">Encerradas: {{ $totalMinhasRequisicoesAdminEncerradas }}</span>
        </div>

        @if ($minhasRequisicoesAdmin->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($minhasRequisicoesAdmin as $requisicao)
                    <a href="{{ route('livros.show', $requisicao->livro->id) }}" class="card bg-base-100 shadow hover:shadow-lg">
                        <div class="card-body p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <img src="{{ $requisicao->cidadao_foto_url }}" alt="{{ $requisicao->cidadao_nome ?? auth()->user()->name }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 shrink-0">
                                    <div class="min-w-0">
                                        <p class="text-xs uppercase tracking-wide text-gray-400">Cidadão</p>
                                        <p class="text-sm font-medium text-gray-700 truncate">{{ $requisicao->cidadao_nome ?? auth()->user()->name }}</p>
                                        <p class="text-xs text-gray-400">N.º requisição: {{ $requisicao->numero_requisicao ?? '-' }}</p>
                                        <p class="text-xs text-gray-400">N.º leitor: {{ $requisicao->cidadao_numero_leitor ?? auth()->user()->numero_leitor ?? '-' }}</p>
                                    </div>
                                </div>
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
            <div class="text-center py-8 bg-base-100 rounded-xl">
                <p class="text-gray-500">Ainda não existem requisições no seu histórico.</p>
            </div>
        @endif
    </div>

    <script>
        // Preserva a posição de scroll ao filtrar e navegar na secção de requisições.
        document.addEventListener('DOMContentLoaded', function () {
            const storageKey = 'admin-dashboard-scroll-y';
            const savedScrollY = sessionStorage.getItem(storageKey);

            if (savedScrollY !== null) {
                const parsed = parseInt(savedScrollY, 10);

                if (!Number.isNaN(parsed)) {
                    window.scrollTo(0, parsed);
                }

                sessionStorage.removeItem(storageKey);
            }

            const requisicoesSection = document.getElementById('secao-requisicoes');
            const filtrosForm = document.getElementById('filtros-requisicoes-form');

            // Se a secção não existir, não há necessidade de registar eventos de scroll.
            if (!requisicoesSection) {
                return;
            }

            // Guarda posição vertical atual para restaurar após recarregamento.
            const saveScrollPosition = function () {
                sessionStorage.setItem(storageKey, String(window.scrollY));
            };

            if (filtrosForm) {
                filtrosForm.addEventListener('submit', saveScrollPosition);
            }

            requisicoesSection.querySelectorAll('a[href]').forEach(function (link) {
                link.addEventListener('click', function () {
                    saveScrollPosition();
                });
            });
        });

        // Inicializa gráfico de barras com número de livros por editora.
        const ctx = document.getElementById('graficoEditoras');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [
                    @foreach ($livrosPorEditora as $editora)
                        "{{ $editora->nome }}",
                    @endforeach
                ],
                datasets: [{
                    label: 'Livros',
                    data: [
                        @foreach ($livrosPorEditora as $editora)
                            {{ $editora->livros_count }},
                        @endforeach
                    ],
                    backgroundColor: 'rgba(17,17,17,0.8)',
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { stepSize: 1 } }
                }
            }
        });
    </script>
</x-app-layout>



