@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold mb-4">Editar Relatório</h1>
        <form method="POST" action="{{ route('relatorios-situacionais.update', $post->id) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="block mb-1 font-bold">Título</label>
                <input type="text" name="titulo" class="w-full border p-2 rounded" value="{{ old('titulo', $post->titulo) }}" required>
            </div>
            <div class="mb-3">
                <label class="block mb-1 font-bold">Conteúdo</label>
                <textarea name="conteudo" id="conteudo" class="w-full border p-2 rounded" rows="6" required oninput="atualizarPreview()">{{ old('conteudo', $post->conteudo) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="block mb-1 font-bold">Preview</label>
                <div id="preview" class="border p-2 rounded bg-gray-50 min-h-[100px]">
                    {!! old('conteudo', $post->conteudo) !!}
                </div>
            </div>
            <div class="flex justify-end">
                <a href="{{ route('relatorios-situacionais.show', $post->id) }}" class="px-4 py-2 bg-gray-300 rounded mr-2">Cancelar</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Salvar</button>
            </div>
        </form>
    </div>
</div>
<script>
function atualizarPreview() {
    const conteudo = document.getElementById('conteudo').value;
    document.getElementById('preview').innerHTML = conteudo;
}
</script>
@endsection 