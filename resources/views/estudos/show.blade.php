@extends('layouts.app')

@section('content')
<div class="mx-auto py-8" style="width: 80%;">
    <div class="flex justify-end mb-4 gap-2">
        <a href="{{ route('estudos.edit', $estudo->id) }}" class="bg-yellow-400 hover:bg-yellow-500 text-white font-bold py-2 px-4 rounded">Editar</a>
        <form id="form-deletar-{{ $estudo->id }}" action="{{ route('estudos.destroy', $estudo->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="button" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded" onclick="abrirModalSenha({{ $estudo->id }})">Deletar</button>
        </form>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <img src="{{ asset('storage/' . $estudo->capa) }}" alt="Capa" class="w-full h-64 object-cover rounded mb-4">
        <h1 class="text-3xl font-bold mb-2">{{ $estudo->titulo }}</h1>
        <div class="flex justify-between text-sm text-gray-500 mb-4">
            <span>Autor: {{ $estudo->autor }}</span>
            <span>{{ \Carbon\Carbon::parse($estudo->data)->format('d/m/Y') }}</span>
            @if($estudo->tag)
                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">{{ $estudo->tag }}</span>
            @endif
        </div>
        <div class="prose max-w-none">{!! $estudo->conteudo !!}</div>
    </div>
</div>
<!-- Modal de senha -->
<div id="modal-senha" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-sm text-center">
        <h2 class="text-xl font-bold mb-4 text-gray-800">Confirme a exclusão</h2>
        <p class="mb-4 text-gray-600">Digite a senha para deletar:</p>
        <input type="password" id="senha-confirmacao" class="border rounded px-3 py-2 w-full mb-4 text-center" placeholder="Senha">
        <div id="erro-senha" class="text-red-600 mb-2 hidden">Senha incorreta!</div>
        <div class="flex justify-end gap-2">
            <button type="button" onclick="fecharModalSenha()" class="px-4 py-2 bg-gray-300 rounded">Cancelar</button>
            <button type="button" onclick="confirmarSenha()" class="px-4 py-2 bg-red-600 text-white rounded font-bold">Deletar</button>
        </div>
    </div>
</div>
<script>
    let formDeletarId = null;
    function abrirModalSenha(id) {
        formDeletarId = id;
        document.getElementById('modal-senha').classList.remove('hidden');
        document.getElementById('senha-confirmacao').value = '';
        document.getElementById('erro-senha').classList.add('hidden');
    }
    function fecharModalSenha() {
        document.getElementById('modal-senha').classList.add('hidden');
        formDeletarId = null;
    }
    function confirmarSenha() {
        const senha = document.getElementById('senha-confirmacao').value;
        if (senha === '123') {
            document.getElementById('form-deletar-' + formDeletarId).submit();
        } else {
            document.getElementById('erro-senha').classList.remove('hidden');
        }
    }
</script>
@endsection 