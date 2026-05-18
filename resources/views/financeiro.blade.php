@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto mt-10 space-y-6">
    <div class="text-center space-y-2">
        <h1 class="text-3xl font-bold text-gray-900">Financeiro</h1>
        <p class="text-gray-600">Escolha uma área para visualizar indicadores, checklists e ações dedicadas.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($sections as $slug => $section)
            <a href="{{ route('financeiro.show', $slug) }}"
               class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-lg transition flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $section['title'] }}</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ \Illuminate\Support\Str::limit($section['description'], 90) }}</p>
                </div>
                <span class="text-indigo-600 group-hover:translate-x-1 transition">
                    <i class="fa fa-arrow-right"></i>
                </span>
            </a>
        @endforeach
    </div>
</div>
@endsection 