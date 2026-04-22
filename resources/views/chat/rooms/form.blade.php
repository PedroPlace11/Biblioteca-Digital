@extends('layouts.app')

@section('content')
<div class="min-h-[calc(100vh-10rem)] bg-gradient-to-br from-slate-50 via-white to-blue-50/40 px-4 py-10 sm:px-6 lg:px-8">
    <div class="mx-auto w-full max-w-3xl">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_24px_60px_-28px_rgba(15,23,42,0.35)] backdrop-blur">
            <div class="border-b border-slate-200/80 bg-gradient-to-r from-slate-50 via-white to-blue-50/70 px-6 py-6 sm:px-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Gestão de salas</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
                            @if(isset($room))
                                Editar Sala
                            @else
                                Criar Nova Sala
                            @endif
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                            Configure os detalhes da sala para manter as conversas organizadas e profissionais.
                        </p>
                    </div>

                    @if(isset($room) && (auth()->user()->isAdmin() || auth()->user()->id === $room->creator_id))
                        <button type="button"
                            onclick="if(confirm('Tem certeza?')) { document.getElementById('delete-form').submit(); }"
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-red-600 shadow-sm ring-1 ring-slate-200 transition hover:bg-red-50 hover:ring-red-200 focus:outline-none focus:ring-4 focus:ring-red-100"
                            title="Eliminar sala"
                            aria-label="Eliminar sala">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 7h12m-9 0V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l.7 11.2a2 2 0 001.99 1.8h4.62a2 2 0 001.99-1.8L17 7M10 11v6m4-6v6" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            <div class="p-6 sm:p-8">
                <form action="{{ isset($room) ? route('chat.rooms.update', $room) : route('chat.rooms.store') }}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="space-y-7">
                    @csrf
                    @if(isset($room))
                        @method('PATCH')
                    @endif

                    <div class="grid gap-6">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5 shadow-sm">
                            <label for="name" class="mb-2 block text-sm font-semibold text-slate-800">
                            Nome da Sala *
                            </label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   value="{{ old('name', $room->name ?? '') }}"
                                   class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-400 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100 @error('name') border-red-500 focus:border-red-500 focus:ring-red-100 @enderror"
                                   placeholder="Ex.: Equipa de Catalogacao"
                                   required>
                            <p class="mt-2 text-xs text-slate-500">Use um nome curto e claro para facilitar a identificação da sala.</p>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <label for="description" class="mb-2 block text-sm font-semibold text-slate-800">
                            Descricao
                            </label>
                            <textarea id="description"
                                      name="description"
                                      rows="5"
                                      class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-400 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100 @error('description') border-red-500 focus:border-red-500 focus:ring-red-100 @enderror"
                                      placeholder="Descreva o objetivo desta sala...">{{ old('description', $room->description ?? '') }}</textarea>
                            <p class="mt-2 text-xs text-slate-500">Uma boa descrição ajuda os membros a perceberem o propósito da sala.</p>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5 shadow-sm">
                            <label for="avatar" class="mb-3 block text-sm font-semibold text-slate-800">
                            Avatar da Sala
                            </label>

                            @if(isset($room) && $room->avatar)
                                <div class="mb-4 flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3">
                                    <img src="{{ asset('storage/' . $room->avatar) }}"
                                         alt="{{ $room->name }}"
                                         class="h-16 w-16 rounded-xl border border-slate-200 object-cover shadow-sm">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">Avatar atual</p>
                                        <p class="text-xs leading-5 text-slate-500">Carregue uma nova imagem para substituir.</p>
                                    </div>
                                </div>
                            @endif

                            <input type="file"
                                   id="avatar"
                                   name="avatar"
                                   accept="image/*"
                                   class="w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm file:mr-4 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200 focus:outline-none focus:ring-4 focus:ring-blue-100 @error('avatar') border-red-500 focus:ring-red-100 @enderror">

                            <p class="mt-2 text-xs text-slate-500">JPG, PNG, GIF ou WebP (max 2MB)</p>

                            @error('avatar')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Botoes -->
                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row">
                        <a href="{{ route('chat.rooms.index') }}"
                           class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-300 bg-slate-100 px-4 py-3 text-center font-semibold text-slate-700 transition hover:bg-slate-200">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="inline-flex flex-1 items-center justify-center rounded-2xl bg-black px-4 py-3 font-semibold text-white shadow-sm transition hover:bg-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-200">
                            @if(isset($room))
                                Atualizar
                            @else
                                Criar Sala
                            @endif
                        </button>
                    </div>
                </form>

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
</div>
@endsection
