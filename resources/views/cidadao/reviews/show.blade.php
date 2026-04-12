<x-app-layout>
<div class="max-w-2xl mx-auto p-6">
    {{-- Navegação de retorno para a listagem de reviews. --}}
    <div class="mb-6 text-left">
        <a href="{{ route('cidadao.reviews.index') }}" class="btn btn-outline text-xl px-4 py-2 min-h-0 h-auto leading-none" aria-label="Voltar aos Reviews" title="Voltar">&larr;</a>
    </div>
    <h1 class="text-2xl font-bold mb-6">Detalhes do Review</h1>
    <div class="bg-white rounded-xl shadow p-6">
        {{-- Bloco com identificação do livro avaliado. --}}
        <div class="mb-4">
            <span class="font-semibold">Livro:</span> {{ $review->livro->nome ?? '-' }}
        </div>
        <div class="mb-4">
            <span class="font-semibold">Conteúdo:</span>
            <div class="mt-1 text-gray-800">{{ $review->conteudo }}</div>
        </div>
        <div class="mb-4">
            <span class="font-semibold">Estado:</span>
            {{-- Estado apresentado com cor semântica para leitura rápida. --}}
            @if($review->estado === 'ativo')
                <span class="text-green-600 font-semibold">Aprovado</span>
            @elseif($review->estado === 'recusado')
                <span class="text-red-600 font-semibold">Recusado</span>
            @else
                <span class="text-yellow-600 font-semibold">Aguardando</span>
            @endif
        </div>
        <div class="mb-4">
            <span class="font-semibold">Submetido em:</span> {{ $review->created_at->format('d/m/Y H:i') }}
        </div>
        @if($review->estado === 'recusado' && $review->justificacao)
        {{-- Exibe motivo da recusa quando informado na moderação. --}}
        <div class="mb-4">
            <span class="font-semibold text-red-600">Justificação:</span>
            <span class="text-red-500">{{ $review->justificacao }}</span>
        </div>
        @endif
        <!-- Botão de voltar já está no topo -->
    </div>
</div>
</x-app-layout>
