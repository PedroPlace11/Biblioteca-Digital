<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        {{-- Metadados básicos do documento --}}
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Token CSRF para requisições protegidas no frontend --}}
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Define nome da aplicação e título da página com base na rota atual --}}
        @php
            $configuredName = config('app.name');
            // Normaliza o nome da aplicação quando a configuração padrão ainda está ativa.
            $appName = empty($configuredName) || $configuredName === 'Laravel' ? 'Biblioteca' : $configuredName;
            $routeName = request()->route()?->getName();
            // Associa nomes de rotas a títulos curtos para a aba do navegador.
            $routeTitles = [
                'dashboard' => 'Painel',
                'livros.index' => 'Livros',
                'livros.create' => 'Novo Livro',
                'livros.edit' => 'Editar Livro',
                'livros.show' => 'Detalhe do Livro',
                'autores.index' => 'Autores',
                'autores.create' => 'Novo Autor',
                'autores.edit' => 'Editar Autor',
                'editoras.index' => 'Editoras',
                'editoras.create' => 'Nova Editora',
                'editoras.edit' => 'Editar Editora',
            ];
            $pageTitle = $routeTitles[$routeName] ?? null;
        @endphp

        {{-- Título exibido na aba do navegador --}}
        <title>{{ $pageTitle ? $pageTitle . ' - ' . $appName : $appName }}</title>

        {{-- Favicon da aplicação --}}
        <link rel="icon" type="image/png" href="{{ asset('images/logo/inovcorp.png') }}">
        <!-- Fontes -->

        {{-- Assets de estilo/scripts via CDN --}}
        <!-- Scripts -->
        <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

        {{-- Estilos necessários do Livewire --}}
        <!-- Estilos -->
        @livewireStyles
    </head>

    {{-- Corpo principal da aplicação --}}
    <body class="font-sans antialiased">
        {{-- Banner global do Jetstream --}}
        <x-banner />

        {{-- Container raiz da página --}}
        <div class="min-h-screen bg-gray-100 flex flex-col">
            {{-- Menu de navegação principal --}}
            @livewire('navigation-menu')

            {{-- Cabeçalho opcional de página (quando o slot $header for definido) --}}
            <!-- Cabecalho da Pagina -->
            @if (isset($header))
                {{-- Só renderiza este bloco quando a view filha fornece cabeçalho. --}}
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            {{-- Conteúdo principal da página injetado via slot --}}
            <!-- Conteudo da Pagina -->
            <main class="pb-24 sm:pb-0 flex-1">
                {{-- Compatibiliza views que usam section('content') e componentes com slot. --}}
                @yield('content')
                {{ $slot ?? '' }}
            </main>

            {{-- Rodapé comum a todas as páginas autenticadas. --}}
            <footer class="footer footer-center p-6 bg-base-100 border-t border-gray-100">
                <p>Biblioteca Digital © {{ date('Y') }}</p>
            </footer>
        </div>

        {{-- Pilha para modais empurrados por componentes/visões filhas --}}
        @stack('modals')

        {{-- Scripts necessários do Livewire --}}
        @livewireScripts
    </body>
</html>



