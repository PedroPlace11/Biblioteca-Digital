<x-app-layout>
    {{-- Biblioteca Chart.js para renderizar o gráfico de livros por editora --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Container principal da dashboard --}}
    <div class="p-6">
        {{-- Título da página --}}
        <h1 class="text-3xl font-bold mb-6">
            Dashboard Biblioteca
        </h1>

        {{-- Cards de indicadores gerais do sistema --}}
        <div class="grid grid-cols-4 gap-4 mb-8">
            {{-- Indicadores resumidos para leitura rápida do estado do catálogo. --}}
            {{-- Total de livros cadastrados --}}
            <div class="card bg-primary text-primary-content">
                <div class="card-body">
                    <h2 class="card-title">Livros</h2>
                    <p class="text-3xl">{{ $totalLivros }}</p>
                </div>
            </div>

            {{-- Total de autores cadastrados --}}
            <div class="card bg-secondary text-secondary-content">
                <div class="card-body">
                    <h2 class="card-title">Autores</h2>
                    <p class="text-3xl">{{ $totalAutores }}</p>
                </div>
            </div>

            {{-- Total de editoras cadastradas --}}
            <div class="card bg-accent text-accent-content">
                <div class="card-body">
                    <h2 class="card-title">Editoras</h2>
                    <p class="text-3xl">{{ $totalEditoras }}</p>
                </div>
            </div>

            {{-- Soma total do valor dos livros cadastrados --}}
            <div class="card bg-success text-success-content">
                <div class="card-body">
                    <h2 class="card-title">Valor Livros</h2>
                    <p class="text-3xl">€ {{ $valorLivros }}</p>
                </div>
            </div>
        </div>

        {{-- Card com gráfico de quantidade de livros por editora --}}
        <div class="card bg-base-100 shadow mt-8">
            <div class="card-body">
                {{-- Gráfico de barras com distribuição de livros por editora. --}}
                <h2 class="card-title">
                    Livros por Editora
                </h2>
                <canvas id="graficoEditoras"></canvas>
            </div>
        </div>

        {{-- Ranking de autores com mais livros vinculados --}}
        <div class="card bg-base-100 shadow mt-8">
            <div class="card-body">
                {{-- Ranking com os autores mais publicados no sistema. --}}
                <h2 class="card-title">
                    Top Autores
                </h2>
                <ul>
                    {{-- Lista de autores com contador de livros --}}
                    @foreach ($topAutores as $autor)
                        <li class="flex justify-between p-2 border-b">
                            <span>{{ $autor->nome }}</span>
                            <span class="badge badge-primary">
                                {{ $autor->livros_count }} livros
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Destaques de preço: livro mais caro e mais barato --}}
        <div class="grid grid-cols-2 gap-4 mt-8">
            {{-- Card do livro com maior preço --}}
            <div class="card bg-error text-error-content">
                <div class="card-body">
                    <h2 class="card-title">
                        Livro Mais Caro
                    </h2>
                    <p>{{ $livroMaisCaro->nome }}</p>
                    <p>€ {{ $livroMaisCaro->preco }}</p>
                </div>
            </div>

            {{-- Card do livro com menor preço --}}
            <div class="card bg-success text-success-content">
                <div class="card-body">
                    <h2 class="card-title">
                        Livro Mais Barato
                    </h2>
                    <p>{{ $livroMaisBarato->nome }}</p>
                    <p>€ {{ $livroMaisBarato->preco }}</p>
                </div>
            </div>
        </div>

        {{-- Tabela com os últimos livros adicionados --}}
        <h2 class="text-2xl font-bold mb-4">
            Últimos Livros
        </h2>
        <table class="table w-full">
            <thead>
                <tr>
                    <th>Capa</th>
                    <th>Nome</th>
                    <th>Autor</th>
                    <th>Preço</th>
                </tr>
            </thead>
            <tbody>
                {{-- Percorre os últimos livros para montar as linhas da tabela --}}
                @foreach ($ultimosLivros as $livro)
                    <tr>
                        {{-- Capa do livro quando disponível --}}
                        <td>
                            @if ($livro->imagem_capa)
                                <img src="{{ asset($livro->imagem_capa) }}" class="w-12">
                            @endif
                        </td>

                        {{-- Nome do livro --}}
                        <td>{{ $livro->nome }}</td>

                        {{-- Lista de autores vinculados ao livro com separador visual --}}
                        <td>
                            @foreach ($livro->autores as $autor)
                                @if (!$loop->first)<span class="text-gray-400">|</span>@endif
                                <span class="text-sm text-gray-800">{{ $autor->nome }}</span>
                            @endforeach
                        </td>

                        {{-- Preço do livro --}}
                        <td>€ {{ $livro->preco }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Script de inicialização do gráfico de livros por editora --}}
    <script>
        // Obtém o elemento canvas onde o gráfico será desenhado.
        const ctx = document.getElementById('graficoEditoras');

        // Cria um gráfico de barras com rótulos e dados vindos do backend via Blade.
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [
                    // Nomes das editoras (eixo X).
                    @foreach ($livrosPorEditora as $editora)
                        "{{ $editora->nome }}",
                    @endforeach
                ],
                datasets: [{
                    label: 'Livros',
                    data: [
                        // Quantidade de livros por editora (eixo Y).
                        @foreach ($livrosPorEditora as $editora)
                            {{ $editora->livros_count }},
                        @endforeach
                    ]
                }]
            }
        });
    </script>
</x-app-layout>
