@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="mb-4">
                    <h2 class="text-2xl font-semibold text-gray-800">Editar Registro - {{ $table }}</h2>
                    <p class="text-gray-600 mt-1">ID: {{ $id }}</p>
                </div>

                @if(session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                <form action="{{ route('databases.record.update', ['table' => $table, 'id' => $id]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="space-y-4">
                        @foreach($columns as $column)
                            @if($column !== 'id')
                                <div>
                                    <label for="{{ $column }}" class="block text-sm font-medium text-gray-700">{{ $column }}</label>
                                    <input type="text" 
                                           name="{{ $column }}" 
                                           id="{{ $column }}" 
                                           value="{{ $record->$column }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div class="mt-6 flex items-center justify-end space-x-3">
                        <a href="{{ route('databases.table.details', $table) }}" 
                           class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200">
                            Cancelar
                        </a>
                        <button type="submit" 
                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection 