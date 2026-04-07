<x-app-layout>
    <div class="p-6 max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-slate-900 mb-1">Checkout</h1>
        <p class="text-slate-500 mb-6">Passo 3 de 3: Pagamento</p>

        @if (session('popup_info'))
            <div class="alert alert-info mb-4"><span>{{ session('popup_info') }}</span></div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="text-xl font-semibold text-slate-900 mb-4">Morada de entrega</h2>
                <div class="text-sm text-slate-700 space-y-1">
                    <p><span class="font-semibold">Nome:</span> {{ $dadosMorada['nome_destinatario'] }}</p>
                    <p><span class="font-semibold">Telemóvel:</span> {{ $dadosMorada['telemovel_destinatario'] }}</p>
                    <p><span class="font-semibold">Morada:</span> {{ $dadosMorada['morada_linha_1'] }}</p>
                    @if (!empty($dadosMorada['morada_linha_2']))
                        <p>{{ $dadosMorada['morada_linha_2'] }}</p>
                    @endif
                    <p>{{ $dadosMorada['codigo_postal'] }} - {{ $dadosMorada['cidade'] }} ({{ $dadosMorada['pais'] }})</p>
                </div>

                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-slate-900 mb-3">Livros</h3>
                    <div class="divide-y divide-slate-100">
                        @foreach ($itens as $item)
                            <div class="py-3 flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-slate-800">{{ $item->livro?->nome ?? 'Livro removido' }}</p>
                                    <p class="text-xs text-slate-500">Qtd: {{ $item->quantidade }}</p>
                                </div>
                                <p class="font-semibold text-slate-900">{{ number_format((float) $item->subtotal, 2, ',', '.') }} &euro;</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 h-fit">
                <h2 class="text-lg font-semibold text-slate-900">Pagamento</h2>

                <div class="mt-4">
                    <p class="text-sm text-slate-500">Total a pagar</p>
                    <p class="text-2xl font-bold text-slate-900">{{ number_format((float) $total, 2, ',', '.') }} &euro;</p>
                </div>

                <form method="POST" action="{{ route('checkout.pagamento.stripe') }}" class="mt-5">
                    @csrf
                    <button type="submit" class="btn w-full bg-black text-white border-black hover:bg-neutral-800">
                        Pagar
                    </button>
                </form>

                <div class="mt-3 flex flex-col gap-2">
                    <a href="{{ route('checkout.morada') }}" class="btn btn-outline w-full">Editar morada</a>
                    <a href="{{ route('carrinho.index') }}" class="btn btn-ghost w-full">Voltar ao carrinho</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
