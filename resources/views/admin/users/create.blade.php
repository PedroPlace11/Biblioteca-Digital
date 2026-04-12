<x-app-layout>
    <div class="py-8">
        <div class="max-w-md mx-auto">
                {{-- Wrapper central para manter o formulário focado em telas de gestão. --}}
                {{-- Estilos locais desta tela de criação de administrador. --}}
                <style>
                    /* Cartão principal da modal com sombra e cantos arredondados. */
                    .modal-card {
                        background: white;
                        border-radius: 12px;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
                        overflow: hidden;
                    }

                    .modal-header {
                        /* Cabeçalho com título e ação de fechar no mesmo eixo. */
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 2rem;
                    }

                    .modal-header h2 {
                        font-size: 1.75rem;
                        color: #333;
                        margin: 0;
                        font-weight: 700;
                    }

                    .close-modal {
                        background: #f0f0f0;
                        border: none;
                        width: 2.5rem;
                        height: 2.5rem;
                        border-radius: 50%;
                        color: #333;
                        font-size: 1.5rem;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }

                    .close-modal:hover {
                        background: #e0e0e0;
                        transform: rotate(90deg);
                    }

                    .form-group {
                        margin-bottom: 1.5rem;
                    }

                    .form-group label {
                        display: block;
                        color: #555;
                        font-weight: 500;
                        margin-bottom: 0.6rem;
                        font-size: 0.95rem;
                    }

                    .form-group input {
                        width: 100%;
                        padding: 0.85rem 1rem;
                        border: 2px solid #111;
                        border-radius: 8px;
                        background: #fff;
                        color: #111;
                        font-size: 1rem;
                        transition: border-color 0.25s ease, box-shadow 0.25s ease, transform 0.25s ease;
                        font-family: inherit;
                    }

                    /* Efeito curto de destaque visual quando o campo recebe foco. */
                    @keyframes inputFocusPulse {
                        0% {
                            box-shadow: 0 0 0 0 rgba(17, 17, 17, 0);
                        }
                        50% {
                            box-shadow: 0 0 0 6px rgba(17, 17, 17, 0.2);
                        }
                        100% {
                            box-shadow: 0 0 0 4px rgba(17, 17, 17, 0.15);
                        }
                    }

                    .form-group input:focus {
                        outline: none;
                        border-color: #111;
                        background: #fff;
                        color: #111;
                        box-shadow: 0 0 0 4px rgba(17, 17, 17, 0.15);
                        transform: translateY(-1px);
                        animation: inputFocusPulse 0.28s ease-out;
                    }

                    .form-actions {
                        /* Linha de ações com alinhamento à direita para confirmar/cancelar. */
                        display: flex;
                        gap: 1rem;
                        justify-content: flex-end;
                        margin-top: 2rem;
                    }

                    .btn-primary {
                        padding: 0.6rem 1.4rem;
                        background: #111;
                        color: white;
                        border: none;
                        border-radius: 8px;
                        font-size: 0.9rem;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        font-family: inherit;
                    }

                    .btn-primary:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
                        background: #000;
                    }

                    .error-message {
                        background: #fee;
                        color: #c33;
                        padding: 1rem;
                        border-radius: 8px;
                        margin-bottom: 1.5rem;
                        font-size: 0.9rem;
                        border-left: 4px solid #c33;
                    }

                    .success-message {
                        background: #efe;
                        color: #3c3;
                        padding: 1rem;
                        border-radius: 8px;
                        margin-bottom: 1.5rem;
                        font-size: 0.9rem;
                        border-left: 4px solid #3c3;
                    }
                </style>

                {{-- Estrutura da modal que contém o formulário de criação do admin. --}}
                <div class="modal-card">
                    <div style="padding: 2.5rem;">
                        <div class="modal-header">
                            <h2>Criar Admin</h2>
                            <a href="{{ route('admins.index') }}" class="close-modal" title="Fechar">&times;</a>
                        </div>

                        {{-- Mensagem de sucesso após criação do utilizador. --}}
                        @if (session('success'))
                            <div class="success-message">
                                {{ session('success') }}
                            </div>
                        @endif

                        {{-- Lista de erros de validação do formulário. --}}
                        @if ($errors->any())
                            <div class="error-message">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Formulário principal para registar um novo administrador. --}}
                        <form method="POST" action="{{ route('admins.store') }}">
                            @csrf

                            <div class="form-group">
                                {{-- Campo de email do novo administrador. --}}
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                            </div>

                            <div class="form-group">
                                {{-- Campo de senha inicial da conta admin. --}}
                                <label for="password">Senha</label>
                                <input type="password" id="password" name="password" required autocomplete="new-password">
                            </div>

                            <div class="form-group">
                                {{-- Confirmação de senha para validação de consistência. --}}
                                <label for="password_confirmation">Confirmar Senha</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Salvar</button>
                                <a href="{{ route('admins.index') }}" class="btn btn-ghost">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>



