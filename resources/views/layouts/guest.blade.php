<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        {{-- Metadados básicos do documento --}}
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Token CSRF para formulários e requisições seguras --}}
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Define nome da aplicação e título da página conforme rota de autenticação atual --}}
        @php
            $configuredName = config('app.name');
            // Normaliza o nome da aplicação para o layout público também.
            $appName = empty($configuredName) || $configuredName === 'Laravel' ? 'Biblioteca' : $configuredName;
            $routeName = request()->route()?->getName();
            // Usa títulos específicos para cada página de autenticação pública.
            $routeTitles = [
                'login' => 'Login',
                'register' => 'Registar',
                'password.request' => 'Recuperar Palavra-passe',
                'password.reset' => 'Redefinir Palavra-passe',
                'verification.notice' => 'Verificar Email',
                'verification.verify' => 'Verificacao de Email',
                'password.confirm' => 'Confirmar Palavra-passe',
                'two-factor.login' => 'Autenticacao em Dois Fatores',
            ];
            $pageTitle = $routeTitles[$routeName] ?? null;
        @endphp

        {{-- Título exibido na aba do navegador --}}
        <title>{{ $pageTitle ? $pageTitle . ' - ' . $appName : $appName }}</title>

        {{-- Favicon da aplicação --}}
        <link rel="icon" type="image/png" href="{{ asset('images/logo/inovcorp.png') }}">

        {{-- Pré-conexão e carregamento da fonte usada na interface --}}
        <!-- Fontes -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        {{-- Assets compilados via Vite --}}
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Estilos necessários do Livewire --}}
        <!-- Estilos -->
        @livewireStyles
    </head>

    {{-- Layout para páginas públicas/guest (login, registo, recuperação de senha, etc.) --}}
    <body>
        {{-- Slot principal onde o conteúdo da view convidada é renderizado --}}
        <div class="font-sans text-gray-900 antialiased min-h-screen flex flex-col">
            {{-- Aqui entra o conteúdo de login, registo e recuperação de senha. --}}
            {{ $slot }}

            {{-- Rodapé simples partilhado pelas páginas públicas. --}}
            <footer class="footer footer-center p-6 bg-base-100 border-t border-gray-100 mt-auto">
                <p>Biblioteca Digital © {{ date('Y') }}</p>
            </footer>
        </div>

        {{-- Scripts necessários do Livewire --}}
        @livewireScripts
    </body>
</html>



