<x-app-layout>
    {{-- Popup de sucesso exibido quando há mensagem de sucesso na sessão --}}
    @if (session('popup_success'))
        <div id="livro-popup-success" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/45"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-gray-900">Sucesso</h3>
                <p class="mt-2 text-sm text-gray-600">{{ session('popup_success') }}</p>
                <div class="mt-5 flex justify-end">
                    <button type="button" id="livro-popup-success-close" class="btn btn-outline">OK</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Popup de aviso exibido quando há mensagem de informação na sessão --}}
    @if (session('popup_info'))
        <div id="livro-popup-info" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/45"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-gray-900">Aviso</h3>
                <p class="mt-2 text-sm text-gray-600">{{ session('popup_info') }}</p>
                <div class="mt-5 flex justify-end">
                    <button type="button" id="livro-popup-info-close" class="btn btn-outline">OK</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Container principal da página de detalhes do livro --}}
    <div class="p-6 max-w-5xl mx-auto">
                {{-- Seção de Livros Relacionados --}}
                {{-- ...existing code... --}}
        {{-- Botão para voltar à listagem de livros --}}
        <div class="mb-6 text-left">
            <a href="{{ route('livros.index') }}" class="btn btn-outline text-xl px-4 py-2 min-h-0 h-auto leading-none" aria-label="Voltar aos Livros" title="Voltar">&larr;</a>
        </div>

        {{-- Cabeçalho com nome do livro --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold">{{ $livro->nome }}</h1>
        </div>

        {{-- Card com dados principais do livro: capa, metadados e ações --}}
        <div class="card bg-base-100 shadow mb-8">
            <div class="card-body grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Coluna da capa (ou placeholder quando não existir imagem) --}}
                <div>
                    @if ($livro->imagem_capa)
                        <img src="{{ asset($livro->imagem_capa) }}" alt="Capa de {{ $livro->nome }}"
                            class="w-full max-w-52 object-cover rounded-lg">
                    @else
                        <div
                            class="w-full max-w-52 h-64 bg-base-200 rounded-lg flex items-center justify-center text-sm opacity-70">
                            Sem capa
                        </div>
                    @endif
                </div>

                {{-- Coluna com metadados do livro: editora, ISBN, bibliografia, preço --}}
                <div class="md:col-span-2 flex flex-col h-full">
                    <div class="space-y-2">
                        <p><span class="font-semibold">Editora:</span> {{ $livro->editora?->nome ?? 'Sem editora' }}</p>
                        <p><span class="font-semibold">ISBN:</span> {{ $livro->isbn }}</p>
                        <p><span class="font-semibold">Bibliografia:</span> <span class="text-sm leading-relaxed">{{ $livro->bibliografia }}</span></p>
                        <p><span class="font-semibold">Preço:</span>
                            @if (!is_null($livro->preco))
                                {{ number_format((float) $livro->preco, 2, ',', '.') }} &euro;
                            @else
                                -
                            @endif
                        </p>
                    </div>

                    {{-- Bloco de ações: requisitar, solicitar devolução, editar (admin) ou login --}}
                    <div class="mt-auto pt-6 flex justify-end">
                        @if (auth()->check())
                            <div class="flex items-center gap-2">
                                @if (!empty($livroIndisponivel))
                                    <span class="badge badge-success badge-outline">Requisitado</span>
                                    @if (!empty($requisitadoPorMim))
                                        @if ($minhaRequisicaoAtiva && $minhaRequisicaoAtiva->devolucao_solicitada_em)
                                            <span class="badge border-amber-500 text-amber-700 bg-amber-50">Devolução em validação</span>
                                        @else
                                            <form action="{{ route('livros.cancelar-requisicao', $livro->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline btn-error">
                                                    Solicitar devolução
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                @else
                                    <form action="{{ route('livros.requisitar', $livro->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white">
                                            Requisitar
                                        </button>
                                    </form>
                                @endif

                                @if (auth()->user()->role === 'admin')
                                    <a href="{{ route('livros.edit', $livro->id) }}" class="btn btn-sm btn-outline">
                                        Editar
                                    </a>
                                @endif
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline">
                                Entrar para requisitar
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Seção inferior com relacionamentos: autores e editora --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <h2 class="text-2xl font-semibold mb-4">Autores</h2>

                {{-- Lista de autores vinculados ao livro --}}
                @if ($livro->autores->count() > 0)
                    <div class="flex flex-col gap-3">
                        @foreach ($livro->autores as $autor)
                            {{-- Card clicável do autor --}}
                            <a href="{{ route('autores.show', $autor->id) }}"
                                class="card bg-base-100 shadow hover:shadow-md transition-shadow">
                                <div class="card-body flex-row items-center gap-3 p-4">
                                    {{-- Foto do autor ou inicial do nome quando não houver foto --}}
                                    @if ($autor->foto)
                                        <img src="{{ asset($autor->foto) }}" alt="Foto de {{ $autor->nome }}"
                                            class="w-12 h-12 rounded-full object-cover">
                                    @else
                                        <div
                                            class="w-12 h-12 rounded-full bg-base-200 flex items-center justify-center text-sm font-semibold">
                                            {{ strtoupper(substr($autor->nome, 0, 1)) }}
                                        </div>
                                    @endif
                                    <span class="font-semibold text-black">{{ $autor->nome }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    {{-- Estado vazio quando não há autores vinculados --}}
                    <div class="alert">
                        <span>Este livro ainda não possui autores vinculados.</span>
                    </div>
                @endif
            </div>

            <div>
                <h2 class="text-2xl font-semibold mb-4">Editora</h2>

                {{-- Card da editora vinculada ao livro --}}
                @if ($livro->editora)
                    <a href="{{ route('editoras.show', $livro->editora->id) }}"
                        class="card bg-base-100 shadow hover:shadow-md transition-shadow">
                        <div class="card-body flex-row items-center gap-3 p-4">
                            {{-- Logotipo da editora ou inicial do nome quando não houver imagem --}}
                            @if ($livro->editora->logotipo)
                                <img src="{{ asset($livro->editora->logotipo) }}"
                                    alt="Logo de {{ $livro->editora->nome }}" class="w-12 h-12 rounded-full object-cover">
                            @else
                                <div
                                    class="w-12 h-12 rounded-full bg-base-200 flex items-center justify-center text-sm font-semibold">
                                    {{ strtoupper(substr($livro->editora->nome, 0, 1)) }}
                                </div>
                            @endif
                            <span class="font-semibold text-black">{{ $livro->editora->nome }}</span>
                        </div>
                    </a>
                @else
                    {{-- Estado vazio quando não há editora vinculada --}}
                    <div class="alert">
                        <span>Este livro ainda não possui editora vinculada.</span>
                    </div>
                @endif
            </div>
        </div>


        {{-- Seção de reviews ativos do livro --}}
        @if(isset($reviewsAtivos) && $reviewsAtivos->count())
            <div class="mt-10">
                <h2 class="text-2xl font-semibold mb-4">Reviews dos Leitores</h2>
                <div class="space-y-4">
                    @foreach($reviewsAtivos as $review)
                        <div class="card bg-base-100 shadow">
                            <div class="card-body p-4">
                                <div class="flex items-center gap-3 mb-2">
                                    @php
                                        $user = $review->user;
                                        $foto = ($user && $user->profile_photo_path) ? asset('storage/'.$user->profile_photo_path) : ($user ? $user->profile_photo_url : '');
                                    @endphp
                                    <img src="{{ $foto }}" alt="{{ $user->name }}" class="w-10 h-10 rounded-full object-cover border border-base-300">
                                    <span class="font-semibold">{{ $review->user->name }}</span>
                                    @if(isset($review->rating))
                                        <div class="ml-4 flex items-center">
                                            <div class="rating rating-sm">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <input type="radio" class="mask mask-star-2 bg-warning" disabled {{ $review->rating == $i ? 'checked' : '' }} />
                                                @endfor
                                            </div>
                                            <span class="ml-2 text-xs text-gray-500">{{ $review->rating }}/5</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="text-gray-800">{{ $review->conteudo }}</div>
                                <div class="text-xs text-gray-500 mt-2">Submetido em {{ $review->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Botão para deixar review (apenas cidadão, requisição encerrada e sem review) --}}
        @auth
            @php
                $temRequisicaoEncerrada = isset($historicoRequisicoesPorCidadao[auth()->id()]) && $historicoRequisicoesPorCidadao[auth()->id()]->whereNotNull('deleted_at')->count() > 0;
                $jaDeuReview = \App\Models\Review::where('user_id', auth()->id())->where('livro_id', $livro->id)->exists();
            @endphp
            @if(auth()->user()->role === 'cidadao' && $temRequisicaoEncerrada && !$jaDeuReview)
                <div class="my-8 flex justify-center">
                    <a href="{{ route('reviews.create', $livro->id) }}?requisicao_id={{ optional($historicoRequisicoesPorCidadao[auth()->id()]->whereNotNull('deleted_at')->first())->id }}" class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white px-6 py-2 rounded font-bold">
                        Deixar Review
                    </a>
                </div>
            @endif
        @endauth

        {{-- Seção de histórico de requisições do livro --}}
        @auth
        <div class="mt-10">
            <h2 class="text-2xl font-semibold mb-4">Histórico</h2>
            @if ($historicoRequisicoesPorCidadao->isNotEmpty())
                <div class="space-y-4">
                    @foreach ($historicoRequisicoesPorCidadao as $requisicoesDoCidadao)
                        @php
                            $cidadao = $requisicoesDoCidadao->first()?->user;
                        @endphp
                        {{-- Card de histórico de requisições por cidadão --}}
                        <div class="card bg-base-100 shadow">
                            <div class="card-body p-4">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-3">
                                    <div class="flex items-center gap-3">
                                        @php
                                            $user = $cidadao;
                                            $foto = ($user && $user->profile_photo_path) ? asset('storage/'.$user->profile_photo_path) : ($user ? $user->profile_photo_url : '');
                                        @endphp
                                        <img src="{{ $foto }}" alt="{{ $requisicoesDoCidadao->first()?->cidadao_nome ?? $user?->name ?? 'Cidadão' }}" class="w-14 h-14 rounded-full object-cover border border-base-300 shrink-0">
                                        <div>
                                            <p class="font-semibold text-lg">{{ $requisicoesDoCidadao->first()?->cidadao_nome ?? $cidadao?->name ?? 'Cidadão removido' }}</p>
                                            <p class="text-sm opacity-70">{{ $requisicoesDoCidadao->first()?->cidadao_email ?? $cidadao?->email ?? '-' }}</p>
                                            <p class="text-sm opacity-70">N.º leitor: {{ $requisicoesDoCidadao->first()?->cidadao_numero_leitor ?? $cidadao?->numero_leitor ?? '-' }}</p>
                                        </div>
                                    </div>
                                    <span class="badge badge-outline">
                                        {{ $requisicoesDoCidadao->count() }} {{ $requisicoesDoCidadao->count() === 1 ? 'requisição' : 'requisições' }}
                                    </span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>N.º requisição</th>
                                                <th>Estado</th>
                                                <th>Data de requisição</th>
                                                <th>Data prevista de fim</th>
                                                <th>Dias decorridos</th>
                                                <th>Data de encerramento</th>
                                                @if (auth()->check() && auth()->user()->role === 'admin')
                                                    <th>Ação</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($requisicoesDoCidadao as $requisicao)
                                                @php
                                                    $dataEncerramento = $requisicao->data_recepcao_real ?? $requisicao->deleted_at;
                                                    $diasDecorridos = $requisicao->dias_decorridos;
                                                    if (is_null($diasDecorridos) && $dataEncerramento && $requisicao->created_at) {
                                                        $diasDecorridos = (int) ceil(max(0, $requisicao->created_at->diffInHours($dataEncerramento) / 24));
                                                    }
                                                    $diasDecorridos = is_null($diasDecorridos) ? null : (int) $diasDecorridos;
                                                @endphp
                                                <tr>
                                                    <td>{{ $requisicao->numero_requisicao ?? '-' }}</td>
                                                    <td>
                                                        @if (is_null($requisicao->deleted_at))
                                                            @if ($requisicao->devolucao_solicitada_em)
                                                                <span class="badge border-amber-500 text-amber-700 bg-amber-50 whitespace-nowrap">A confirmar</span>
                                                            @else
                                                                <span class="badge badge-success badge-outline whitespace-nowrap">Ativa</span>
                                                            @endif
                                                        @else
                                                            <span class="badge border-red-500 text-red-600 bg-red-50 whitespace-nowrap">Encerrada</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $requisicao->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                                    <td>{{ $requisicao->data_fim_prevista?->format('d/m/Y H:i') ?? '-' }}</td>
                                                    <td>{{ is_null($diasDecorridos) ? '-' : $diasDecorridos . ' ' . ($diasDecorridos === 1 ? 'dia' : 'dias') }}</td>
                                                    <td>{{ $dataEncerramento?->format('d/m/Y H:i') ?? '-' }}</td>
                                                    @if (auth()->check() && auth()->user()->role === 'admin')
                                                        <td>
                                                            @if (is_null($requisicao->deleted_at))
                                                                @if (auth()->id() === $requisicao->user_id)
                                                                    <span class="text-xs text-amber-600 font-medium whitespace-nowrap">Outro admin deve confirmar</span>
                                                                @else
                                                                    <form action="{{ route('requisicoes.confirmar-recepcao', $requisicao) }}" method="POST">
                                                                        @csrf
                                                                        <button type="submit" class="btn btn-sm bg-black text-white border-black hover:bg-gray-900 whitespace-nowrap">Confirmar receção</button>
                                                                    </form>
                                                                @endif
                                                            @else
                                                                <span class="text-xs text-gray-400">Concluída</span>
                                                            @endif
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Estado vazio quando não existe histórico de requisições para o livro --}}
                <div class="alert">
                    <span>Ainda não existe histórico de requisições para este livro.</span>
                </div>
            @endif
        </div>
        @endauth

        {{-- Livros Relacionados (sem título), logo após o Histórico --}}
        @if(isset($livrosRelacionados) && $livrosRelacionados->count())
            <div class="mb-10 mt-10">
                <h2 class="text-2xl font-semibold mb-4">Livros Relacionados</h2>
                <div class="flex flex-wrap justify-center gap-6">
                    @foreach($livrosRelacionados as $relacionado)
                        <a href="{{ route('livros.show', $relacionado->id) }}" class="card bg-base-100 shadow hover:shadow-md transition-shadow" style="width: 220px;">
                            <div class="card-body p-4 flex flex-col items-center">
                                @if ($relacionado->imagem_capa)
                                    <img src="{{ asset($relacionado->imagem_capa) }}" alt="Capa de {{ $relacionado->nome }}" class="w-24 h-36 object-cover rounded mb-2">
                                @else
                                    <div class="w-24 h-36 bg-base-200 rounded flex items-center justify-center text-xs opacity-70 mb-2">Sem capa</div>
                                @endif
                                <div class="font-semibold text-center">{{ $relacionado->nome }}</div>
                                <div class="text-xs text-gray-500 text-center mt-1 line-clamp-2">{{ Str::limit($relacionado->bibliografia, 60) }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

    {{-- Script para fechar o popup de informação ao clicar em OK, fora do modal ou pressionar ESC --}}
    @if (session('popup_info'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var popup = document.getElementById('livro-popup-info');
                var closeBtn = document.getElementById('livro-popup-info-close');

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

    {{-- Script para fechar o popup de sucesso ao clicar em OK, fora do modal ou pressionar ESC --}}
    @if (session('popup_success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var popup = document.getElementById('livro-popup-success');
                var closeBtn = document.getElementById('livro-popup-success-close');

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



