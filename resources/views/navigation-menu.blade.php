{{-- Definição de variáveis para identificar qual rota está ativa, usadas para destacar o menu --}}
@php
    $isDashboard = request()->routeIs('dashboard');
    $isLivros = request()->routeIs('livros.*');
    $isCarrinho = request()->routeIs('carrinho.*') || request()->routeIs('checkout.*');
    $isRequisicoes = request()->routeIs('requisicoes.*');
    $isAutores = request()->routeIs('autores.*');
    $isEditoras = request()->routeIs('editoras.*');
    $isProfile = request()->routeIs('profile.show');
    $isCidadaoMoradas = request()->routeIs('cidadao.moradas.*');
    $isCidadaoEncomendas = request()->routeIs('cidadao.encomendas.*');
    $isAdminsManage = request()->routeIs('admins.index');
    $isAdminsCreate = request()->routeIs('admins.create');
    $isAdminEncomendas = request()->routeIs('admin.encomendas.*');
    $cartCount = 0;
    $cartSchemaReady = false;
    $cartPreviewItems = collect();
    $cartTotal = 0;

    if (auth()->check() && auth()->user()->role === 'cidadao') {
        try {
            $cartSchemaReady = \Illuminate\Support\Facades\Schema::hasTable('carrinhos')
                && \Illuminate\Support\Facades\Schema::hasTable('carrinho_itens');
        } catch (\Throwable $e) {
            $cartSchemaReady = false;
        }

        if ($cartSchemaReady) {
            $carrinho = auth()->user()->carrinho()->with('itens.livro')->first();

            if ($carrinho) {
                $cartCount = (int) $carrinho->itens->sum('quantidade');
                $cartPreviewItems = $carrinho->itens->take(5);
                $cartTotal = (float) $carrinho->itens->sum(fn ($item) => (float) $item->subtotal);
            }
        }
    }
@endphp

