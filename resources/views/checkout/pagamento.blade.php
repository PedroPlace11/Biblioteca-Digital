<x-app-layout>
    <div class="relative overflow-hidden py-8">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-br from-slate-50 via-white to-sky-50"></div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="mb-6">
                <h1 class="text-3xl sm:text-4xl font-semibold text-slate-900">Checkout</h1>
                <p class="mt-1 text-slate-500">Pagamento</p>
            </div>

        @if (session('popup_info'))
            <div class="alert alert-info mb-4"><span>{{ session('popup_info') }}</span></div>
        @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <section class="lg:col-span-2 rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 pt-6">
                        <h2 class="text-xl font-semibold text-slate-900">Morada de entrega</h2>
                        <p class="mt-1 text-sm text-slate-500">Confirma os dados do destinatário antes de pagar.</p>

                        <div class="mt-5 text-sm text-slate-700 space-y-1">
                            <p><span class="font-semibold text-slate-900">Nome:</span> {{ $dadosMorada['nome_destinatario'] }}</p>
                            <p><span class="font-semibold text-slate-900">Telemóvel:</span> {{ $dadosMorada['telemovel_destinatario'] }}</p>
                            <p><span class="font-semibold text-slate-900">Morada:</span> {{ $dadosMorada['morada_linha_1'] }}</p>
                            @if (!empty($dadosMorada['morada_linha_2']))
                                <p>{{ $dadosMorada['morada_linha_2'] }}</p>
                            @endif
                            <p>{{ $dadosMorada['codigo_postal'] }} - {{ $dadosMorada['cidade'] }} ({{ $dadosMorada['pais'] }})</p>
                        </div>

                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-slate-900">Livros</h3>
                            <p class="mt-1 text-sm text-slate-500">Lista dos livros incluídos nesta compra.</p>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[640px] text-sm">
                            <thead class="bg-slate-50 text-slate-600 uppercase tracking-wide text-xs">
                                <tr>
                                    <th class="px-6 py-3 text-left font-semibold">Livro</th>
                                    <th class="px-6 py-3 text-left font-semibold">Qtd</th>
                                    <th class="px-6 py-3 text-left font-semibold">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @foreach ($itens as $item)
                                    <tr class="hover:bg-slate-50/70 transition align-middle">
                                        <td class="px-6 py-4">
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
                                                <div class="font-medium text-slate-900 leading-snug">{{ $item->livro?->nome ?? 'Livro removido' }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">{{ $item->quantidade }}</td>
                                        <td class="px-6 py-4 font-semibold text-slate-900">{{ number_format((float) $item->subtotal, 2, ',', '.') }} &euro;</td>
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
                            <li class="flex items-center gap-3"><span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700">1</span>Carrinho</li>
                            <li class="flex items-center gap-3"><span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700">2</span>Morada de entrega</li>
                            <li class="flex items-center gap-3"><span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-sky-100 text-xs font-semibold text-sky-700">3</span>Pagamento</li>
                        </ol>
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Código promocional</p>

                        <form method="POST" action="{{ route('checkout.promocao') }}" class="mt-4 space-y-3">
                            @csrf

                            <div class="flex gap-2">
                                <input
                                    type="text"
                                    name="codigo_promocional"
                                    value="{{ old('codigo_promocional', $promocaoAtiva['codigo'] ?? '') }}"
                                    class="input input-bordered w-full bg-white uppercase"
                                    placeholder="BIBLIOTECA10"
                                >
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-black px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-800">
                                    Aplicar
                                </button>
                            </div>

                            @error('codigo_promocional')
                                <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror

                            @if (!empty($promocaoAtiva))
                                <p class="text-xs font-medium text-emerald-700">-{{ $totais['desconto_percentual'] }}% de desconto ativo.</p>
                                <button
                                    type="submit"
                                    name="codigo_promocional"
                                    value=""
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                >
                                    Remover código
                                </button>
                            @endif
                        </form>
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-semibold text-black">Resumo</p>

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
                                    <span class="text-sm font-semibold text-slate-900">{{ number_format($totais['valor_sem_iva'], 2, ',', '.') }} &euro;</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm text-slate-600">IVA (6%)</span>
                                    <span class="text-sm font-semibold text-slate-900">{{ number_format($totais['valor_iva'], 2, ',', '.') }} &euro;</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm text-slate-600">Portes</span>
                                    <span class="text-sm font-semibold text-slate-900">{{ number_format($totais['portes'], 2, ',', '.') }} &euro;</span>
                                </div>
                                @if ($totais['desconto_percentual'] > 0)
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm text-emerald-700">Desconto (-{{ $totais['desconto_percentual'] }}%)</span>
                                        <span class="text-sm font-semibold text-emerald-700">-{{ number_format($totais['desconto_valor'], 2, ',', '.') }} &euro;</span>
                                    </div>
                                @endif
                            </div>

                            <div class="border-t border-slate-100 pt-4 flex items-end justify-between gap-3">
                                <span class="text-sm font-medium text-slate-600">Total</span>
                                <span class="text-3xl font-semibold text-slate-900">{{ number_format($totais['total'], 2, ',', '.') }} &euro;</span>
                            </div>
                        </div>

                        @if (!empty($clientSecret) && !empty($publishableKey))
                            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-900">Forma de pagamento</p>
                                <p class="mt-1 text-sm text-slate-500">Escolhe uma forma de pagamento e confirma aqui mesmo.</p>
                                <div id="payment-element" class="mt-4 rounded-xl bg-white p-3"></div>
                                <div id="payment-message" class="mt-3 text-sm text-rose-600" role="alert"></div>
                            </div>
                        @else
                            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                O Stripe não está configurado corretamente para mostrar as formas de pagamento nesta página.
                            </div>
                        @endif

                        <form id="payment-form" class="mt-6">
                            <button id="submit-payment" type="submit" disabled class="inline-flex w-full items-center justify-center rounded-xl bg-black px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:bg-slate-400">
                                <span id="button-text">Pagar</span>
                            </button>
                        </form>

                        <div class="mt-3 flex flex-col gap-2">
                            <a href="{{ route('checkout.morada') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Editar morada</a>
                            <a href="{{ route('carrinho.index') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-transparent px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Voltar ao carrinho</a>
                        </div>

                    </section>
                </aside>
            </div>
        </div>
    </div>

    @if (!empty($clientSecret) && !empty($publishableKey))
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const stripe = Stripe(@js($publishableKey));
                const elements = stripe.elements({
                    clientSecret: @js($clientSecret),
                    appearance: {
                        theme: 'stripe',
                        variables: {
                            colorPrimary: '#111827',
                            colorText: '#0f172a',
                            colorDanger: '#e11d48',
                            borderRadius: '12px',
                            fontFamily: 'ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                        },
                    },
                });

                const paymentElement = elements.create('payment', {
                    layout: 'tabs',
                });

                paymentElement.mount('#payment-element');

                const form = document.getElementById('payment-form');
                const submitButton = document.getElementById('submit-payment');
                const buttonText = document.getElementById('button-text');
                const messageContainer = document.getElementById('payment-message');
                const successUrl = @js(route('checkout.sucesso'));
                let paymentElementReady = false;

                paymentElement.on('ready', function () {
                    paymentElementReady = true;
                    submitButton.disabled = false;
                });

                paymentElement.on('loaderror', function (event) {
                    setMessage(event?.error?.message || 'Não foi possível carregar as formas de pagamento.');
                });

                const setMessage = (message) => {
                    messageContainer.textContent = message || '';
                };

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    if (!paymentElementReady) {
                        setMessage('A aguardar o carregamento das formas de pagamento.');
                        return;
                    }

                    setMessage('');
                    submitButton.disabled = true;
                    buttonText.textContent = 'A processar...';

                    try {
                        const { error: submitError } = await elements.submit();

                        if (submitError) {
                            setMessage(submitError.message || 'Confirma os dados do pagamento.');
                            submitButton.disabled = false;
                            buttonText.textContent = 'Pagar';
                            return;
                        }

                        const result = await stripe.confirmPayment({
                            elements,
                            confirmParams: {
                                return_url: successUrl,
                            },
                            redirect: 'if_required',
                        });

                        if (result.error) {
                            setMessage(result.error.message || 'Não foi possível concluir o pagamento.');
                            submitButton.disabled = false;
                            buttonText.textContent = 'Pagar';
                            return;
                        }

                        const paymentIntent = result.paymentIntent;

                        if (paymentIntent && ['succeeded', 'processing'].includes(paymentIntent.status)) {
                            window.location.href = `${successUrl}?payment_intent=${encodeURIComponent(paymentIntent.id)}`;
                            return;
                        }

                        setMessage('Não foi possível confirmar o pagamento.');
                    } catch (error) {
                        setMessage(error?.message || 'Erro inesperado ao processar o pagamento.');
                    }

                    submitButton.disabled = false;
                    buttonText.textContent = 'Pagar';
                });
            });
        </script>
    @endif
</x-app-layout>
