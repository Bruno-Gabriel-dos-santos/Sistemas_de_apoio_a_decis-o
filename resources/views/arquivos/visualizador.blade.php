@extends('layouts.app')
@section('content')
<div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-2xl flex flex-col items-center">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Visualizando: {{ $arquivo->nome }}</h2>
        <div class="mb-6 text-gray-500 text-sm">Tipo: {{ $tipo }} | Tamanho: {{ $arquivo->tamanho_arquivo ? number_format($arquivo->tamanho_arquivo/1024, 1) . ' KB' : '-' }}</div>
        <div class="w-full flex flex-col items-center justify-center">
            @if(Str::startsWith($tipo, 'image/'))
                <img src="{{ route('arquivos.preview', $arquivo->id) }}" alt="Imagem" class="max-w-full max-h-[60vh] rounded shadow" />
            @elseif(Str::startsWith($tipo, 'video/'))
                <video src="{{ route('arquivos.preview', $arquivo->id) }}" controls class="max-w-full max-h-[60vh] rounded shadow"></video>
            @elseif(Str::startsWith($tipo, 'audio/'))
                <audio src="{{ route('arquivos.preview', $arquivo->id) }}" controls class="w-full"></audio>
            @elseif($tipo === 'application/pdf')
                <iframe src="{{ route('arquivos.preview', $arquivo->id) }}" class="w-full min-h-[60vh] rounded shadow" frameborder="0"></iframe>
            @elseif(Str::startsWith($tipo, 'text/'))
                <iframe src="{{ route('arquivos.preview', $arquivo->id) }}" class="w-full min-h-[60vh] rounded shadow bg-gray-100" frameborder="0"></iframe>
            @else
                <a href="{{ route('arquivos.preview', $arquivo->id) }}" target="_blank" class="text-blue-600 underline text-lg">Baixar/Visualizar arquivo</a>
            @endif
        </div>
        <a href="{{ url()->previous() }}" class="mt-8 px-4 py-2 rounded bg-gray-300 text-gray-800 hover:bg-gray-400">Voltar</a>
    </div>
</div>
@endsection 