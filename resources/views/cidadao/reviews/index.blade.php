<x-app-layout>
<div class="max-w-7xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6">Meus Reviews Submetidos</h1>

    {{-- Filtros de busca --}}
    <form method="GET" action="" class="mb-6 p-4 rounded-xl border border-gray-100 bg-gray-50/60">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Estado</label>
                <select name="estado" class="select select-bordered w-full bg-white">
                    <option value="todas" {{ request('estado', 'todas') === 'todas' ? 'selected' : '' }}>Todas</option>
                    <option value="ativo" {{ request('estado') === 'ativo' ? 'selected' : '' }}>Aprovado</option>
                    <option value="recusado" {{ request('estado') === 'recusado' ? 'selected' : '' }}>Recusado</option>
                    <option value="suspenso" {{ request('estado') === 'suspenso' ? 'selected' : '' }}>Aguardando</option>
                </select>
            </div>
            <div>
                <label class="block text-xs uppercase tracking-wide text-gray-500 mb-1">Ordenar por</label>
                <select name="ordenar" class="select select-bordered w-full bg-white">
                    <option value="recentes" {{ request('ordenar', 'recentes') === 'recentes' ? 'selected' : '' }}>Mais recentes</option>
                    <option value="antigos" {{ request('ordenar') === 'antigos' ? 'selected' : '' }}>Mais antigos</option>
                    <option value="livro" {{ request('ordenar') === 'livro' ? 'selected' : '' }}>Nome do livro</option>
                </select>
            </div>
        </div>
        <div class="mt-3 flex gap-2">
            <button type="submit" class="px-4 py-2 rounded bg-black text-white font-bold border border-black">Filtrar</button>
            <a href="?" class="px-4 py-2 rounded bg-white text-black font-bold border border-black">Limpar</a>
        </div>
    </form>
    <div class="overflow-x-auto bg-white rounded-xl shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Livro</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Submetido em</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($reviews as $review)
                <tr>
                    <td class="px-4 py-2">{{ $review->livro->nome ?? '-' }}</td>
                    <td class="px-4 py-2">
                        @if($review->estado === 'ativo')
                            <span class="text-green-600 font-semibold">Aprovado</span>
                        @elseif($review->estado === 'recusado')
                            <span class="text-red-600 font-semibold">Recusado</span>
                        @else
                            <span class="text-yellow-600 font-semibold">Aguardando</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">{{ $review->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2">
                        <a href="{{ route('cidadao.reviews.show', $review->id) }}" class="px-2 py-1 rounded bg-black text-white font-bold border border-black text-xs">Ver</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{-- Paginação customizada --}}
    <div class="pagination-custom mt-6">
        <div class="join grid grid-cols-2 w-56 mx-auto">
            {{-- Botão página anterior --}}
            @if ($reviews->onFirstPage())
                <button class="join-item btn bg-black text-white font-semibold w-full py-1 px-2 text-sm" disabled>Página anterior</button>
            @else
                <a href="{{ $reviews->previousPageUrl() }}" class="join-item btn bg-black text-white font-semibold w-full py-1 px-2 text-sm">Página anterior</a>
            @endif

            {{-- Botão próxima página --}}
            @if ($reviews->hasMorePages())
                <a href="{{ $reviews->nextPageUrl() }}" class="join-item btn btn-outline font-semibold w-full py-1 px-2 text-sm">Próxima página</a>
            @else
                <button class="join-item btn btn-outline font-semibold w-full py-1 px-2 text-sm" disabled>Próxima página</button>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
