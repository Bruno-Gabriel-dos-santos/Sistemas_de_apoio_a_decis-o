@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Mensagens de Sucesso/Erro -->
        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Seção de Bancos de Dados -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-semibold text-gray-800">Bancos de Dados</h2>
                    <button onclick="toggleModal('createDatabaseModal')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                        Novo Banco de Dados
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($databases as $db)
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">{{ current((array)$db) }}</h3>
                                    <p class="text-sm text-gray-500">
                                        @if(current((array)$db) === $currentDb)
                                            <span class="text-indigo-600 font-medium">Selecionado</span>
                                        @else
                                            <span class="text-gray-500">Não selecionado</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    @if(current((array)$db) !== $currentDb)
                                        <form action="{{ route('databases.select') }}" method="POST" class="ml-2">
                                            @csrf
                                            <input type="hidden" name="database_name" value="{{ current((array)$db) }}">
                                            <button type="submit" class="px-3 py-1 text-sm bg-indigo-100 text-indigo-700 rounded-md hover:bg-indigo-200">
                                                Selecionar
                                            </button>
                                        </form>
                                        <form action="{{ route('databases.drop') }}" method="POST" class="ml-2">
                                            @csrf
                                            <input type="hidden" name="database_name" value="{{ current((array)$db) }}">
                                            <button type="button" onclick="showDeleteDatabaseModal('{{ current((array)$db) }}')" 
                                                    class="text-red-600 hover:text-red-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Seção de Tabelas -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-semibold text-gray-800">Tabelas do Banco Atual</h2>
                    <button onclick="toggleModal('createTableModal')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                        Nova Tabela
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($tables as $table)
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 relative">
                            <div class="flex justify-between">
                                <div class="flex-1 min-w-0 pr-4">
                                    <h3 class="text-lg font-medium text-gray-900 truncate mb-2">{{ $table['name'] }}</h3>
                                    <div class="space-y-1">
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-600 font-medium">Registros:</span>
                                            <span class="text-gray-900">{{ number_format($table['rows']) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-600 font-medium">Colunas:</span>
                                            <span class="text-gray-900">{{ $table['columns'] }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-600 font-medium">Tamanho:</span>
                                            <span class="text-gray-900">{{ $table['size'] }} MB</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col space-y-2">
                                    <a href="{{ route('databases.table.details', $table['name']) }}" 
                                       class="text-indigo-600 hover:text-indigo-800 p-1 rounded-full hover:bg-indigo-50">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('databases.table.drop') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="table_name" value="{{ $table['name'] }}">
                                        <button type="button" onclick="showDeleteTableModal('{{ $table['name'] }}')"
                                                class="text-red-600 hover:text-red-800 p-1 rounded-full hover:bg-red-50">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Banco de Dados -->
<div id="createDatabaseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900">Criar Novo Banco de Dados</h3>
            <form action="{{ route('databases.create') }}" method="POST" class="mt-4">
                @csrf
                <div class="mt-2">
                    <input type="text" name="database_name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Nome do banco de dados">
                </div>
                <div class="flex justify-end mt-4">
                    <button type="button" onclick="toggleModal('createDatabaseModal')"
                            class="mr-2 px-4 py-2 text-gray-500 hover:text-gray-700 font-medium">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Criar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Criar Tabela -->
<div id="createTableModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900">Criar Nova Tabela</h3>
            <form action="{{ route('databases.table.create') }}" method="POST" class="mt-4">
                @csrf
                <div class="mt-2">
                    <input type="text" name="table_name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Nome da tabela">
                </div>
                
                <div class="mt-4">
                    <h4 class="text-md font-medium text-gray-700 mb-2">Colunas</h4>
                    <div id="columns" class="space-y-3">
                        <div class="grid grid-cols-4 gap-2">
                            <input type="text" name="columns[0][name]" placeholder="Nome da coluna" required
                                   class="col-span-1 px-3 py-2 border border-gray-300 rounded-md">
                            <select name="columns[0][type]" required
                                    class="col-span-1 px-3 py-2 border border-gray-300 rounded-md">
                                <option value="INT">INT</option>
                                <option value="VARCHAR">VARCHAR</option>
                                <option value="TEXT">TEXT</option>
                                <option value="DATE">DATE</option>
                                <option value="DATETIME">DATETIME</option>
                                <option value="BOOLEAN">BOOLEAN</option>
                            </select>
                            <input type="number" name="columns[0][length]" placeholder="Tamanho"
                                   class="col-span-1 px-3 py-2 border border-gray-300 rounded-md">
                            <div class="col-span-1 flex items-center">
                                <input type="checkbox" name="columns[0][nullable]" class="mr-2">
                                <label class="text-sm text-gray-600">Permite NULL</label>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addColumn()"
                            class="mt-2 px-3 py-1 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        + Adicionar Coluna
                    </button>
                </div>

                <div class="flex justify-end mt-4">
                    <button type="button" onclick="toggleModal('createTableModal')"
                            class="mr-2 px-4 py-2 text-gray-500 hover:text-gray-700 font-medium">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Criar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmação para Excluir Database -->
<div id="deleteDatabaseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Confirmar Exclusão</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Você tem certeza que deseja excluir o banco de dados <span id="databaseNameToDelete" class="font-medium text-gray-900"></span>?
                </p>
                <p class="text-sm text-red-500 mt-2">
                    Esta ação não pode ser desfeita!
                </p>
            </div>
            <div class="flex justify-center mt-4 space-x-4">
                <button onclick="hideDeleteDatabaseModal()" class="px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Cancelar
                </button>
                <form id="deleteDatabaseForm" action="{{ route('databases.drop') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" id="databaseNameInput" name="database_name" value="">
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação para Excluir Tabela -->
<div id="deleteTableModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Confirmar Exclusão</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Você tem certeza que deseja excluir a tabela <span id="tableNameToDelete" class="font-medium text-gray-900"></span>?
                </p>
                <p class="text-sm text-red-500 mt-2">
                    Esta ação não pode ser desfeita e todos os dados serão perdidos!
                </p>
            </div>
            <div class="flex justify-center mt-4 space-x-4">
                <button onclick="hideDeleteTableModal()" class="px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Cancelar
                </button>
                <form id="deleteTableForm" action="{{ route('databases.table.drop') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" id="tableNameInput" name="table_name" value="">
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.toggle('hidden');
    }

    let columnCount = 1;
    function addColumn() {
        const columnsDiv = document.getElementById('columns');
        const newColumn = document.createElement('div');
        newColumn.className = 'grid grid-cols-4 gap-2';
        newColumn.innerHTML = `
            <input type="text" name="columns[${columnCount}][name]" placeholder="Nome da coluna" required
                   class="col-span-1 px-3 py-2 border border-gray-300 rounded-md">
            <select name="columns[${columnCount}][type]" required
                    class="col-span-1 px-3 py-2 border border-gray-300 rounded-md">
                <option value="INT">INT</option>
                <option value="VARCHAR">VARCHAR</option>
                <option value="TEXT">TEXT</option>
                <option value="DATE">DATE</option>
                <option value="DATETIME">DATETIME</option>
                <option value="BOOLEAN">BOOLEAN</option>
            </select>
            <input type="number" name="columns[${columnCount}][length]" placeholder="Tamanho"
                   class="col-span-1 px-3 py-2 border border-gray-300 rounded-md">
            <div class="col-span-1 flex items-center">
                <input type="checkbox" name="columns[${columnCount}][nullable]" class="mr-2">
                <label class="text-sm text-gray-600">Permite NULL</label>
            </div>
        `;
        columnsDiv.appendChild(newColumn);
        columnCount++;
    }

    function showDeleteDatabaseModal(databaseName) {
        document.getElementById('databaseNameToDelete').textContent = databaseName;
        document.getElementById('databaseNameInput').value = databaseName;
        document.getElementById('deleteDatabaseModal').classList.remove('hidden');
    }

    function hideDeleteDatabaseModal() {
        document.getElementById('deleteDatabaseModal').classList.add('hidden');
    }

    function showDeleteTableModal(tableName) {
        document.getElementById('tableNameToDelete').textContent = tableName;
        document.getElementById('tableNameInput').value = tableName;
        document.getElementById('deleteTableModal').classList.remove('hidden');
    }

    function hideDeleteTableModal() {
        document.getElementById('deleteTableModal').classList.add('hidden');
    }

    // Fechar modais ao clicar fora deles
    window.onclick = function(event) {
        const deleteDatabaseModal = document.getElementById('deleteDatabaseModal');
        const deleteTableModal = document.getElementById('deleteTableModal');
        if (event.target === deleteDatabaseModal) {
            hideDeleteDatabaseModal();
        }
        if (event.target === deleteTableModal) {
            hideDeleteTableModal();
        }
    }
</script>
@endsection 