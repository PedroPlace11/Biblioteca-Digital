<x-app-layout>
    {{-- Cabeçalho da página de gestão de tokens de API. --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tokens de API') }}
        </h2>
    </x-slot>

    {{-- Conteúdo principal: componente Livewire com criação/revogação de tokens. --}}
    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            {{-- Componente responsável por listar, criar, editar permissões e eliminar tokens. --}}
            @livewire('api.api-token-manager')
        </div>
    </div>
</x-app-layout>



