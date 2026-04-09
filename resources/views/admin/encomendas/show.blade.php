<x-app-layout>
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

    <div class="relative overflow-hidden py-8">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-br from-sky-50 via-white to-blue-50"></div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-6">
            @php
                $baseTotal = (float) $encomenda->itens->sum('subtotal');
                $valorSemIva = $baseTotal / 1.06;
                $valorIva = $baseTotal - $valorSemIva;
                $portes = $baseTotal < 50 ? 1.99 : 0.0;
                $quantidadeItens = (int) $encomenda->itens->sum('quantidade');
                $descontoPercentual = (int) ($encomenda->desconto_percentual ?? 0);
                $descontoValor = (float) ($encomenda->valor_desconto ?? 0);
                $totalFinal = (float) $encomenda->total;
            @endphp

            <section class="rounded-3xl border border-slate-200 bg-white/90 shadow-sm backdrop-blur p-6 sm:p-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Encomenda</p>
                        <h1 class="mt-2 text-3xl sm:text-4xl font-semibold text-slate-900">#{{ $encomenda->id }}</h1>
                        <p class="mt-2 text-slate-600">Resumo completo da compra e estado de gestão.</p>
                    </div>

                    <div class="flex flex-col items-start sm:items-end gap-3">
                        <a href="{{ route('admin.encomendas.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                            Voltar
                        </a>

                        @if ($encomenda->estado === 'enviado')
                            <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">Enviado</span>
                        @elseif ($encomenda->estado === 'paga')
                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Pago</span>
                        @elseif ($encomenda->estado === 'pagamento_recusado')
                            <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">Recusado</span>
                        @else
                            <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Pendente</span>
                        @endif
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Cliente</p>
                        <p class="text-sm text-slate-600">N.o leitor: {{ $encomenda->user?->numero_leitor ?? '-' }}</p>
                        <p class="mt-1 font-semibold text-slate-900 truncate">{{ $encomenda->user?->name ?? '-' }}</p>
                        <p class="text-sm text-slate-600 truncate">{{ $encomenda->user?->email ?? '-' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Data</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $encomenda->created_at?->format('d/m/Y H:i') }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4 space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Valor sem IVA</p>
                            <p class="text-sm font-semibold text-slate-900">{{ number_format($valorSemIva, 2, ',', '.') }} &euro;</p>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">IVA (6%)</p>
                            <p class="text-sm font-semibold text-slate-900">{{ number_format($valorIva, 2, ',', '.') }} &euro;</p>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Portes</p>
                            <p class="text-sm font-semibold text-slate-900">{{ number_format($portes, 2, ',', '.') }} &euro;</p>
                        </div>
                        @if ($descontoPercentual > 0 && $quantidadeItens >= 2)
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs uppercase tracking-wide text-emerald-700">-{{ $descontoPercentual }}% de desconto</p>
                                <p class="text-sm font-semibold text-emerald-700">-{{ number_format($descontoValor, 2, ',', '.') }} &euro;</p>
                            </div>
                        @endif
                        <div class="border-t border-slate-100 pt-3 flex items-center justify-between gap-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Total</p>
                            <p class="text-2xl font-semibold text-slate-900">{{ number_format($totalFinal, 2, ',', '.') }} &euro;</p>
                        </div>

                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Rastreio</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $encomenda->transportadora ?? 'CTT' }}</p>
                        <p class="text-sm text-slate-600 break-all">{{ $encomenda->numero_rastreio ?? 'Ainda sem código' }}</p>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 pt-6">
                        <h2 class="text-xl font-semibold text-slate-900">Itens da encomenda</h2>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[640px] text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-6 py-3 text-left font-semibold">Livro</th>
                                    <th class="px-6 py-3 text-left font-semibold">Qtd</th>
                                    <th class="px-6 py-3 text-left font-semibold">Preço unitário</th>
                                    <th class="px-6 py-3 text-left font-semibold">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @foreach ($encomenda->itens as $item)
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-4">
                                                <div class="shrink-0 flex items-center justify-center">
                                                    @if ($item->livro?->imagem_capa)
                                                        <img src="{{ asset($item->livro->imagem_capa) }}" alt="Capa {{ $item->livro_nome }}" class="h-24 w-16 rounded-md object-cover border border-slate-200 bg-slate-50">
                                                    @else
                                                        <div class="h-24 w-16 rounded-md border border-slate-200 bg-slate-100 flex items-center justify-center text-[10px] font-semibold text-slate-400 text-center px-1">
                                                            Sem capa
                                                        </div>
                                                    @endif
                                                </div>
                                                @if ($item->livro)
                                                    <a href="{{ route('livros.show', $item->livro) }}" class="font-medium text-slate-900 transition hover:text-sky-700 hover:underline">
                                                        {{ $item->livro_nome }}
                                                    </a>
                                                @else
                                                    <div class="font-medium text-slate-900">{{ $item->livro_nome }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">{{ $item->quantidade }}</td>
                                        <td class="px-6 py-4">{{ number_format((float) $item->preco_unitario, 2, ',', '.') }} &euro;</td>
                                        <td class="px-6 py-4 font-semibold">{{ number_format((float) $item->subtotal, 2, ',', '.') }} &euro;</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-6">
                    <aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-slate-900">Entrega</h2>
                        <p class="mt-1 text-sm text-slate-500">Dados do destinatário e morada.</p>

                        <div class="mt-5 space-y-3 text-slate-700">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-500">Destinatário</p>
                                <p class="font-semibold text-slate-900">{{ $encomenda->nome_destinatario }}</p>
                            </div>

                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-500">Contacto</p>
                                <p>{{ $encomenda->telemovel_destinatario }}</p>
                            </div>

                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-500">Morada</p>
                                <p>{{ $encomenda->morada_linha_1 }}</p>
                                @if ($encomenda->morada_linha_2)
                                    <p>{{ $encomenda->morada_linha_2 }}</p>
                                @endif
                                <p>{{ $encomenda->codigo_postal }} {{ $encomenda->cidade }} ({{ $encomenda->pais }})</p>
                            </div>
                        </div>
                    </aside>

                    @if (!in_array($encomenda->estado, ['enviado', 'pagamento_recusado'], true))
                        <aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="text-xl font-semibold text-slate-900">Gestão do estado</h2>
                            <p class="mt-1 text-sm text-slate-500">Sessão Stripe e ações administrativas.</p>

                            <div class="mt-5 space-y-3 text-slate-700 text-sm">
                                <p><span class="font-semibold">Sessão Stripe:</span></p>
                                <p class="text-xs break-all text-slate-600">{{ $encomenda->stripe_checkout_session_id ?? '-' }}</p>
                            </div>

                            <div class="mt-5 space-y-3">
                                <form method="POST" action="{{ route('admin.encomendas.pagamento', $encomenda) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="acao" value="enviar">
                                    <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-sky-600 to-blue-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:from-sky-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2">
                                        Confirmar envio
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.encomendas.pagamento', $encomenda) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="acao" value="pendente">
                                    <button type="submit" class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 transition hover:border-amber-300 hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2">
                                        Voltar para pendente
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.encomendas.pagamento', $encomenda) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="acao" value="recusado">
                                    <button type="submit" class="w-full rounded-xl border border-rose-200 bg-white px-4 py-3 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-300 focus:ring-offset-2">
                                        Recusar pagamento
                                    </button>
                                </form>
                            </div>
                        </aside>
                    @endif
                </div>
            </section>
        </div>
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
