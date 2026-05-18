@extends('layouts.app')

@section('content')
<div class="mx-auto py-8" style="width: 80%;">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold mb-4">Editar Pesquisa</h1>
        <form action="{{ route('pesquisas.update', $pesquisa->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block font-semibold mb-1">Título</label>
                <input type="text" name="titulo" value="{{ old('titulo', $pesquisa->titulo) }}" class="w-full border rounded p-2" required>
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Conteúdo</label>
                <textarea name="conteudo" id="conteudo" rows="8" class="w-full border rounded p-2" required>{{ old('conteudo', $pesquisa->conteudo) }}</textarea>
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Preview em tempo real:</label>
                <div id="preview" class="prose border rounded p-4 bg-gray-50">{!! old('conteudo', $pesquisa->conteudo) !!}</div>
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('pesquisas.show', $pesquisa->id) }}" class="px-4 py-2 bg-gray-300 rounded">Cancelar</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Salvar</button>
            </div>
        </form>
    </div>
</div>
<script>
    const textarea = document.getElementById('conteudo');
    const preview = document.getElementById('preview');
    textarea.addEventListener('input', function() {
        preview.innerHTML = textarea.value;
    });
</script>
@endsection 