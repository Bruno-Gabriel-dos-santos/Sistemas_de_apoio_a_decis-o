@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Editar Página do Sistema</h1>
    <form action="{{ route('paginas_sistemas.update', [$sistema->id, $pagina->id]) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label class="block text-gray-700">Título</label>
            <input type="text" name="titulo" class="border rounded w-full px-3 py-2" value="{{ $pagina->titulo }}" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Descrição</label>
            <textarea name="descricao" class="border rounded w-full px-3 py-2">{{ $pagina->descricao }}</textarea>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Conteúdo</label>
            <textarea id="conteudo" name="conteudo" class="border rounded w-full px-3 py-2" required>{{ $pagina->conteudo }}</textarea>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded">Atualizar</button>
            <a href="{{ route('sistemas.show', $sistema->nome) }}" class="ml-2 bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded border">Voltar</a>
        </div>
    </form>
    <div class="mt-8">
        <h2 class="text-lg font-bold mb-2">Preview</h2>
        <iframe id="preview-frame" class="w-full border rounded min-h-[300px]" style="min-height:300px;"></iframe>
    </div>
</div>
<script>
    const conteudo = document.getElementById('conteudo');
    const iframe = document.getElementById('preview-frame');
    conteudo.addEventListener('input', function() {
        iframe.srcdoc = conteudo.value;
    });
    window.addEventListener('DOMContentLoaded', function() {
        iframe.srcdoc = conteudo.value;
    });
</script>
@endsection 