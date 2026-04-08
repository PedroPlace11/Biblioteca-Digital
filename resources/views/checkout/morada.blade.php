<x-app-layout>
    <div class="relative overflow-hidden py-8">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-br from-slate-50 via-white to-sky-50"></div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white/90 shadow-sm backdrop-blur p-6 sm:p-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Checkout</p>
                        <h1 class="mt-2 text-3xl sm:text-4xl font-semibold text-slate-900">Morada de entrega</h1>
                    </div>
                </div>
            </section>

            @if (session('popup_info'))
                <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 shadow-sm">
                    {{ session('popup_info') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <section class="lg:col-span-2 rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 pt-6">
                        <h2 class="text-xl font-semibold text-slate-900">Dados de entrega</h2>
                        <p class="mt-1 text-sm text-slate-500">Preenche os campos com atenção. Podes voltar atrás a qualquer momento.</p>
                    </div>

                    @if ($moradas->isNotEmpty())
                        <form method="GET" action="{{ route('checkout.morada') }}" class="px-6 pt-4">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Moradas guardadas</label>
                            <select name="morada_id" class="select select-bordered w-full bg-white" onchange="this.form.submit()">
                                <option value="" @selected($preenchimentoManual || (int) $moradaSelecionadaId === 0)>Preencher manualmente</option>
                                @foreach ($moradas as $morada)
                                    <option value="{{ $morada->id }}" @selected((int) $moradaSelecionadaId === (int) $morada->id)>
                                        {{ $morada->titulo ?: $morada->morada_linha_1 }} - {{ $morada->codigo_postal }} {{ $morada->cidade }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Também podes gerir moradas em <a href="{{ route('cidadao.moradas.index') }}" class="font-semibold text-sky-700 hover:text-sky-800">Gerir Moradas</a>.</p>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('checkout.morada.guardar') }}" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        @csrf
                        <input type="hidden" name="morada_id" value="{{ old('morada_id', $moradaSelecionadaId ?: '') }}">

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nome do destinatário</label>
                            <input type="text" name="nome_destinatario" class="input input-bordered w-full bg-white" value="{{ old('nome_destinatario', $dadosMorada['nome_destinatario'] ?? ($preenchimentoManual ? '' : auth()->user()->name)) }}" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Telemóvel</label>
                            <input type="text" name="telemovel_destinatario" class="input input-bordered w-full bg-white" value="{{ old('telemovel_destinatario', $dadosMorada['telemovel_destinatario'] ?? '') }}" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Código postal</label>
                            <input type="text" name="codigo_postal" class="input input-bordered w-full bg-white" value="{{ old('codigo_postal', $dadosMorada['codigo_postal'] ?? '') }}" required>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Morada (linha 1)</label>
                            <input type="text" name="morada_linha_1" class="input input-bordered w-full bg-white" value="{{ old('morada_linha_1', $dadosMorada['morada_linha_1'] ?? '') }}" required>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Morada (linha 2)</label>
                            <input type="text" name="morada_linha_2" class="input input-bordered w-full bg-white" value="{{ old('morada_linha_2', $dadosMorada['morada_linha_2'] ?? '') }}" placeholder="Opcional">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Cidade</label>
                            <input type="text" name="cidade" class="input input-bordered w-full bg-white" value="{{ old('cidade', $dadosMorada['cidade'] ?? '') }}" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">País (código ISO)</label>
                            <input type="text" name="pais" maxlength="2" class="input input-bordered w-full bg-white uppercase" value="{{ old('pais', $dadosMorada['pais'] ?? ($preenchimentoManual ? '' : 'PT')) }}" required>
                        </div>

                        <div class="md:col-span-2 mt-2 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-2 border-t border-slate-100">
                            <a href="{{ route('carrinho.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                Voltar ao carrinho
                            </a>
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-black px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-800">
                                Ir para pagamento
                            </button>
                        </div>
                    </form>
                </section>

                <aside class="space-y-4">
                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Resumo</p>
                        <div class="mt-5 space-y-4">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm text-slate-600">Livros no carrinho</span>
                                <span class="text-sm font-semibold text-slate-900">{{ $itens->count() }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm text-slate-600">Total</span>
                                <span class="text-3xl font-semibold text-slate-900">{{ number_format((float) $total, 2, ',', '.') }} &euro;</span>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Etapas</p>
                        <ol class="mt-4 space-y-3 text-sm text-slate-600">
                            <li class="flex items-center gap-3"><span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700">1</span>Carrinho</li>
                            <li class="flex items-center gap-3"><span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-sky-100 text-xs font-semibold text-sky-700">2</span>Morada de entrega</li>
                            <li class="flex items-center gap-3"><span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">3</span>Pagamento</li>
                        </ol>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
