<x-app-layout>
    <div class="p-6 max-w-3xl mx-auto">
        <h1 class="text-3xl font-bold text-slate-900 mb-1">Checkout</h1>
        <p class="text-slate-500 mb-6">Passo 2 de 3: Morada</p>

        @if (session('popup_info'))
            <div class="alert alert-info mb-4"><span>{{ session('popup_info') }}</span></div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <form method="POST" action="{{ route('checkout.morada.guardar') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Nome do destinatário</span></label>
                    <input type="text" name="nome_destinatario" class="input input-bordered w-full" value="{{ old('nome_destinatario', $dadosMorada['nome_destinatario'] ?? auth()->user()->name) }}" required>
                </div>

                <div>
                    <label class="label"><span class="label-text">Telemóvel</span></label>
                    <input type="text" name="telemovel_destinatario" class="input input-bordered w-full" value="{{ old('telemovel_destinatario', $dadosMorada['telemovel_destinatario'] ?? '') }}" required>
                </div>

                <div>
                    <label class="label"><span class="label-text">Código postal</span></label>
                    <input type="text" name="codigo_postal" class="input input-bordered w-full" value="{{ old('codigo_postal', $dadosMorada['codigo_postal'] ?? '') }}" required>
                </div>

                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Morada (linha 1)</span></label>
                    <input type="text" name="morada_linha_1" class="input input-bordered w-full" value="{{ old('morada_linha_1', $dadosMorada['morada_linha_1'] ?? '') }}" required>
                </div>

                <div class="md:col-span-2">
                    <label class="label"><span class="label-text">Morada (linha 2)</span></label>
                    <input type="text" name="morada_linha_2" class="input input-bordered w-full" value="{{ old('morada_linha_2', $dadosMorada['morada_linha_2'] ?? '') }}">
                </div>

                <div>
                    <label class="label"><span class="label-text">Cidade</span></label>
                    <input type="text" name="cidade" class="input input-bordered w-full" value="{{ old('cidade', $dadosMorada['cidade'] ?? '') }}" required>
                </div>

                <div>
                    <label class="label"><span class="label-text">País (código ISO)</span></label>
                    <input type="text" name="pais" maxlength="2" class="input input-bordered w-full uppercase" value="{{ old('pais', $dadosMorada['pais'] ?? 'PT') }}" required>
                </div>

                <div class="md:col-span-2 flex items-center justify-between mt-4">
                    <a href="{{ route('carrinho.index') }}" class="btn btn-outline">Voltar</a>
                    <button type="submit" class="btn bg-black text-white border-black hover:bg-neutral-800">Ir para pagamento</button>
                </div>
            </form>
        </div>

        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Resumo: {{ $itens->count() }} livro(s)</p>
            <p class="text-xl font-semibold text-slate-900">{{ number_format((float) $total, 2, ',', '.') }} &euro;</p>
        </div>
    </div>
</x-app-layout>
