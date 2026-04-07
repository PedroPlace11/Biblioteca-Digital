<x-app-layout>
    <div class="p-6 max-w-6xl mx-auto">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Carrinho</h1>
                <p class="text-slate-500 mt-1">Passo 1 de 3: Rever os livros adicionados</p>
            </div>
            <a href="{{ route('livros.index') }}" class="btn btn-outline">Continuar a explorar</a>
        </div>

        @if (session('popup_success') || session('popup_info'))
            <div class="fixed top-5 right-5 z-50 w-full max-w-md space-y-2">
                @if (session('popup_success'))
                    <div class="js-popup alert alert-success shadow-lg flex items-center justify-between gap-3">
                        <span>{{ session('popup_success') }}</span>
                        <button type="button" data-close-popup class="btn btn-xs btn-ghost">x</button>
                    </div>
                @endif

                @if (session('popup_info'))
                    <div class="js-popup alert alert-info shadow-lg flex items-center justify-between gap-3">
                        <span>{{ session('popup_info') }}</span>
                        <button type="button" data-close-popup class="btn btn-xs btn-ghost">x</button>
                    </div>
                @endif
            </div>
        @endif

        @if ($itens->isEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
                <p class="text-slate-600">Ainda não existem livros no carrinho.</p>
            </div>
        @else
            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Livro</th>
                                <th>Preço</th>
                                <th>Quantidade</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($itens as $item)
                                <tr>
                                    <td>
                                        <div class="font-semibold text-slate-800">{{ $item->livro?->nome ?? 'Livro removido' }}</div>
                                        <div class="text-xs text-slate-500">ISBN: {{ $item->livro?->isbn ?? '-' }}</div>
                                    </td>
                                    <td>{{ number_format((float) $item->preco_unitario, 2, ',', '.') }} &euro;</td>
                                    <td>
                                        <form method="POST" action="{{ route('carrinho.atualizar', $item->id) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="number" name="quantidade" min="1" max="20" value="{{ $item->quantidade }}" class="input input-bordered input-sm w-20" required>
                                            <button type="submit" class="btn btn-sm btn-outline">Atualizar</button>
                                        </form>
                                    </td>
                                    <td>{{ number_format((float) $item->subtotal, 2, ',', '.') }} &euro;</td>
                                    <td>
                                        <form method="POST" action="{{ route('carrinho.remover', $item->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-error btn-outline">Remover</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <form method="POST" action="{{ route('carrinho.limpar') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline btn-error">Limpar carrinho</button>
                </form>

                <div class="text-right">
                    <p class="text-sm text-slate-500">Total</p>
                    <p class="text-2xl font-bold text-slate-900">{{ number_format((float) $total, 2, ',', '.') }} &euro;</p>
                    <a href="{{ route('checkout.morada') }}" class="btn bg-black text-white border-black hover:bg-neutral-800 mt-3">
                        Avançar para morada
                    </a>
                </div>
            </div>
        @endif
    </div>

    @if (session('popup_success') || session('popup_info'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.js-popup').forEach(function (popup) {
                    var closeBtn = popup.querySelector('[data-close-popup]');

                    if (closeBtn) {
                        closeBtn.addEventListener('click', function () {
                            popup.remove();
                        });
                    }

                    setTimeout(function () {
                        popup.remove();
                    }, 4500);
                });
            });
        </script>
    @endif
</x-app-layout>
