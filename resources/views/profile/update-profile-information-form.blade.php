<x-form-section submit="updateProfileInformation">
    <x-slot name="title">
        Informações do Perfil
    </x-slot>

    <x-slot name="description">
        Atualize as informações do seu perfil e o seu endereço de email.
    </x-slot>

    <x-slot name="form">
        <!-- Fotografia de Perfil -->
        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
            <div x-data="{photoName: null, photoPreview: null}" class="col-span-6 sm:col-span-4">
                <!-- Input de Ficheiro para Fotografia de Perfil -->
                <input type="file" id="photo" class="hidden"
                            wire:model.live="photo"
                            x-ref="photo"
                            x-on:change="
                                    photoName = $refs.photo.files[0].name;
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        photoPreview = e.target.result;
                                    };
                                    reader.readAsDataURL($refs.photo.files[0]);
                            " />

                <x-label for="photo" value="Fotografia" />

                <!-- Fotografia de Perfil Atual -->
                <div class="mt-2" x-show="! photoPreview">
                    @if ($this->user->profile_photo_path)
                        <img src="{{ asset('storage/'.$this->user->profile_photo_path) }}" alt="{{ $this->user->name }}" class="rounded-full size-20 object-cover border-2 border-slate-200">
                    @else
                        <img src="{{ $this->user->profile_photo_url }}" alt="{{ $this->user->name }}" class="rounded-full size-20 object-cover border-2 border-slate-200">
                    @endif
                </div>

                <!-- Pré-visualização da Nova Fotografia de Perfil -->
                <div class="mt-2" x-show="photoPreview" style="display: none;">
                    <span class="block rounded-full size-20 bg-cover bg-no-repeat bg-center border-2 border-slate-200"
                          x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
                    </span>
                </div>

                <p class="text-xs text-slate-500 mt-2" x-show="photoName" style="display: none;">
                    Nova imagem: <span class="font-medium" x-text="photoName"></span>
                </p>

                <x-secondary-button class="mt-3 me-2" type="button" x-on:click.prevent="$refs.photo.click()">
                    Selecionar nova fotografia
                </x-secondary-button>

                @if ($this->user->profile_photo_path)
                    <x-secondary-button type="button" class="mt-3" wire:click="deleteProfilePhoto">
                        Remover fotografia
                    </x-secondary-button>
                @endif

                <x-input-error for="photo" class="mt-2" />
            </div>
        @endif

        <!-- Nome -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="name" value="Nome" />
            <x-input id="name" type="text" class="mt-1 block w-full" wire:model="state.name" required autocomplete="name" />
            <x-input-error for="name" class="mt-2" />
        </div>

        <!-- Email -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="email" value="{{ __('Email') }}" />
            <x-input id="email" type="email" class="mt-1 block w-full" wire:model="state.email" required autocomplete="username" />
            <x-input-error for="email" class="mt-2" />

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()) && ! $this->user->hasVerifiedEmail())
                <p class="text-sm mt-2">
                    O seu endereço de email não está verificado.

                    <button type="button" class="underline text-sm text-slate-600 hover:text-slate-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500" wire:click.prevent="sendEmailVerification">
                        Clique aqui para reenviar o email de verificação.
                    </button>
                </p>

                @if ($this->verificationLinkSent)
                    <p class="mt-2 font-medium text-sm text-emerald-600">
                        Um novo link de verificação foi enviado para o seu endereço de email.
                    </p>
                @endif
            @endif
        </div>

        <!-- Numero de Leitor (apenas leitura) -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="numero_leitor" value="N.º de Leitor" />
            <x-input
                id="numero_leitor"
                type="text"
                class="mt-1 block w-full bg-slate-50 text-slate-600"
                value="{{ $this->user->numero_leitor ?? '-' }}"
                readonly
                disabled
            />
            <p class="mt-2 text-xs text-slate-500">Este número é atribuído automaticamente e não pode ser alterado.</p>
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-action-message class="me-3" on="saved">
            Guardado.
        </x-action-message>

        <x-button wire:loading.attr="disabled" wire:target="photo">
            Guardar
        </x-button>
    </x-slot>
</x-form-section>



