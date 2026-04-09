<x-app-layout>
    @php
        $estadoTexto = $encomenda->estado === 'enviado'
            ? 'Enviado'
            : ($encomenda->estado === 'paga' ? 'Paga' : 'Pendente');
        $estadoClasses = $encomenda->estado === 'enviado'
            ? 'text-blue-700 bg-blue-50 border-blue-200'
            : 'text-slate-900 bg-slate-50 border-slate-200';
    @endphp

    <div class="relative overflow-hidden py-10">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-br from-emerald-50 via-white to-slate-50"></div>

        <div class="mx-auto max-w-4xl px-4 sm:px-6">
            <div class="overflow-hidden rounded-[2rem] border border-emerald-200/80 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                <div class="border-b border-emerald-100 bg-gradient-to-r from-emerald-50 to-white px-6 py-5 sm:px-8">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 shadow-sm">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-7 w-7">
                                <path d="M20 7L10 17l-5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-emerald-700">Pagamento confirmado</p>
                            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Encomenda feita com sucesso</h1>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-8 sm:px-8">
                    <p class="max-w-2xl text-base leading-7 text-slate-600">
                        O pagamento foi confirmado e a tua encomenda ficou registada com sucesso. Podes consultar o estado da encomenda abaixo ou voltar a explorar os livros.
                    </p>

                    <div class="mt-8 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Encomenda</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">#{{ $encomenda->id }}</p>
                        </div>
                        <div class="rounded-2xl border {{ $estadoClasses }} p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</p>
                            <p class="mt-2 text-lg font-semibold {{ $encomenda->estado === 'enviado' ? 'text-blue-700' : 'text-slate-900' }}">{{ $estadoTexto }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">{{ number_format((float) $encomenda->total, 2, ',', '.') }} &euro;</p>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                        <a href="{{ route('cidadao.encomendas.show', $encomenda) }}" class="inline-flex items-center justify-center rounded-xl bg-black px-5 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                            Ver encomenda
                        </a>
                        <a href="{{ route('livros.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Continuar a comprar
                        </a>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-transparent px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">
                            Ir para o painel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
