<x-app-layout>
    <div class="py-10 px-4">
        <div class="max-w-6xl mx-auto">
            <style>
                /* ── Botão de voltar ── */
                .mg-back-btn {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.4rem;
                    font-size: 0.875rem;
                    font-weight: 500;
                    color: #6b7280;
                    text-decoration: none;
                    margin-bottom: 1.75rem;
                    transition: color 0.15s;
                }
                .mg-back-btn:hover { color: #111827; }

                /* ── Cabeçalho da página ── */
                .mg-page-header {
                    display: flex;
                    align-items: flex-start;
                    justify-content: space-between;
                    gap: 1rem;
                    margin-bottom: 2rem;
                }
                .mg-page-title {
                    font-size: 1.75rem;
                    font-weight: 700;
                    color: #111827;
                    letter-spacing: -0.02em;
                    margin: 0;
                }
                .mg-page-subtitle {
                    color: #6b7280;
                    font-size: 0.9rem;
                    margin-top: 0.3rem;
                }
                .mg-create-btn {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.45rem;
                    padding: 0.65rem 1.15rem;
                    border-radius: 10px;
                    background: #111827;
                    color: white;
                    font-size: 0.875rem;
                    font-weight: 600;
                    text-decoration: none;
                    white-space: nowrap;
                    transition: background 0.15s;
                    flex-shrink: 0;
                }
                .mg-create-btn:hover { background: #1f2937; }

                /* ── Alertas visuais ── */
                .mg-alert {
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    padding: 0.9rem 1.1rem;
                    border-radius: 10px;
                    margin-bottom: 1.5rem;
                    font-size: 0.9rem;
                    font-weight: 500;
                }
                .mg-alert-success {
                    background: #ecfdf5;
                    color: #065f46;
                    border: 1px solid #a7f3d0;
                }
                .mg-alert-error {
                    background: #fef2f2;
                    color: #991b1b;
                    border: 1px solid #fecaca;
                }
                .mg-alert-icon {
                    width: 18px;
                    height: 18px;
                    flex-shrink: 0;
                }

                /* ── Notificação de sucesso ── */
                .mg-success-toast {
                    position: fixed;
                    top: 1.25rem;
                    right: 1.25rem;
                    z-index: 70;
                    width: min(92vw, 380px);
                    border-radius: 12px;
                    border: 1px solid #a7f3d0;
                    background: #ecfdf5;
                    color: #065f46;
                    box-shadow: 0 14px 30px rgba(0, 0, 0, 0.12);
                    opacity: 1;
                    transform: translateY(0);
                    transition: opacity 0.25s ease, transform 0.25s ease;
                }
                .mg-success-toast.is-hidden {
                    opacity: 0;
                    transform: translateY(-8px);
                    pointer-events: none;
                }
                .mg-success-toast-body {
                    display: flex;
                    align-items: flex-start;
                    gap: 0.65rem;
                    padding: 0.85rem 0.9rem;
                }
                .mg-success-toast-icon {
                    width: 18px;
                    height: 18px;
                    flex-shrink: 0;
                    margin-top: 0.1rem;
                }
                .mg-success-toast-text {
                    font-size: 0.9rem;
                    font-weight: 600;
                    line-height: 1.35;
                    flex: 1;
                }
                .mg-success-toast-close {
                    border: none;
                    background: transparent;
                    color: #065f46;
                    font-size: 1.05rem;
                    line-height: 1;
                    cursor: pointer;
                    padding: 0.15rem;
                }

                /* ── Faixa de indicadores ── */
                .mg-kpi-strip {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 1rem;
                    margin-bottom: 2rem;
                }
                .mg-kpi-card {
                    background: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 14px;
                    padding: 1.4rem 1.5rem;
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
                }
                .mg-kpi-icon-wrap {
                    width: 46px;
                    height: 46px;
                    border-radius: 12px;
                    background: #f3f4f6;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                }
                .mg-kpi-icon-wrap svg {
                    width: 22px;
                    height: 22px;
                    color: #374151;
                }
                .mg-kpi-label {
                    font-size: 0.75rem;
                    text-transform: uppercase;
                    letter-spacing: 0.07em;
                    color: #9ca3af;
                    font-weight: 700;
                }
                .mg-kpi-value {
                    font-size: 2rem;
                    font-weight: 700;
                    color: #111827;
                    line-height: 1.1;
                    margin-top: 0.15rem;
                }

                /* ── Cartão da tabela ── */
                .mg-table-card {
                    background: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 16px;
                    overflow: hidden;
                    box-shadow: 0 1px 6px rgba(0,0,0,0.05);
                }
                .mg-table-card-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 1.25rem 1.75rem;
                    border-bottom: 1px solid #f1f5f9;
                }
                .mg-table-card-title {
                    font-size: 0.95rem;
                    font-weight: 700;
                    color: #111827;
                }
                .mg-table-card-count {
                    font-size: 0.8rem;
                    color: #9ca3af;
                    background: #f3f4f6;
                    padding: 0.25rem 0.65rem;
                    border-radius: 999px;
                    font-weight: 600;
                }
                .mg-table-wrap { overflow-x: auto; }
                .mg-table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .mg-table th {
                    padding: 0.75rem 1.5rem;
                    font-size: 0.72rem;
                    text-transform: uppercase;
                    letter-spacing: 0.07em;
                    color: #9ca3af;
                    font-weight: 700;
                    background: #fafafa;
                    border-bottom: 1px solid #f1f5f9;
                    text-align: left;
                }
                .mg-table td {
                    padding: 1rem 1.5rem;
                    border-bottom: 1px solid #f8fafc;
                    vertical-align: middle;
                }
                .mg-table tbody tr:last-child td { border-bottom: none; }
                .mg-table tbody tr { transition: background 0.12s; }
                .mg-table tbody tr:hover { background: #fafafa; }
                .mg-table tbody tr.mg-row-current { background: #f0fdf4; }
                .mg-table tbody tr.mg-row-current:hover { background: #dcfce7; }

                /* ── Célula de utilizador ── */
                .mg-user-cell {
                    display: flex;
                    align-items: center;
                    gap: 0.9rem;
                }
                .mg-avatar {
                    width: 40px;
                    height: 40px;
                    border-radius: 9999px;
                    object-fit: cover;
                    border: 2px solid #e5e7eb;
                    flex-shrink: 0;
                }
                .mg-user-name {
                    font-size: 0.9rem;
                    font-weight: 600;
                    color: #111827;
                }
                .mg-user-role {
                    font-size: 0.78rem;
                    color: #9ca3af;
                    margin-top: 0.1rem;
                }
                .mg-cell-email {
                    font-size: 0.875rem;
                    color: #4b5563;
                }
                .mg-cell-meta {
                    font-size: 0.75rem;
                    color: #9ca3af;
                    margin-top: 0.2rem;
                }
                .mg-cell-date {
                    font-size: 0.85rem;
                    color: #6b7280;
                }

                /* ── Selos de estado ── */
                .mg-badge-active {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.3rem;
                    padding: 0.3rem 0.65rem;
                    border-radius: 999px;
                    background: #ecfdf5;
                    color: #059669;
                    border: 1px solid #a7f3d0;
                    font-size: 0.775rem;
                    font-weight: 600;
                }
                .mg-badge-dot {
                    width: 6px;
                    height: 6px;
                    border-radius: 999px;
                    background: #10b981;
                }
                .mg-badge-self {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.3rem;
                    padding: 0.3rem 0.65rem;
                    border-radius: 999px;
                    background: #eff6ff;
                    color: #1d4ed8;
                    border: 1px solid #bfdbfe;
                    font-size: 0.775rem;
                    font-weight: 600;
                }

                /* ── Botão de ação ── */
                .mg-delete-btn {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.35rem;
                    padding: 0.5rem 0.9rem;
                    border-radius: 8px;
                    background: white;
                    color: #dc2626;
                    border: 1px solid #fca5a5;
                    font-size: 0.82rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.15s;
                }
                .mg-delete-btn:hover {
                    background: #fef2f2;
                    border-color: #f87171;
                }
                .mg-self-note {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.35rem;
                    color: #9ca3af;
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                /* ── Estado vazio ── */
                .mg-empty {
                    text-align: center;
                    padding: 3.5rem 1rem;
                    color: #9ca3af;
                }
                .mg-empty svg { width: 40px; height: 40px; margin: 0 auto 0.75rem; }
                .mg-empty p { font-size: 0.9rem; margin: 0; }
            </style>

            {{-- Botão de voltar --}}
            <a href="{{ route('dashboard') }}" class="btn btn-outline text-xl px-4 py-2 min-h-0 h-auto leading-none mb-6 inline-flex" aria-label="Voltar ao Painel" title="Voltar">&larr;</a>

            {{-- Cabeçalho da página --}}
            <div class="mg-page-header">
                <div>
                    <h1 class="mg-page-title">Gestão de Administradores</h1>
                    <p class="mg-page-subtitle">Consulte, monitorize e gira as contas de administrador do sistema.</p>
                </div>
                <a href="{{ route('admins.create') }}" class="mg-create-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Criar Admin
                </a>
            </div>

            {{-- Alertas --}}
            @if (session('success'))
                <div id="mg-success-toast" class="mg-success-toast" role="status" aria-live="polite">
                    <div class="mg-success-toast-body">
                        <svg class="mg-success-toast-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span class="mg-success-toast-text">{{ session('success') }}</span>
                        <button type="button" id="mg-success-toast-close" class="mg-success-toast-close" aria-label="Fechar notificação">&times;</button>
                    </div>
                </div>
            @endif
            @if (session('error'))
                <div class="mg-alert mg-alert-error">
                    <svg class="mg-alert-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            {{-- Faixa de indicadores --}}
            <div class="mg-kpi-strip">
                <div class="mg-kpi-card">
                    <div class="mg-kpi-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div>
                        <div class="mg-kpi-label">Total de Admins</div>
                        <div class="mg-kpi-value">{{ $totalAdmins }}</div>
                    </div>
                </div>
            </div>

            {{-- Tabela de administradores --}}
            <div class="mg-table-card">
                <div class="mg-table-card-header">
                    <span class="mg-table-card-title">Todos os Administradores</span>
                    <span class="mg-table-card-count">{{ $totalAdmins }} {{ $totalAdmins === 1 ? 'registo' : 'registos' }}</span>
                </div>
                <div class="mg-table-wrap">
                    <table class="mg-table">
                        <thead>
                            <tr>
                                <th>Administrador</th>
                                <th>Email</th>
                                <th>Membro desde</th>
                                <th>Estado</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($admins as $admin)
                                <tr class="{{ Auth::id() === $admin->id ? 'mg-row-current' : '' }}">
                                    <td>
                                        <div class="mg-user-cell">
                                            @php
                                                $foto = $admin->profile_photo_path ? asset('storage/'.$admin->profile_photo_path) : $admin->profile_photo_url;
                                            @endphp
                                            <img src="{{ $foto }}" alt="{{ $admin->name }}" class="mg-avatar">
                                            <div>
                                                <div class="mg-user-name">{{ $admin->name }}</div>
                                                <div class="mg-user-role">Administrador</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="mg-cell-email">{{ $admin->email }}</span>
                                        <div class="mg-cell-meta">N.º leitor: {{ $admin->numero_leitor ?? '-' }}</div>
                                    </td>
                                    <td><span class="mg-cell-date">{{ $admin->created_at?->format('d/m/Y') ?? '-' }}</span></td>
                                    <td>
                                        @if (Auth::id() === $admin->id)
                                            <span class="mg-badge-self">
                                                <span class="mg-badge-dot" style="background:#3b82f6;"></span>
                                                Conta atual
                                            </span>
                                        @else
                                            <span class="mg-badge-active">
                                                <span class="mg-badge-dot"></span>
                                                Ativo
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (Auth::id() === $admin->id)
                                            <span class="mg-self-note">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                Não é possível apagar
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('admins.destroy', $admin) }}" class="js-admin-delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="mg-delete-btn">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                                    Apagar
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="mg-empty">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                            <p>Nenhum administrador encontrado.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Modal de confirmação usada antes de apagar um administrador. --}}
            <div id="delete-admin-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/45"></div>
                <div class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl">
                    <h3 class="text-lg font-bold text-gray-900">Confirmar apagamento</h3>
                    <p class="mt-2 text-sm text-gray-600">Tem a certeza que pretende apagar este administrador? Esta ação não pode ser revertida.</p>
                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" id="delete-admin-cancel" class="btn btn-outline">Cancelar</button>
                        <button type="button" id="delete-admin-confirm" class="mg-delete-btn">Apagar</button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Controla toast de sucesso e fluxo de confirmação para remoção de admins.
        document.addEventListener('DOMContentLoaded', function () {
            var successToast = document.getElementById('mg-success-toast');
            var successToastClose = document.getElementById('mg-success-toast-close');
            var modal = document.getElementById('delete-admin-modal');
            var confirmBtn = document.getElementById('delete-admin-confirm');
            var cancelBtn = document.getElementById('delete-admin-cancel');
            var pendingForm = null;

            // Fecha o toast com animação e remove do DOM após a transição.
            function hideSuccessToast() {
                if (!successToast) {
                    return;
                }

                successToast.classList.add('is-hidden');
                setTimeout(function () {
                    if (successToast) {
                        successToast.remove();
                    }
                }, 260);
            }

            // Auto-fecho do toast e botão manual de encerramento.
            if (successToast) {
                setTimeout(hideSuccessToast, 4200);

                if (successToastClose) {
                    successToastClose.addEventListener('click', hideSuccessToast);
                }
            }

            // Se elementos da modal não existirem, interrompe apenas a parte de confirmação.
            if (!modal || !confirmBtn || !cancelBtn) {
                return;
            }

            // Abre modal e guarda o form que será submetido se o utilizador confirmar.
            function openModal(form) {
                pendingForm = form;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            // Fecha modal e limpa referência do formulário pendente.
            function closeModal() {
                pendingForm = null;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            // Interceta submit dos botões "Apagar" para exigir confirmação prévia.
            document.querySelectorAll('.js-admin-delete-form').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    openModal(form);
                });
            });

            // Confirma remoção submetendo o formulário selecionado.
            confirmBtn.addEventListener('click', function () {
                if (pendingForm) {
                    pendingForm.submit();
                }
            });

            cancelBtn.addEventListener('click', closeModal);

            // Fecha modal ao clicar fora do cartão.
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            // Permite fechar modal com tecla Escape.
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        });
    </script>
</x-app-layout>



