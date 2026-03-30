<x-app-layout>
    <div class="p-6 max-w-3xl mx-auto">
        <div class="mb-6 text-left">
            <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline text-xl px-4 py-2 min-h-0 h-auto leading-none" aria-label="Voltar aos Reviews" title="Voltar">&larr;</a>
        </div>

        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-6 md:p-8">
                <div class="mb-6">
                    <h1 class="text-2xl md:text-3xl font-bold text-base-content">Detalhe do Review</h1>
                    <p class="text-sm text-base-content/70 mt-2">Veja e modere o review submetido pelo cidadão.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <div class="font-semibold text-base-content/80">Livro</div>
                        <div class="text-base-content">{{ $review->livro->nome }}</div>
                    </div>
                    <div>
                        <div class="font-semibold text-base-content/80">Cidadão</div>
                        <div class="text-base-content">{{ $review->user->name }} ({{ $review->user->email }})</div>
                    </div>
                </div>

                <div class="mb-6">
                    <div class="font-semibold text-base-content/80 mb-1">Conteúdo do Review</div>
                    <div class="bg-base-200 rounded p-4 text-base-content">{{ $review->conteudo }}</div>
                </div>

                <div class="mb-6">
                    <div class="font-semibold text-base-content/80 mb-1">Estado atual</div>
                    @if($review->estado === 'ativo')
                        <div class="badge badge-success capitalize">Aprovado</div>
                    @elseif($review->estado === 'recusado')
                        <div class="badge badge-error capitalize">Recusado</div>
                    @else
                        <div class="badge bg-yellow-400 text-yellow-900 capitalize">Aguardando</div>
                    @endif
                </div>

                @if($review->estado === 'recusado')
                    <div class="mb-6">
                        <div class="font-semibold text-base-content/80 mb-1">Justificação da Recusa</div>
                        <div class="bg-base-200 rounded p-4 text-error">{{ $review->justificacao }}</div>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.reviews.update', $review->id) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Alterar Estado</span>
                        </label>
                        <select name="estado" id="estado" class="select select-bordered w-full" required onchange="document.getElementById('justificacao-box').style.display = this.value === 'recusado' ? 'block' : 'none';">
                            <option value="ativo" @if($review->estado==='ativo') selected @endif>Aprovar (Ativo)</option>
                            <option value="recusado" @if($review->estado==='recusado') selected @endif>Recusar</option>
                        </select>
                    </div>

                    <div class="form-control" id="justificacao-box" style="display: {{ $review->estado==='recusado' ? 'block' : 'none' }};">
                        <label class="label">
                            <span class="label-text font-semibold">Justificação (obrigatória se recusar)</span>
                        </label>
                        <textarea name="justificacao" id="justificacao" class="textarea textarea-bordered w-full" rows="3">{{ old('justificacao', $review->justificacao) }}</textarea>
                    </div>

                    <div class="pt-2 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                        <a href="{{ route('admin.reviews.index') }}" class="btn btn-ghost">Cancelar</a>
                        <button class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white" type="submit">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
