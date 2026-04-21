@extends('layouts.app')

@section('content')
<div class="min-h-[calc(100vh-10rem)] px-4 py-10 sm:px-6 lg:px-8">
    <div class="mx-auto w-full max-w-3xl">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_20px_50px_-25px_rgba(15,23,42,0.45)]">
            <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-blue-50/60 px-6 py-6 sm:px-8">
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
                    @if(isset($room))
                        Editar Sala
                    @else
                        Criar Nova Sala
                    @endif
                </h1>
                <p class="mt-2 text-sm text-slate-600">
                    Configure os detalhes da sala para manter as conversas organizadas e profissionais.
                </p>
            </div>

            <div class="p-6 sm:p-8">
                <form action="{{ isset($room) ? route('chat.rooms.update', $room) : route('chat.rooms.store') }}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="space-y-6">
                    @csrf
                    @if(isset($room))
                        @method('PATCH')
                    @endif

                    <!-- Nome -->
                    <div>
                        <label for="name" class="mb-2 block text-sm font-semibold text-slate-800">
                            Nome da Sala *
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name', $room->name ?? '') }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-900 placeholder:text-slate-400 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100 @error('name') border-red-500 focus:border-red-500 focus:ring-red-100 @enderror"
                               placeholder="Ex.: Equipa de Catalogacao"
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Descricao -->
                    <div>
                        <label for="description" class="mb-2 block text-sm font-semibold text-slate-800">
                            Descricao
                        </label>
                        <textarea id="description"
                                  name="description"
                                  rows="4"
                                  class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-900 placeholder:text-slate-400 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100 @error('description') border-red-500 focus:border-red-500 focus:ring-red-100 @enderror"
                                  placeholder="Descreva o objetivo desta sala...">{{ old('description', $room->description ?? '') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Avatar -->
                    <div>
                        <label for="avatar" class="mb-2 block text-sm font-semibold text-slate-800">
                            Avatar da Sala
                        </label>

                        @if(isset($room) && $room->avatar)
                            <div class="mb-4 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <img src="{{ asset('storage/' . $room->avatar) }}"
                                     alt="{{ $room->name }}"
                                     class="h-16 w-16 rounded-lg border border-slate-200 object-cover">
                                <div>
                                    <p class="text-sm font-medium text-slate-800">Avatar atual</p>
                                    <p class="text-xs text-slate-500">Carregue uma nova imagem para substituir.</p>
                                </div>
                            </div>
                        @endif

                        <input type="file"
                               id="avatar"
                               name="avatar"
                               accept="image/*"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200 focus:outline-none focus:ring-4 focus:ring-blue-100 @error('avatar') border-red-500 focus:ring-red-100 @enderror">

                        <p class="mt-2 text-xs text-slate-500">JPG, PNG, GIF ou WebP (max 2MB)</p>

                        @error('avatar')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Botoes -->
                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row">
                        <a href="{{ route('chat.rooms.index') }}"
                           class="inline-flex flex-1 items-center justify-center rounded-xl border border-slate-300 bg-slate-100 px-4 py-2.5 text-center font-semibold text-slate-700 transition hover:bg-slate-200">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="inline-flex flex-1 items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200">
                            @if(isset($room))
                                Atualizar Sala
                            @else
                                Criar Sala
                            @endif
                        </button>

                        @if(isset($room) && (auth()->user()->isAdmin() || auth()->user()->id === $room->creator_id))
                            <button type="button"
                                    onclick="if(confirm('Tem certeza?')) { document.getElementById('delete-form').submit(); }"
                                    class="inline-flex items-center justify-center rounded-xl bg-red-600 px-4 py-2.5 font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-200">
                                Eliminar
                            </button>
                        @endif
                    </div>
                </form>

                @if(isset($room))
                    <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-700">
                        As alteracoes nesta sala ficam visiveis imediatamente para os participantes.
                    </div>
                @endif
            @if(isset($room) && (auth()->user()->isAdmin() || auth()->user()->id === $room->creator_id))
                <form id="delete-form"
                      action="{{ route('chat.rooms.destroy', $room) }}"
                      method="POST"
                      class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
