@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Nova Página do Sistema</h1>
    <form id="form-pagina" action="{{ route('paginas_sistemas.store', $sistema->id) }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block text-gray-700">Título</label>
            <input type="text" name="titulo" class="border rounded w-full px-3 py-2" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Descrição</label>
            <textarea name="descricao" class="border rounded w-full px-3 py-2"></textarea>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Tipo</label>
            <input type="text" name="tipo" class="border rounded w-full px-3 py-2" value="post" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Conteúdo (HTML, CSS, JS)</label>
            <textarea id="conteudo" name="conteudo" class="border rounded w-full px-3 py-2 h-48 font-mono" required></textarea>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Salvar</button>
            <a href="{{ route('sistemas.show', $sistema->id) }}" class="ml-2 bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded border">Voltar</a>
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
    // Atualiza o preview ao carregar a página (caso haja conteúdo)
    window.addEventListener('DOMContentLoaded', function() {
        iframe.srcdoc = conteudo.value;
    });
</script>
@endsection  