<nav class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="h-16 grid grid-cols-[auto_1fr_auto] items-center gap-6">
            {{-- Logo e link para dashboard ou home --}}
            <div class="flex items-center">
                <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="shrink-0">
                    <x-application-mark class="block h-9 w-auto" />
                </a>
            </div>

            {{-- Navegação principal (desktop) --}}
            <div class="hidden sm:flex items-center justify-center gap-2">
                                @auth
                                @endauth
                @auth
                    {{-- Link para painel do usuário --}}
                    <a href="{{ route('dashboard') }}"
                        class="px-3 py-2 rounded-xl text-sm font-semibold transition {{ $isDashboard ? 'bg-sky-100 text-sky-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }}">
                        Painel
                    </a>
                @endauth
                {{-- Link para listagem de livros --}}
                <a href="{{ route('livros.index') }}"
                    class="px-3 py-2 rounded-xl text-sm font-semibold transition {{ $isLivros ? 'bg-sky-100 text-sky-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }}">
                    Livros
                </a>
                @auth
                    {{-- Link para requisições (apenas autenticado) --}}
                    <a href="{{ route('requisicoes.index') }}"
                        class="px-3 py-2 rounded-xl text-sm font-semibold transition {{ $isRequisicoes ? 'bg-sky-100 text-sky-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }}">
                        Requisição
                    </a>
                @endauth
                {{-- Link para autores --}}
                <a href="{{ route('autores.index') }}"
                    class="px-3 py-2 rounded-xl text-sm font-semibold transition {{ $isAutores ? 'bg-sky-100 text-sky-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }}">
                    Autores
                </a>
                {{-- Link para editoras --}}
                <a href="{{ route('editoras.index') }}"
                    class="px-3 py-2 rounded-xl text-sm font-semibold transition {{ $isEditoras ? 'bg-sky-100 text-sky-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }}">
                    Editoras
                </a>
            </div>

            {{-- Bloco de ações do usuário (direita, desktop) --}}
            <div class="hidden sm:flex items-center justify-end sm:ms-6">
                @auth
                {{-- Dropdown de equipes (se habilitado no Jetstream) --}}
                @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
                    <div class="ms-3 relative">
                        <x-dropdown align="right" width="60">
                            <x-slot name="trigger">
                                <span class="inline-flex rounded-md">
                                    <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                                        {{ Auth::user()->currentTeam->name }}

                                        <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                        </svg>
                                    </button>
                                </span>
                            </x-slot>

                            <x-slot name="content">
                                <div class="w-60">
                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        {{ __('Gerir Equipa') }}
                                    </div>

                                    <x-dropdown-link href="{{ route('teams.show', Auth::user()->currentTeam->id) }}">
                                        {{ __('Definicoes da Equipa') }}
                                    </x-dropdown-link>

                                    @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                                        <x-dropdown-link href="{{ route('teams.create') }}">
                                            {{ __('Criar Nova Equipa') }}
                                        </x-dropdown-link>
                                    @endcan

                                    @if (Auth::user()->allTeams()->count() > 1)
                                        <div class="border-t border-gray-200"></div>

                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            {{ __('Trocar de Equipa') }}
                                        </div>

                                        @foreach (Auth::user()->allTeams() as $team)
                                            <x-switchable-team :team="$team" />
                                        @endforeach
                                    @endif
                                </div>
                            </x-slot>
                        </x-dropdown>
                    </div>
                @endif

                {{-- Dropdown de notificações do usuário --}}
                @php
                    $unreadNotificationsCount = Auth::user()->unreadNotifications()->count();
                    $topNotifications = Auth::user()->notifications()->latest()->take(8)->get();
                @endphp

                <div class="ms-3 relative">
                    <x-dropdown align="right" width="96" contentClasses="p-0 bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden" dropdownClasses="mt-3">
                        <x-slot name="trigger">
                            <button type="button" class="relative inline-flex items-center justify-center size-9 rounded-full border border-slate-200 text-slate-600 hover:bg-slate-100 transition" aria-label="Notificações">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9a6 6 0 1 0-12 0v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                </svg>
                                @if ($unreadNotificationsCount > 0)
                                    <span class="absolute -top-1 -right-1 min-w-[1.15rem] h-[1.15rem] px-1 rounded-full bg-rose-600 text-white text-[10px] leading-[1.15rem] font-bold text-center">
                                        {{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}
                                    </span>
                                @endif
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="w-full max-h-[440px] overflow-hidden flex flex-col bg-white">
                                {{-- Cabeçalho do dropdown de notificações --}}
                                <div class="px-4 py-3.5 border-b border-slate-200 bg-slate-50/70">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span class="inline-flex items-center justify-center size-8 rounded-lg bg-slate-900 text-white shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9a6 6 0 1 0-12 0v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                            </svg>
                                        </span>
                                            <div class="min-w-0">
                                                <p class="text-base font-bold tracking-[0.02em] text-slate-700">Notificações</p>
                                                <p class="text-sm text-slate-500 leading-5">Confirmações de requisições</p>
                                            </div>
                                        </div>
                                        @if ($unreadNotificationsCount > 0)
                                            <div class="flex flex-col items-end gap-1 shrink-0">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-sky-100 text-sky-700">
                                                {{ $unreadNotificationsCount }} nova{{ $unreadNotificationsCount > 1 ? 's' : '' }}
                                            </span>
                                            </div>
                                        @endif
                                    </div>

                                    @if ($unreadNotificationsCount > 0)
                                        <div class="mt-2 flex justify-end">
                                            <form method="POST" action="{{ route('notifications.read-all') }}">
                                                @csrf
                                                <button type="submit" class="text-sm font-semibold text-sky-700 hover:text-sky-800 underline underline-offset-2">Marcar todas</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>

                                {{-- Lista de notificações recentes --}}
                                <div class="overflow-y-auto divide-y divide-slate-100">
                                    @forelse ($topNotifications as $notification)
                                        <div class="px-4 py-3 transition hover:bg-slate-50 {{ is_null($notification->read_at) ? 'bg-sky-50/50' : '' }}">
                                            <div class="flex items-start justify-between gap-2">
                                                <p class="text-sm font-semibold text-slate-800 leading-5">
                                                    {{ $notification->data['title'] ?? 'Notificação' }}
                                                </p>
                                                @if (is_null($notification->read_at))
                                                    <span class="mt-1 size-2 rounded-full bg-sky-500"></span>
                                                @endif
                                            </div>
                                            <p class="text-sm text-slate-600 mt-1 leading-5">
                                                {{ $notification->data['message'] ?? 'Nova atualização de requisição.' }}
                                            </p>
                                            @if (!empty($notification->data['cidadao_numero_leitor']))
                                                <p class="text-xs text-slate-500 mt-1">N.º leitor: {{ $notification->data['cidadao_numero_leitor'] }}</p>
                                            @endif
                                            <div class="mt-2 flex items-center justify-between gap-2">
                                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                                    @csrf
                                                    @php
                                                        $isReview = isset($notification->data['title']) && str_contains(Str::lower($notification->data['title']), 'review');
                                                        $isConfirmacao = isset($notification->data['title']) && str_contains(Str::lower($notification->data['title']), 'confirmação de requisição');
                                                        $isRecepcao = isset($notification->data['title']) && str_contains(Str::lower($notification->data['title']), 'receção confirmada');
                                                        $isDevolucao = isset($notification->data['title']) && str_contains(Str::lower($notification->data['title']), 'pedido de devolução');
                                                        $isLivroDisponivel = isset($notification->data['title']) && str_contains(Str::lower($notification->data['title']), 'livro disponível');
                                                        $isCarrinhoNotif = isset($notification->data['title']) && str_contains(Str::lower($notification->data['title']), 'carrinho');
                                                        $isEncomendaNotif = isset($notification->data['title']) && str_contains(Str::lower($notification->data['title']), 'encomenda');
                                                        $isPagamentoNotif = isset($notification->data['title']) && str_contains(Str::lower($notification->data['title']), 'pagamento');
                                                        $encomendaUrl = $notification->data['encomenda_url'] ?? null;
                                                        $reviewUrl = $notification->data['review_url'] ?? null;
                                                        $livroUrl = $notification->data['livro_url'] ?? null;
                                                        $carrinhoUrl = $notification->data['carrinho_url'] ?? null;
                                                        $adminReviewsUrl = route('admin.reviews.index');
                                                    @endphp
                                                    <input type="hidden" name="redirect_to" value="
                                                        @if($isReview && Auth::user()->role === 'admin' && $reviewUrl)
                                                            {{ $reviewUrl }}
                                                        @elseif($isReview && Auth::user()->role === 'cidadao' && $reviewUrl)
                                                            {{ $reviewUrl }}
                                                        @elseif($isReview)
                                                            {{ route('cidadao.reviews.index') }}
                                                        @elseif($isRecepcao && $livroUrl)
                                                            {{ $livroUrl }}
                                                        @elseif($isLivroDisponivel && $livroUrl)
                                                            {{ $livroUrl }}
                                                        @elseif($isCarrinhoNotif && $carrinhoUrl)
                                                            {{ $carrinhoUrl }}
                                                        @elseif(($isEncomendaNotif || $isPagamentoNotif) && $encomendaUrl)
                                                            {{ $encomendaUrl }}
                                                        @elseif(($isConfirmacao || $isDevolucao) && $livroUrl)
                                                            {{ $livroUrl }}
                                                        @else
                                                            {{ url()->current() }}
                                                        @endif
                                                    ">
                                                    <button type="submit" class="text-xs font-semibold text-sky-700 hover:text-sky-800">Ver detalhes</button>
                                                </form>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[11px] text-slate-400">{{ $notification->created_at?->diffForHumans() }}</span>
                                                    @if (is_null($notification->read_at))
                                                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                                            @csrf
                                                            <button type="submit" class="text-[11px] font-semibold text-slate-600 hover:text-slate-800">Lida</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        {{-- Estado vazio quando não há notificações --}}
                                        <div class="px-5 py-10 flex flex-col items-center justify-center text-center">
                                            <div class="mb-3 flex items-center justify-center size-10 rounded-full bg-slate-100 text-slate-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9a6 6 0 1 0-12 0v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                                </svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-700 w-full">Sem notificações</p>
                                            <p class="text-xs text-slate-500 mt-1 w-full leading-5">As novas confirmações de requisições aparecerão aqui.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>

                @if (Auth::user()->role === 'cidadao' && $cartSchemaReady)
                    <div class="ms-3 relative group">
                        <a href="{{ route('carrinho.index') }}"
                            class="relative inline-flex items-center justify-center size-9 rounded-full border transition overflow-visible {{ $isCarrinho ? 'border-sky-300 bg-sky-50 text-sky-700' : 'border-slate-200 text-slate-600 hover:bg-slate-100' }}"
                            aria-label="Carrinho">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386a1.5 1.5 0 0 1 1.464 1.175L5.4 5.5m0 0h13.95a.75.75 0 0 1 .73.923l-1.2 5.25a.75.75 0 0 1-.73.577H7.05m-1.65-6.75 1.65 6.75m0 0a2.25 2.25 0 1 0 0 4.5h9.9a2.25 2.25 0 1 0 0-4.5H7.05Z" />
                            </svg>
                            @if ($cartCount > 0)
                                <span class="absolute -top-2 -right-2 min-w-5 h-5 px-1 rounded-full bg-rose-600 text-white text-[10px] leading-5 font-bold text-center shadow-md ring-2 ring-white">
                                    {{ $cartCount > 9 ? '9+' : $cartCount }}
                                </span>
                            @endif
                        </a>

                        <div class="absolute right-0 top-11 z-50 w-80 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl opacity-0 pointer-events-none transition duration-150 group-hover:opacity-100 group-hover:pointer-events-auto group-focus-within:opacity-100 group-focus-within:pointer-events-auto">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-800">Carrinho</p>
                                <span class="text-xs text-slate-500">{{ $cartCount }} item(ns)</span>
                            </div>

                            @if ($cartPreviewItems->isEmpty())
                                <p class="mt-3 text-sm text-slate-500">O carrinho está vazio.</p>
                            @else
                                <div class="mt-3 max-h-64 overflow-y-auto divide-y divide-slate-100">
                                    @foreach ($cartPreviewItems as $cartItem)
                                        <div class="py-2">
                                            <p class="text-sm font-medium text-slate-800 truncate">{{ $cartItem->livro?->nome ?? 'Livro removido' }}</p>
                                            <p class="text-xs text-slate-500">
                                                {{ $cartItem->quantidade }} x {{ number_format((float) $cartItem->preco_unitario, 2, ',', '.') }} &euro;
                                            </p>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-3 border-t border-slate-100 pt-3 flex items-center justify-between">
                                    <span class="text-sm text-slate-500">Total</span>
                                    <span class="text-sm font-semibold text-slate-900">{{ number_format((float) $cartTotal, 2, ',', '.') }} &euro;</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Dropdown de perfil e ações da conta --}}
                <div class="ms-3 relative">
                    <x-dropdown align="right" width="60" contentClasses="p-2 bg-white" dropdownClasses="mt-3">
                        <x-slot name="trigger">
                            @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                                <button class="flex text-sm rounded-full ring-2 ring-transparent hover:ring-slate-200 focus:outline-none focus:ring-slate-300 transition">
                                    @if (Auth::user()->profile_photo_path)
                                        <img class="size-8 rounded-full object-cover" src="{{ asset('storage/'.Auth::user()->profile_photo_path) }}" alt="{{ Auth::user()->name }}" />
                                    @else
                                        <img class="size-8 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                                    @endif
                                </button>
                            @else
                                <span class="inline-flex rounded-md">
                                    <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                                        {{ Auth::user()->name }}

                                        <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                </span>
                            @endif
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-3 pt-2 pb-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">
                                Gerir Conta
                            </div>



                            {{-- Link para perfil do usuário --}}
                            <x-dropdown-link href="{{ route('profile.show') }}" class="rounded-xl px-3 py-2.5 font-medium {{ $isProfile ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                                Perfil
                            </x-dropdown-link>

                            {{-- Link para reviews do cidadão --}}
                            @if (Auth::user()->role === 'cidadao')
                                <x-dropdown-link href="{{ route('cidadao.moradas.index') }}" class="rounded-xl px-3 py-2.5 font-medium {{ $isCidadaoMoradas ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                                    Gerir Moradas
                                </x-dropdown-link>

                                <x-dropdown-link href="{{ route('cidadao.reviews.index') }}" class="rounded-xl px-3 py-2.5 font-medium">
                                    Meus Reviews
                                </x-dropdown-link>

                                <x-dropdown-link href="{{ route('cidadao.encomendas.index') }}" class="rounded-xl px-3 py-2.5 font-medium {{ $isCidadaoEncomendas ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                                    Minhas Encomendas
                                </x-dropdown-link>
                            @endif

                            {{-- Links de administração para admin --}}
                            @if (Auth::user()->role === 'admin')
                                <x-dropdown-link href="{{ route('admins.index') }}" class="rounded-xl px-3 py-2.5 font-medium {{ $isAdminsManage ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                                    Gerir Admins
                                </x-dropdown-link>

                                <x-dropdown-link href="{{ route('admins.create') }}" class="rounded-xl px-3 py-2.5 font-medium {{ $isAdminsCreate ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                                    Criar Admin
                                </x-dropdown-link>

                                <x-dropdown-link href="{{ route('admin.reviews.index') }}" class="rounded-xl px-3 py-2.5 font-medium {{ request()->routeIs('admin.reviews.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                                    Gerir Reviews
                                </x-dropdown-link>

                                <x-dropdown-link href="{{ route('admin.encomendas.index') }}" class="rounded-xl px-3 py-2.5 font-medium {{ $isAdminEncomendas ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                                    Gerir Encomendas
                                </x-dropdown-link>
                            @endif

                            {{-- Link para tokens de API (se habilitado) --}}
                            @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                                <x-dropdown-link href="{{ route('api-tokens.index') }}" class="rounded-xl px-3 py-2.5 font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-900">
                                    {{ __('Tokens de API') }}
                                </x-dropdown-link>
                            @endif

                            <div class="my-2 border-t border-slate-200"></div>

                            {{-- Botão para logout --}}
                            <form method="POST" action="{{ route('logout') }}" x-data>
                                @csrf

                                <x-dropdown-link href="{{ route('logout') }}" class="rounded-xl px-3 py-2.5 font-medium text-rose-600 hover:bg-rose-50 hover:text-rose-700" @click.prevent="$root.submit();">
                                    Terminar Sessão
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
                @else
                {{-- Bloco de autenticação para visitantes (login/registro) --}}
                <div class="flex items-center gap-2">
                    <a href="{{ route('login') }}"
                        class="px-3 py-2 rounded-xl text-sm font-semibold bg-black text-white border border-black hover:bg-neutral-800 transition">
                        Entrar
                    </a>
                    <a href="{{ route('register') }}"
                        class="px-3 py-2 rounded-xl text-sm font-semibold bg-white text-black border border-black hover:bg-gray-100 transition">
                        Registrar
                    </a>
                </div>
                @endauth
            </div>
        </div>
    </div>

    {{-- Barra de navegação inferior (mobile), fixa na tela --}}
    <div class="sm:hidden fixed bottom-4 left-1/2 -translate-x-1/2 w-[92%] max-w-md z-50">
        <div class="relative h-16 rounded-2xl border border-slate-200 bg-white/95 backdrop-blur shadow-[0_14px_35px_-16px_rgba(15,23,42,0.65)] px-3">
            <div class="h-full grid grid-cols-5 items-center">
                @auth
                    {{-- Link para painel (mobile) --}}
                    <a href="{{ route('dashboard') }}"
                        class="flex flex-col items-center justify-center gap-1 transition {{ $isDashboard ? 'text-sky-600' : 'text-slate-500' }}"
                        aria-label="Painel">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 19.5v-9Z" />
                        </svg>
                    </a>
                @else
                    <div></div>
                @endauth

                {{-- Link para autores (mobile) --}}
                @if (auth()->check() && auth()->user()->role === 'cidadao' && $cartSchemaReady)
                    <a href="{{ route('carrinho.index') }}"
                        class="relative flex flex-col items-center justify-center gap-1 transition {{ $isCarrinho ? 'text-sky-600' : 'text-slate-500' }}"
                        aria-label="Carrinho">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386a1.5 1.5 0 0 1 1.464 1.175L5.4 5.5m0 0h13.95a.75.75 0 0 1 .73.923l-1.2 5.25a.75.75 0 0 1-.73.577H7.05m-1.65-6.75 1.65 6.75m0 0a2.25 2.25 0 1 0 0 4.5h9.9a2.25 2.25 0 1 0 0-4.5H7.05Z" />
                        </svg>
                        @if ($cartCount > 0)
                            <span class="absolute -top-1 -right-1 min-w-[1.15rem] h-[1.15rem] px-1 rounded-full bg-rose-600 text-white text-[10px] leading-[1.15rem] font-bold text-center shadow-md ring-2 ring-white">
                                {{ $cartCount > 9 ? '9+' : $cartCount }}
                            </span>
                        @endif
                    </a>
                @else
                    <a href="{{ route('autores.index') }}"
                        class="flex flex-col items-center justify-center gap-1 transition {{ $isAutores ? 'text-sky-600' : 'text-slate-500' }}"
                        aria-label="Autores">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5a6.75 6.75 0 1 0-6 0M3.75 20.25a8.25 8.25 0 0 1 16.5 0" />
                        </svg>
                    </a>
                @endif

                {{-- Botão central para livros (mobile), com destaque visual --}}
                <a href="{{ route('livros.index') }}"
                    class="absolute left-1/2 -translate-x-1/2 -top-5 size-14 rounded-full border-4 border-white bg-gradient-to-b from-sky-500 to-blue-600 text-white shadow-[0_12px_24px_-10px_rgba(37,99,235,0.95)] flex items-center justify-center transition hover:scale-105 {{ $isLivros ? 'ring-4 ring-sky-100' : '' }}"
                    aria-label="Livros">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 5.25A2.25 2.25 0 0 1 6.75 3h10.5A2.25 2.25 0 0 1 19.5 5.25v13.5A2.25 2.25 0 0 1 17.25 21H6.75A2.25 2.25 0 0 1 4.5 18.75V5.25Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5h7.5M8.25 11.25h7.5M8.25 15h4.5" />
                    </svg>
                </a>

                {{-- Link para editoras (mobile) --}}
                <a href="{{ route('editoras.index') }}"
                    class="flex flex-col items-center justify-center gap-1 transition {{ $isEditoras ? 'text-sky-600' : 'text-slate-500' }}"
                    aria-label="Editoras">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M4.5 21V9.75m15 11.25V9.75M9 21V3h6v18" />
                    </svg>
                </a>

                @auth
                    {{-- Link para perfil (mobile) --}}
                    <a href="{{ route('profile.show') }}"
                        class="flex flex-col items-center justify-center gap-1 transition {{ $isProfile ? 'text-sky-600' : 'text-slate-500' }}"
                        aria-label="Perfil">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.964 0A9 9 0 1 0 6.018 18.725m11.964 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275" />
                        </svg>
                    </a>
                @else
                    <div></div>
                @endauth
            </div>

            @auth
                @if (auth()->user()->role === 'cidadao')
                    <div class="absolute -top-11 right-2">
                        <a href="{{ route('cidadao.encomendas.index') }}"
                            class="px-3 py-1.5 text-xs font-semibold rounded-full border transition {{ $isCidadaoEncomendas ? 'bg-sky-100 text-sky-700 border-sky-200' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                            Encomendas
                        </a>
                    </div>
                @endif
            @endauth
        </div>
    </div>
</nav>



