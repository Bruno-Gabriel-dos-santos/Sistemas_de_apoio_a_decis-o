@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-12">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <h2 class="text-2xl font-bold mb-8">Progresso de Leituras</h2>

            <!-- Seção 1: Leituras Atuais -->
            <div class="mb-12 bg-gray-50 p-6 rounded-lg">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-800">Leituras em Andamento</h3>
                    <button id="addLeituraBtn" 
                            class="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Adicionar Nova Leitura
                    </button>
                </div>

                <!-- Grid de Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="leiturasGrid">
                    @foreach($leiturasAtuais as $leitura)
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                            <div class="flex justify-between items-start mb-4">
                                <h4 class="text-lg font-medium text-gray-900">{{ $leitura->titulo_livro }}</h4>
                                <button onclick="deleteLeitura({{ $leitura->id }})" class="text-red-500 hover:text-red-700">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Página Atual</label>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <input type="number" 
                                               value="{{ $leitura->pagina_atual }}"
                                               min="0"
                                               class="pagina-atual block w-32 h-12 text-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                               data-leitura-id="{{ $leitura->id }}">
                                        @if($leitura->total_paginas)
                                            <span class="text-lg text-gray-500">de {{ $leitura->total_paginas }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Meta de Conclusão</label>
                                    <input type="date" 
                                           value="{{ $leitura->meta_conclusao?->format('Y-m-d') }}"
                                           class="meta-conclusao mt-1 block w-full h-12 text-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           data-leitura-id="{{ $leitura->id }}">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Notas</label>
                                    <textarea class="notas-leitura mt-1 block w-full text-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                              rows="4"
                                              data-leitura-id="{{ $leitura->id }}">{{ $leitura->notas_leitura }}</textarea>
                                </div>

                                <button onclick="updateLeitura({{ $leitura->id }})"
                                        class="w-full mt-4 inline-flex justify-center items-center px-3 py-2 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Atualizar
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Seção 2: Metas de Leitura -->
            <div class="mb-12 bg-gray-50 p-6 rounded-lg">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-800">Metas e Objetivos de Leitura</h3>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                    <textarea id="metasLeitura" 
                              class="w-full h-64 p-4 border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500"
                              placeholder="Digite aqui suas metas e objetivos de leitura...">{{ $metas?->conteudo }}</textarea>
                    <button onclick="updateRegistro('metas')"
                            class="mt-4 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Salvar Metas
                    </button>
                </div>
            </div>

            <!-- Seção 3: Histórico de Leituras -->
            <div class="bg-gray-50 p-6 rounded-lg">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-800">Histórico e Anotações</h3>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                    <textarea id="historicoLeitura" 
                              class="w-full h-64 p-4 border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500"
                              placeholder="Digite aqui seu histórico de leituras e anotações...">{{ $historico?->conteudo }}</textarea>
                    <button onclick="updateRegistro('historico')"
                            class="mt-4 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Salvar Histórico
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar Leitura -->
<div id="addLeituraModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-md w-full">
        <div class="flex justify-between items-start mb-4">
            <h3 class="text-lg font-medium text-gray-900">Adicionar Nova Leitura</h3>
            <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <form id="addLeituraForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Título do Livro</label>
                <input type="text" id="novoTitulo" required
                       class="mt-1 block w-full h-12 text-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Página Atual</label>
                    <input type="number" id="novaPaginaAtual" required min="0"
                           class="mt-1 block w-full h-12 text-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Total de Páginas</label>
                    <input type="number" id="novoTotalPaginas" min="0"
                           class="mt-1 block w-full h-12 text-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Meta de Conclusão</label>
                <input type="date" id="novaMetaConclusao"
                       class="mt-1 block w-full h-12 text-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Notas</label>
                <textarea id="novasNotas" rows="4"
                          class="mt-1 block w-full text-lg rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="toggleModal(false)"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Adicionar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Notificação -->
<div id="notification" class="fixed top-4 left-1/2 transform -translate-x-1/2 hidden">
    <div class="bg-white border-l-4 p-4 shadow-lg rounded-lg min-w-[300px]" role="alert">
        <div class="flex items-center">
            <div id="notificationIcon" class="flex-shrink-0">
                <!-- Ícone será inserido via JavaScript -->
            </div>
            <div class="ml-3">
                <p id="notificationMessage" class="text-sm font-medium"></p>
            </div>
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button onclick="hideNotification()" class="inline-flex rounded-md p-1.5 text-gray-500 hover:text-gray-600 focus:outline-none">
                        <span class="sr-only">Fechar</span>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação para Deletar -->
<div id="deleteModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
        <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mt-4">Confirmar Exclusão</h3>
            <p class="text-sm text-gray-500 mt-2">Tem certeza que deseja remover esta leitura? Esta ação não pode ser desfeita.</p>
        </div>
        <div class="mt-6 flex justify-center space-x-4">
            <button onclick="toggleDeleteModal(false)" 
                    class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Cancelar
            </button>
            <button id="confirmDeleteBtn"
                    class="px-4 py-2 bg-red-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                Sim, remover
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Funções do Modal
    window.toggleModal = function(show) {
        const modal = document.getElementById('addLeituraModal');
        if (show) {
            modal.classList.remove('hidden');
        } else {
            modal.classList.add('hidden');
        }
    }

    // Botão de adicionar leitura
    document.getElementById('addLeituraBtn').addEventListener('click', () => toggleModal(true));

    // Form de adicionar leitura
    document.getElementById('addLeituraForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = {
            titulo_livro: document.getElementById('novoTitulo').value,
            pagina_atual: document.getElementById('novaPaginaAtual').value,
            total_paginas: document.getElementById('novoTotalPaginas').value || null,
            meta_conclusao: document.getElementById('novaMetaConclusao').value || null,
            notas_leitura: document.getElementById('novasNotas').value
        };

        try {
            const response = await fetch('{{ route("livros.progresso.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();
            
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert('Erro ao adicionar leitura: ' + data.message);
            }
        } catch (error) {
            alert('Erro ao adicionar leitura');
            console.error('Erro:', error);
        }
    });

    // Função de notificação
    window.showNotification = function(message, type = 'success') {
        const notification = document.getElementById('notification');
        const notificationMessage = document.getElementById('notificationMessage');
        const notificationIcon = document.getElementById('notificationIcon');
        const notificationDiv = notification.querySelector('.bg-white');
        
        // Configurar ícone e cores baseado no tipo
        let iconSvg = '';
        if (type === 'success') {
            iconSvg = '<svg class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
            notificationDiv.classList.add('border-green-400');
            notificationDiv.classList.remove('border-red-400');
        } else {
            iconSvg = '<svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
            notificationDiv.classList.add('border-red-400');
            notificationDiv.classList.remove('border-green-400');
        }
        
        notificationIcon.innerHTML = iconSvg;
        notificationMessage.textContent = message;
        notification.classList.remove('hidden');
        
        // Auto-hide após 3 segundos
        setTimeout(hideNotification, 3000);
    };

    window.hideNotification = function() {
        const notification = document.getElementById('notification');
        notification.classList.add('hidden');
    };

    // Atualizar leitura
    window.updateLeitura = async function(id) {
        const card = document.querySelector(`[data-leitura-id="${id}"]`).closest('.bg-white');
        const formData = {
            pagina_atual: card.querySelector('.pagina-atual').value,
            meta_conclusao: card.querySelector('.meta-conclusao').value || null,
            notas_leitura: card.querySelector('.notas-leitura').value
        };

        try {
            const response = await fetch(`/livros/progresso/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();
            
            if (data.status === 'success') {
                showNotification('Leitura atualizada com sucesso!', 'success');
            } else {
                showNotification(data.message || 'Erro ao atualizar leitura', 'error');
            }
        } catch (error) {
            showNotification('Erro ao atualizar leitura', 'error');
            console.error('Erro:', error);
        }
    };

    // Funções do Modal de Deletar
    let leituraIdToDelete = null;

    window.toggleDeleteModal = function(show, leituraId = null) {
        const modal = document.getElementById('deleteModal');
        if (show) {
            leituraIdToDelete = leituraId;
            modal.classList.remove('hidden');
        } else {
            leituraIdToDelete = null;
            modal.classList.add('hidden');
        }
    }

    // Deletar leitura
    window.deleteLeitura = function(id) {
        toggleDeleteModal(true, id);
    };

    document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
        if (!leituraIdToDelete) return;

        try {
            const response = await fetch(`/livros/progresso/${leituraIdToDelete}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();
            
            if (data.status === 'success') {
                window.location.reload();
            } else {
                showNotification(data.message || 'Erro ao remover leitura', 'error');
            }
        } catch (error) {
            showNotification('Erro ao remover leitura', 'error');
            console.error('Erro:', error);
        } finally {
            toggleDeleteModal(false);
        }
    });

    // Atualizar registro (metas ou histórico)
    window.updateRegistro = async function(tipo) {
        const conteudo = document.getElementById(tipo === 'metas' ? 'metasLeitura' : 'historicoLeitura').value;

        try {
            const response = await fetch('{{ route("livros.progresso.updateRegistro") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ tipo, conteudo })
            });

            const data = await response.json();
            
            if (data.status === 'success') {
                showNotification('Registro atualizado com sucesso!', 'success');
            } else {
                showNotification(data.message || 'Erro ao atualizar registro', 'error');
            }
        } catch (error) {
            showNotification('Erro ao atualizar registro', 'error');
            console.error('Erro:', error);
        }
    };
});
</script>
@endsection 