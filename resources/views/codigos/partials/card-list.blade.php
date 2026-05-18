@if($codigos->isEmpty())
    <div class="col-span-full text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum projeto encontrado</h3>
        <p class="mt-1 text-sm text-gray-500">Comece adicionando um novo projeto.</p>
    </div>
@else
    @foreach($codigos as $codigo)
    <div onclick="window.location.href='{{ route('codigos.show', $codigo) }}'" class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200 cursor-pointer relative">
        <button onclick="event.stopPropagation(); openDeleteModal({{ $codigo->id }})" title="Excluir projeto" class="absolute top-2 right-2 text-gray-400 hover:text-red-600 focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <div class="p-5">
            <div class="flex items-center mb-4">
                <svg class="h-8 w-8 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">{{ $codigo->nome_projeto }}</h3>
                    <p class="text-sm text-gray-500">{{ $codigo->tipo_linguagem }}</p>
                </div>
            </div>
            <p class="text-gray-600 text-sm mb-4">{{ Str::limit($codigo->descricao, 100) }}</p>
            <div class="flex justify-between items-center">
                <span class="text-xs text-gray-500">Atualizado: {{ $codigo->updated_at->diffForHumans() }}</span>
                <div class="flex space-x-2">
                    @if($codigo->link_github)
                        <a href="{{ $codigo->link_github }}" target="_blank" class="text-gray-400 hover:text-gray-600" onclick="event.stopPropagation()">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
                            </svg>
                        </a>
                    @endif
                    @if($codigo->link_gitlab)
                        <a href="{{ $codigo->link_gitlab }}" target="_blank" class="text-gray-400 hover:text-gray-600" onclick="event.stopPropagation()">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M22.65 14.39L12 22.13 1.35 14.39a.84.84 0 01-.3-.94l1.22-3.78 2.44-7.51A.42.42 0 014.82 2a.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.49h8.1l2.44-7.51A.42.42 0 0118.6 2a.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.51L23 13.45a.84.84 0 01-.35.94z"/>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
            <div class="flex justify-between items-end mt-2">
                <span></span>
                <span class="text-xs text-gray-700 font-semibold">Categoria: {{ $codigo->categoria }}</span>
            </div>
        </div>
    </div>
    @endforeach
@endif 