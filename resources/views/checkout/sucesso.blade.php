<x-app-layout>
    <div class="p-6 max-w-3xl mx-auto">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-8 text-center">
            <h1 class="text-3xl font-bold text-emerald-800">Encomenda feita com sucesso</h1>
            <p class="text-emerald-700 mt-2">O pagamento foi confirmado e a encomenda foi concluida com sucesso.</p>
            <p class="text-sm text-emerald-700 mt-4">Encomenda #{{ $encomenda->id }}</p>
            <p class="text-sm text-emerald-700">Estado: {{ $encomenda->estado === 'paga' ? 'Paga' : 'Pendente' }}</p>
            <p class="text-lg font-semibold text-emerald-900 mt-3">Total: {{ number_format((float) $encomenda->total, 2, ',', '.') }} &euro;</p>

            <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('dashboard') }}" class="btn bg-black text-white border-black hover:bg-neutral-800">Ir para painel</a>
                <a href="{{ route('cidadao.encomendas.index') }}" class="btn btn-outline">Ver minhas encomendas</a>
                <a href="{{ route('livros.index') }}" class="btn btn-outline">Continuar a comprar</a>
            </div>
        </div>
    </div>
</x-app-layout>
