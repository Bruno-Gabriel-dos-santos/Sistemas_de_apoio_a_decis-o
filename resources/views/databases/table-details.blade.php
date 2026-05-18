@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

        <!-- Cabeçalho e Navegação -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-800">{{ $table }}</h2>
                        <p class="text-sm text-gray-600 mt-1">Total de registros: {{ number_format($totalRows) }}</p>
                    </div>
                    <a href="{{ route('databases.index') }}" class="text-indigo-600 hover:text-indigo-800 flex items-center">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Voltar para Databases
                    </a>
                </div>
            </div>
        </div>

        <!-- Estrutura da Tabela -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 bg-white border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    </svg>
                    Estrutura da Tabela
                </h3>
                <div class="overflow-x-auto bg-gray-50 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nulo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chave</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Padrão</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Extra</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($columns as $column)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $column->Field }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                            {{ $column->Type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($column->Null === 'YES')
                                            <span class="text-green-600">Sim</span>
                                        @else
                                            <span class="text-red-600">Não</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($column->Key)
                                            <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                                {{ $column->Key }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $column->Default ?? 'NULL' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($column->Extra)
                                            <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">
                                                {{ $column->Extra }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Formulário de Pesquisa -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 bg-white border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Pesquisar Registros
                </h3>
                <form action="{{ route('databases.table.search') }}" method="POST" class="flex items-end space-x-4">
                    @csrf
                    <input type="hidden" name="table_name" value="{{ $table }}">
                    
                    <div class="flex-1">
                        <label for="column" class="block text-sm font-medium text-gray-700 mb-1">Coluna</label>
                        <select name="column" id="column" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($columns as $col)
                                <option value="{{ $col->Field }}" {{ isset($selectedColumn) && $selectedColumn == $col->Field ? 'selected' : '' }}>
                                    {{ $col->Field }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex-1">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Termo de Pesquisa</label>
                        <input type="text" 
                               name="search" 
                               id="search" 
                               value="{{ $searchTerm ?? '' }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="Digite o termo de pesquisa...">
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Pesquisar
                        </button>

                        @if(isset($searchTerm))
                            <a href="{{ route('databases.table.details', $table) }}" 
                               class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Limpar
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Dados da Tabela -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Registros
                    <span class="ml-2 text-sm text-gray-600">(Mostrando {{ $perPage }} por página)</span>
                </h3>

                <div class="overflow-x-auto bg-gray-50 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                @foreach($columns as $column)
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ $column->Field }}
                                    </th>
                                @endforeach
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($sample as $row)
                                <tr class="hover:bg-gray-50">
                                    @foreach($columns as $column)
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @php
                                                $fieldName = $column->Field;
                                                $value = property_exists($row, $fieldName) ? $row->$fieldName : 'NULL';
                                                echo is_null($value) ? 'NULL' : $value;
                                            @endphp
                                        </td>
                                    @endforeach
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <a href="{{ route('databases.record.edit', ['table' => $table, 'id' => $row->id]) }}" 
                                           class="text-indigo-600 hover:text-indigo-900 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Editar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Controles de Paginação -->
                <div class="mt-4 flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <label class="text-sm text-gray-600">Registros por página:</label>
                        <select id="perPage" onchange="changePerPage(this.value)" 
                                class="border border-gray-300 rounded-md px-2 py-1 text-sm">
                            @foreach([5, 10, 25] as $option)
                                <option value="{{ $option }}" {{ $perPage == $option ? 'selected' : '' }}>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center space-x-2">
                        @if($page > 1)
                            <a href="{{ route('databases.table.details', ['table' => $table, 'page' => $page - 1, 'per_page' => $perPage]) }}"
                               class="px-3 py-1 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Anterior
                            </a>
                        @endif
                        
                        <span class="text-sm text-gray-600">
                            Página {{ $page }} de {{ $totalPages }}
                        </span>

                        @if($page < $totalPages)
                            <a href="{{ route('databases.table.details', ['table' => $table, 'page' => $page + 1, 'per_page' => $perPage]) }}"
                               class="px-3 py-1 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 flex items-center">
                                Próxima
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function changePerPage(value) {
        window.location.href = `{{ route('databases.table.details', ['table' => $table]) }}?per_page=${value}&page=1`;
    }
</script>
@endsection 