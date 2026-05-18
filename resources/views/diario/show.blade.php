@extends('layouts.app')

@section('content')
@php
    $monthRoute = route('diario.index', ['ano' => $selectedDate->year, 'mes' => $selectedDate->month]);
    $humanDate = \Illuminate\Support\Str::ucfirst($selectedDate->locale('pt_BR')->translatedFormat('d \\d\\e F \\d\\e Y'));
@endphp
<div class="max-w-4xl mx-auto mt-6 space-y-6">
    <div class="flex items-center justify-between bg-white shadow rounded-lg px-6 py-4">
        <div>
            <div class="text-sm text-gray-500">Dia selecionado</div>
            <div class="text-2xl font-bold text-gray-800">{{ $humanDate }}</div>
        </div>
        <a href="{{ $monthRoute }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-2">
            <i class="fa fa-calendar-alt"></i> Voltar para o calendário
        </a>
    </div>

    <div class="bg-white shadow rounded-xl p-6 space-y-4">
        @if (session('success'))
            <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('diario.save', $selectedDate->format('Y-m-d')) }}" class="space-y-4">
            @csrf
            <label for="conteudo" class="block text-sm font-semibold text-gray-600">Anotações do dia</label>
            <textarea id="conteudo" name="conteudo" rows="14"
                      class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 text-gray-800 font-mono">{{ old('conteudo', $content) }}</textarea>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500">Os dados ficam salvos em armazenamento privado do servidor.</span>
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-indigo-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                    <i class="fa fa-save"></i> Salvar dia
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

