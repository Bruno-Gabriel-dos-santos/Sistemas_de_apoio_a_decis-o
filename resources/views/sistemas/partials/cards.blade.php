<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($sistemas as $sistema)
        <div class="bg-white rounded-xl shadow-lg border p-6 flex flex-col justify-between hover:shadow-2xl transition cursor-pointer relative" onclick="window.location.href='/sistemas/{{ $sistema->nome }}'">
            <!-- Ícone de lixeira -->
            <button type="button" onclick="event.stopPropagation(); abrirModalSenha({{ $sistema->id }})" class="absolute top-2 right-2 text-gray-400 hover:text-red-600 focus:outline-none" title="Excluir Sistema">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                        <i class="fa fa-cogs text-indigo-500 text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">{{ $sistema->titulo }}</h2>
                        <p class="text-xs text-gray-500">{{ $sistema->nome }}</p>
                    </div>
                </div>
                <p class="text-gray-600 text-sm mb-2">{{ Str::limit($sistema->descricao, 80) }}</p>
            </div>
            <div class="flex items-center justify-between mt-4 w-full">
                <span class="text-xs text-gray-400 flex-1 text-left">ID: {{ $sistema->id }}</span>
                @if($sistema->categoria)
                    <span class="font-bold text-indigo-700 text-xs flex-1 text-center">{{ $sistema->categoria }}</span>
                @else
                    <span class="flex-1"></span>
                @endif
                <span class="text-xs text-gray-400 flex-1 text-right">{{ $sistema->data_inicio ? $sistema->data_inicio->format('d/m/Y') : '' }}</span>
            </div>
            @if($sistema->db_name)
                <div class="mt-2 text-xs text-gray-500">
                    DB: <code>{{ $sistema->db_name }}</code> &mdash; Usuário: <code>{{ $sistema->db_username }}</code>
                </div>
            @endif
            <form id="form-excluir-{{ $sistema->id }}" action="{{ route('sistemas.destroyCompleto', $sistema->id) }}" method="POST" class="mt-2">
                @csrf
                @method('DELETE')
            </form>
        </div>
    @empty
        <div class="col-span-full text-center py-8 text-gray-500">Nenhum sistema cadastrado ainda.</div>
    @endforelse
</div>
<div class="mt-10 flex justify-center">
    {{ $sistemas->links() }}
</div>

<!-- Modal de senha para exclusão -->
<div id="modal-senha-excluir" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-xl p-8 w-full max-w-xs relative flex flex-col items-center">
        <button onclick="fecharModalSenha()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
        <h2 class="text-xl font-bold mb-4 text-center">Confirme a exclusão</h2>
        <p class="mb-2 text-gray-700 text-center">Digite a senha para excluir o sistema:</p>
        <input type="password" id="senha-excluir" class="border rounded px-3 py-2 w-full mb-2 text-center" placeholder="Senha">
        <input type="hidden" id="id-sistema-excluir">
        <div id="erro-senha" class="text-red-600 text-sm mb-2 hidden">Senha incorreta!</div>
        <div class="flex gap-2 w-full">
            <button onclick="fecharModalSenha()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded w-1/2">Cancelar</button>
            <button onclick="confirmarExclusaoSistema()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded w-1/2 font-bold">Excluir</button>
        </div>
    </div>
</div>

<script>
function abrirModalSenha(id) {
    document.getElementById('modal-senha-excluir').classList.remove('hidden');
    document.getElementById('id-sistema-excluir').value = id;
    document.getElementById('senha-excluir').value = '';
    document.getElementById('erro-senha').classList.add('hidden');
    setTimeout(() => document.getElementById('senha-excluir').focus(), 100);
}
function fecharModalSenha() {
    document.getElementById('modal-senha-excluir').classList.add('hidden');
}
function confirmarExclusaoSistema() {
    const senha = document.getElementById('senha-excluir').value;
    if(senha !== '123') {
        document.getElementById('erro-senha').classList.remove('hidden');
        return;
    }
    const id = document.getElementById('id-sistema-excluir').value;
    document.getElementById('form-excluir-' + id).submit();
}
</script> 