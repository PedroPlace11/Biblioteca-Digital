<x-app-layout>
    {{-- Formulário de submissão de review para um livro específico. --}}
    <div class="p-6 max-w-2xl mx-auto">
        <h2 class="text-3xl font-bold mb-6">Deixar Review para: {{ $livro->nome }}</h2>
        <form method="POST" action="{{ route('reviews.store', $livro->id) }}" class="bg-white rounded-xl shadow border border-gray-100 p-6">
            @csrf
            <div class="mb-4">
                {{-- Campo principal com o texto do review. --}}
                <label for="conteudo" class="block text-sm font-semibold mb-1">Review</label>
                <textarea name="conteudo" id="conteudo" class="form-control w-full rounded border border-gray-300 focus:ring-2 focus:ring-blue-600 focus:outline-none shadow-sm text-base font-medium min-h-[120px]" rows="5" required>{{ old('conteudo') }}</textarea>
                @error('conteudo')
                    {{-- Feedback de validação para o conteúdo submetido. --}}
                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-4">
                {{-- Avaliação em estrelas associada ao review. --}}
                <label for="rating" class="block text-sm font-semibold mb-1">Avaliação</label>
                <div class="rating">
                    <input type="radio" name="rating" value="1" class="mask mask-star-2 bg-orange-400" aria-label="1 estrela" />
                    <input type="radio" name="rating" value="2" class="mask mask-star-2 bg-orange-400" aria-label="2 estrelas" />
                    <input type="radio" name="rating" value="3" class="mask mask-star-2 bg-orange-400" aria-label="3 estrelas" />
                    <input type="radio" name="rating" value="4" class="mask mask-star-2 bg-orange-400" aria-label="4 estrelas" />
                    <input type="radio" name="rating" value="5" class="mask mask-star-2 bg-orange-400" aria-label="5 estrelas" />
                </div>
                @error('rating')
                    {{-- Feedback de validação para a nota atribuída. --}}
                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>
            {{-- Identifica a requisição associada quando a review surge após devolução. --}}
            <input type="hidden" name="requisicao_id" value="{{ request('requisicao_id') }}">
            <div class="flex justify-end gap-2">
                <button type="submit" class="btn bg-black text-white border-black hover:bg-gray-900 hover:text-white px-6 py-2 rounded font-bold">Submeter</button>
            </div>
        </form>
    </div>
</x-app-layout>
