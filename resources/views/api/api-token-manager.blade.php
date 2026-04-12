<div>
    <!-- Gerar Token de API -->
    {{-- Formulário inicial para criar novos tokens associados ao utilizador autenticado. --}}
    <x-form-section submit="createApiToken">
        <x-slot name="title">
            {{ __('Criar Token de API') }}
        </x-slot>

        <x-slot name="description">
            {{ __('Os tokens de API permitem que servicos externos se autentiquem na aplicacao em seu nome.') }}
        </x-slot>

        <x-slot name="form">
            <!-- Nome do Token -->
            {{-- Campo obrigatório para identificar o token na listagem. --}}
            <div class="col-span-6 sm:col-span-4">
                <x-label for="name" value="{{ __('Nome do Token') }}" />
                <x-input id="name" type="text" class="mt-1 block w-full" wire:model="createApiTokenForm.name" autofocus />
                <x-input-error for="name" class="mt-2" />
            </div>

            <!-- Permissoes do Token -->
            @if (Laravel\Jetstream\Jetstream::hasPermissions())
                {{-- Permissões opcionais exibidas apenas quando Jetstream as tiver ativadas. --}}
                <div class="col-span-6">
                    <x-label for="permissions" value="{{ __('Permissoes') }}" />

                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach (Laravel\Jetstream\Jetstream::$permissions as $permission)
                            <label class="flex items-center">
                                <x-checkbox wire:model="createApiTokenForm.permissions" :value="$permission"/>
                                <span class="ms-2 text-sm text-gray-600">{{ $permission }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="actions">
            {{-- Feedback visual quando o token é criado com sucesso. --}}
            <x-action-message class="me-3" on="created">
                {{ __('Criado.') }}
            </x-action-message>

            {{-- Dispara a ação Livewire para gerar um novo token. --}}
            <x-button>
                {{ __('Criar') }}
            </x-button>
        </x-slot>
    </x-form-section>

    @if ($this->user->tokens->isNotEmpty())
        <x-section-border />

        <!-- Gerir Tokens de API -->
        {{-- Secção de gestão para rever utilização, editar permissões e eliminar tokens. --}}
        <div class="mt-10 sm:mt-0">
            <x-action-section>
                <x-slot name="title">
                    {{ __('Gerir Tokens de API') }}
                </x-slot>

                <x-slot name="description">
                    {{ __('Pode eliminar qualquer token existente que ja nao seja necessario.') }}
                </x-slot>

                <!-- Lista de Tokens de API -->
                <x-slot name="content">
                    {{-- Ordena tokens por nome para facilitar procura visual. --}}
                    <div class="space-y-6">
                        @foreach ($this->user->tokens->sortBy('name') as $token)
                            <div class="flex items-center justify-between">
                                <div class="break-all">
                                    {{ $token->name }}
                                </div>

                                <div class="flex items-center ms-2">
                                    @if ($token->last_used_at)
                                        {{-- Mostra atividade recente para ajudar auditoria de uso. --}}
                                        <div class="text-sm text-gray-400">
                                            {{ __('Ultima utilizacao') }} {{ $token->last_used_at->diffForHumans() }}
                                        </div>
                                    @endif

                                    @if (Laravel\Jetstream\Jetstream::hasPermissions())
                                        {{-- Abre modal para ajustar permissões do token selecionado. --}}
                                        <button class="cursor-pointer ms-6 text-sm text-gray-400 underline" wire:click="manageApiTokenPermissions({{ $token->id }})">
                                            {{ __('Permissoes') }}
                                        </button>
                                    @endif

                                    {{-- Solicita confirmação antes de eliminar definitivamente o token. --}}
                                    <button class="cursor-pointer ms-6 text-sm text-red-500" wire:click="confirmApiTokenDeletion({{ $token->id }})">
                                        {{ __('Eliminar') }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-slot>
            </x-action-section>
        </div>
    @endif

    <!-- Modal de Valor do Token -->
    {{-- Mostra o token gerado apenas uma vez para cópia segura. --}}
    <x-dialog-modal wire:model.live="displayingToken">
        <x-slot name="title">
            {{ __('Token de API') }}
        </x-slot>

        <x-slot name="content">
            <div>
                {{ __('Copie o seu novo token de API. Por seguranca, nao sera mostrado novamente.') }}
            </div>

            <x-input x-ref="plaintextToken" type="text" readonly :value="$plainTextToken"
                class="mt-4 bg-gray-100 px-4 py-2 rounded font-mono text-sm text-gray-500 w-full break-all"
                autofocus autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                @showing-token-modal.window="setTimeout(() => $refs.plaintextToken.select(), 250)"
            />
        </x-slot>

        <x-slot name="footer">
            {{-- Fecha modal após cópia do token em texto simples. --}}
            <x-secondary-button wire:click="$set('displayingToken', false)" wire:loading.attr="disabled">
                {{ __('Fechar') }}
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Modal de Permissoes do Token de API -->
    {{-- Modal para atualizar permissões de um token existente. --}}
    <x-dialog-modal wire:model.live="managingApiTokenPermissions">
        <x-slot name="title">
            {{ __('Permissoes do Token de API') }}
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach (Laravel\Jetstream\Jetstream::$permissions as $permission)
                    <label class="flex items-center">
                        <x-checkbox wire:model="updateApiTokenForm.permissions" :value="$permission"/>
                        <span class="ms-2 text-sm text-gray-600">{{ $permission }}</span>
                    </label>
                @endforeach
            </div>
        </x-slot>

        <x-slot name="footer">
            {{-- Cancela edição de permissões sem persistir alterações. --}}
            <x-secondary-button wire:click="$set('managingApiTokenPermissions', false)" wire:loading.attr="disabled">
                {{ __('Cancelar') }}
            </x-secondary-button>

            {{-- Persiste permissões selecionadas para o token atual. --}}
            <x-button class="ms-3" wire:click="updateApiToken" wire:loading.attr="disabled">
                {{ __('Guardar') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Modal de Confirmacao para Eliminar Token -->
    {{-- Confirmação final para prevenir remoção acidental de tokens. --}}
    <x-confirmation-modal wire:model.live="confirmingApiTokenDeletion">
        <x-slot name="title">
            {{ __('Eliminar Token de API') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Tem a certeza de que pretende eliminar este token de API?') }}
        </x-slot>

        <x-slot name="footer">
            {{-- Fecha confirmação sem remover o token selecionado. --}}
            <x-secondary-button wire:click="$toggle('confirmingApiTokenDeletion')" wire:loading.attr="disabled">
                {{ __('Cancelar') }}
            </x-secondary-button>

            {{-- Confirma eliminação definitiva do token de API. --}}
            <x-danger-button class="ms-3" wire:click="deleteApiToken" wire:loading.attr="disabled">
                {{ __('Eliminar') }}
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>
</div>



