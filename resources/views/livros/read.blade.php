@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <!-- Cabeçalho -->
        <div class="bg-gray-800 px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-white">{{ $livro->titulo }}</h1>
                <a href="{{ route('livros.index') }}" class="text-gray-300 hover:text-white transition-colors duration-200">
                    <i class="fas fa-times text-xl"></i>
                </a>
            </div>
            <div class="mt-2 text-gray-300 text-sm">
                <span class="mr-4"><i class="fas fa-user mr-1"></i> {{ $livro->autor ?: 'Autor Desconhecido' }}</span>
                <span class="mr-4"><i class="fas fa-folder mr-1"></i> {{ $livro->categoria }}</span>
                <span><i class="fas fa-calendar mr-1"></i> {{ \Carbon\Carbon::parse($livro->data_publicacao)->format('d/m/Y') }}</span>
            </div>
        </div>

        <!-- Visualizador de PDF -->
        <div class="w-full h-[calc(100vh-12rem)]">
            <embed src="{{ Storage::url($livro->arquivo_path) }}" 
                   type="application/pdf" 
                   width="100%" 
                   height="100%"
                   class="border-0">
        </div>

        <!-- Rodapé -->
        <div class="bg-gray-50 px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    @if($livro->descricao)
                        <p><i class="fas fa-info-circle mr-1"></i> {{ $livro->descricao }}</p>
                    @endif
                </div>
                <div class="space-x-4">
                    <a href="{{ route('livros.download', $livro->id) }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors duration-200">
                        <i class="fas fa-download mr-2"></i> Download
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 