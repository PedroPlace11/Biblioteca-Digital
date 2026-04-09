<x-app-layout>
    <div class="relative overflow-hidden py-8">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-br from-slate-50 via-white to-sky-50"></div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white/90 shadow-sm backdrop-blur p-6 sm:p-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Checkout</p>
                        <h1 class="mt-2 text-3xl sm:text-4xl font-semibold text-slate-900">Carrinho</h1>
                    </div>

                    @if ($itens->isNotEmpty())
                        <a href="{{ route('livros.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                            Continuar a comprar
                        </a>
                    @endif
                </div>
            </section>

            @if ($itens->isEmpty())
                <section class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-500">🛒</div>
                    <h2 class="mt-5 text-2xl font-semibold text-slate-900">O carrinho está vazio</h2>
                    <p class="mt-2 text-slate-600">Adicione livros para iniciar o processo de compra.</p>
                    <div class="mt-6">
                        <a href="{{ route('livros.index') }}" class="inline-flex items-center justify-center rounded-xl bg-black px-5 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                            Explorar livros
                        </a>
                    </div>
                </section>
            @else
                @php
                    $subtotalComIva = (float) $itens->sum('subtotal');
                    $valorSemIva = $subtotalComIva / 1.06;
                    $valorIva = $subtotalComIva - $valorSemIva;
                    $portes = $subtotalComIva < 50 ? 1.99 : 0.0;
                    $totalFinal = $subtotalComIva + $portes;
                @endphp

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <section class="lg:col-span-2 rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div class="px-6 pt-6">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 class="text-xl font-semibold text-slate-900">Itens do carrinho</h2>
                                    <p class="mt-1 text-sm text-slate-500">Revê os livros, ajusta quantidades e remove o que não pretendes requisitar.</p>
                                </div>
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">
                                    {{ $itens->count() }} livro(s)
                                </span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <table class="w-full table-fixed text-sm">
                                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wide text-xs">
                                    <tr>
                                        <th class="px-6 py-4 text-left font-semibold w-[40%]">Livro</th>
                                        <th class="px-6 py-4 text-left font-semibold w-[12%]">Preço</th>
                                        <th class="px-6 py-4 text-center font-semibold w-[25%]">Quantidade</th>
                                        <th class="px-6 py-4 text-left font-semibold w-[13%]">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($itens as $item)
                                        <tr class="align-middle hover:bg-slate-50/70 transition">
                                            <td class="px-6 py-4 whitespace-normal break-words">
                                                <div class="flex items-center gap-4">
                                                    <div class="shrink-0 flex items-center justify-center">
                                                        @if ($item->livro?->imagem_capa)
                                                            <img src="{{ asset($item->livro->imagem_capa) }}" alt="Capa {{ $item->livro?->nome ?? 'Livro removido' }}" class="h-24 w-16 rounded-md object-cover border border-slate-200 bg-slate-50">
                                                        @else
                                                            <div class="h-24 w-16 rounded-md border border-slate-200 bg-slate-100 flex items-center justify-center text-[10px] font-semibold text-slate-400 text-center px-1">
                                                                Sem capa
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="text-left">
                                                        @if ($item->livro)
                                                            <a href="{{ route('livros.show', $item->livro) }}" class="font-semibold text-slate-900 leading-snug hover:underline">
                                                                {{ $item->livro->nome }}
                                                            </a>
                                                        @else
                                                            <div class="font-semibold text-slate-900 leading-snug">Livro removido</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-slate-700 whitespace-nowrap">{{ number_format((float) $item->preco_unitario, 2, ',', '.') }} &euro;</td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center justify-center gap-2 flex-nowrap">
                                                    @if ($item->quantidade === 1)
                                                        <form method="POST" action="{{ route('carrinho.remover', $item->id) }}" class="inline-flex items-center">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-white px-2 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-50" aria-label="Remover item do carrinho">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0C9.91 2.48 9 3.464 9 4.645v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    <form method="POST" action="{{ route('carrinho.atualizar', $item->id) }}" class="inline-flex items-center gap-1">
                                                        @csrf
                                                        @method('PATCH')
                                                        @if ($item->quantidade > 1)
                                                            <button type="submit" name="quantidade" value="{{ $item->quantidade - 1 }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-lg font-semibold text-slate-700 transition hover:bg-slate-50" aria-label="Diminuir quantidade do livro {{ $item->livro?->nome ?? 'Livro removido' }}">-</button>
                                                        @endif
                                                        <input type="text" value="{{ $item->quantidade }}" readonly class="input input-bordered input-sm w-14 bg-white text-center" aria-label="Quantidade do livro {{ $item->livro?->nome ?? 'Livro removido' }}">
                                                        <button type="submit" name="quantidade" value="{{ min(20, $item->quantidade + 1) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-lg font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" {{ $item->quantidade >= 20 ? 'disabled' : '' }} aria-label="Aumentar quantidade do livro {{ $item->livro?->nome ?? 'Livro removido' }}">+</button>
                                                    </form>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 font-semibold text-slate-900 whitespace-nowrap">{{ number_format((float) $item->subtotal, 2, ',', '.') }} &euro;</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <aside class="space-y-4">
                        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <p class="text-sm font-semibold text-slate-900">Etapas</p>
                            <ol class="mt-4 space-y-3 text-sm text-slate-600">
                                <li class="flex items-center gap-3"><span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-sky-100 text-xs font-semibold text-sky-700">1</span>Carrinho</li>
                                <li class="flex items-center gap-3"><span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">2</span>Morada de entrega</li>
                                <li class="flex items-center gap-3"><span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">3</span>Pagamento</li>
                            </ol>
                        </section>

                        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <p class="text-sm font-semibold text-slate-900">Resumo</p>
                            <div class="mt-5 space-y-4">
                                <div class="space-y-2">
                                    @foreach ($itens as $itemResumo)
                                        <div class="flex items-start justify-between gap-3">
                                            <span class="text-sm text-slate-600 leading-snug">{{ $itemResumo->livro?->nome ?? 'Livro removido' }}</span>
                                            <span class="text-sm font-semibold text-slate-900 whitespace-nowrap">x{{ $itemResumo->quantidade }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="border-t border-slate-100 pt-4 space-y-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm text-slate-600">Valor sem IVA</span>
                                        <span class="text-sm font-semibold text-slate-900">{{ number_format($valorSemIva, 2, ',', '.') }} &euro;</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm text-slate-600">IVA (6%)</span>
                                        <span class="text-sm font-semibold text-slate-900">{{ number_format($valorIva, 2, ',', '.') }} &euro;</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm text-slate-600">Portes</span>
                                        <span class="text-sm font-semibold text-slate-900">{{ number_format($portes, 2, ',', '.') }} &euro;</span>
                                    </div>
                                </div>
                                <div class="border-t border-slate-100 pt-4 flex items-end justify-between gap-3">
                                    <span class="text-sm font-medium text-slate-600">Total</span>
                                    <span class="text-3xl font-semibold text-slate-900">{{ number_format($totalFinal, 2, ',', '.') }} &euro;</span>
                                </div>
                            </div>

                            <a href="{{ route('checkout.morada') }}" class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-black px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                                Avançar para morada
                            </a>
                        </section>
                    </aside>
                </div>
            @endif
        </div>
    </div>

</x-app-layout>
