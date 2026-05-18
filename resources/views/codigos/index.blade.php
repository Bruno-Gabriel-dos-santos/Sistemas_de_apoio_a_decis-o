@extends('layouts.app')

@section('styles')
<style>
    /* Estilos para os cards e containers */
    .grid { display: grid; }
    .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    @media (min-width: 768px) { .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .lg\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .gap-6 { gap: 1.5rem; }
    
    /* Estilos para botões e inputs */
    .bg-blue-600 { background-color: #2563eb; }
    .hover\:bg-blue-700:hover { background-color: #1d4ed8; }
    .bg-gray-200 { background-color: #e5e7eb; }
    .hover\:bg-gray-300:hover { background-color: #d1d5db; }
    .text-gray-700 { color: #374151; }
    .text-gray-800 { color: #1f2937; }
    
    /* Estilos para o modal */
    .fixed { position: fixed; }
    .inset-0 { top: 0; right: 0; bottom: 0; left: 0; }
    .z-50 { z-index: 50; }
    .bg-opacity-50 { --tw-bg-opacity: 0.5; }
    
    /* Outros estilos utilitários */
    .flex { display: flex; }
    .flex-1 { flex: 1 1 0%; }
    .items-center { align-items: center; }
    .justify-between { justify-content: space-between; }
    .space-y-4 > * + * { margin-top: 1rem; }
    .space-x-3 > * + * { margin-left: 0.75rem; }
    .mb-6 { margin-bottom: 1.5rem; }
    .mt-6 { margin-top: 1.5rem; }
    .hidden { display: none; }
    
    /* Estilos para inputs e forms */
    .rounded-md { border-radius: 0.375rem; }
    .border-gray-300 { border-color: #d1d5db; }
    .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
    .focus\:border-blue-500:focus { border-color: #3b82f6; }
    .focus\:ring-blue-500:focus { --tw-ring-color: #3b82f6; }
    
    /* Estilos para texto */
    .text-2xl { font-size: 1.5rem; }
    .font-bold { font-weight: 700; }
    .text-white { color: #ffffff; }
</style>
@endsection

@section('content')
<!-- Sistema de Alertas Customizado -->
<div id="customAlert" class="fixed inset-0 flex items-center justify-center z-50 hidden">
    <div class="fixed inset-0 bg-black opacity-50"></div>
    <div class="bg-white rounded-lg p-6 max-w-sm mx-auto relative z-10">
        <div id="alertContent" class="text-center">
            <div id="alertIcon" class="mx-auto mb-4"></div>
            <h3 id="alertTitle" class="text-lg font-semibold mb-2"></h3>
            <p id="alertMessage" class="text-gray-600"></p>
        </div>
        <div class="mt-6 text-center">
            <button onclick="closeCustomAlert()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                OK
            </button>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Cabeçalho com título e botão de adicionar -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Projetos de Código</h1>
        <button onclick="openAddProjectModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Adicionar Projeto
        </button>
    </div>

    <!-- Barra de filtros -->
    <div class="flex flex-wrap gap-4 mb-6">
        <!-- Seletor de linguagem -->
        <select id="language-selector" class="bg-white border border-gray-300 rounded-md py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Todas as linguagens</option>
            <option value="PHP">PHP</option>
            <option value="JavaScript">JavaScript</option>
            <option value="Python">Python</option>
            <option value="Java">Java</option>
            <option value="C#">C#</option>
            <option value="Ruby">Ruby</option>
            <option value="Go">Go</option>
            <option value="Rust">Rust</option>
            <option value="TypeScript">TypeScript</option>
            <option value="Swift">Swift</option>
        </select>

        <!-- Barra de pesquisa -->
        <div class="flex-1">
            <input type="text" id="search-input" placeholder="Pesquisar projetos..." 
                   class="w-full bg-white border border-gray-300 rounded-md py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    <!-- Container dos cards -->
    <div id="card-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Cards serão carregados aqui via AJAX -->
    </div>

    <!-- Controles de paginação -->
    <div class="flex justify-between items-center mt-6">
        <button id="prev-page" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded disabled:opacity-50 disabled:cursor-not-allowed">
            Anterior
        </button>
        <span id="current-page" class="text-gray-600">Página 1 de 1</span>
        <button id="next-page" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded disabled:opacity-50 disabled:cursor-not-allowed">
            Próxima
        </button>
    </div>
</div>

<!-- Modal de adicionar projeto -->
<div id="addProjectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
            <div class="flex justify-between items-center p-6 border-b">
                <h2 class="text-xl font-semibold">Adicionar Novo Projeto</h2>
                <button onclick="closeAddProjectModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="addProjectForm" class="p-6">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="nome_projeto" class="block text-sm font-medium text-gray-700">Nome do Projeto</label>
                        <input type="text" name="nome_projeto" id="nome_projeto" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="tipo_linguagem" class="block text-sm font-medium text-gray-700">Linguagem</label>
                        <select name="tipo_linguagem" id="tipo_linguagem" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Selecione uma linguagem</option>
                            <option value="PHP">PHP</option>
                            <option value="JavaScript">JavaScript</option>
                            <option value="Python">Python</option>
                            <option value="Java">Java</option>
                            <option value="C#">C#</option>
                            <option value="Ruby">Ruby</option>
                            <option value="Go">Go</option>
                            <option value="Rust">Rust</option>
                            <option value="TypeScript">TypeScript</option>
                            <option value="Swift">Swift</option>
                        </select>
                    </div>

                    <div>
                        <label for="categoria" class="block text-sm font-medium text-gray-700">Categoria</label>
                        <input type="text" name="categoria" id="categoria" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="descricao" class="block text-sm font-medium text-gray-700">Descrição</label>
                        <textarea name="descricao" id="descricao" rows="3" required
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>

                    <div>
                        <label for="link_github" class="block text-sm font-medium text-gray-700">Link GitHub</label>
                        <input type="url" name="link_github" id="link_github"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="link_gitlab" class="block text-sm font-medium text-gray-700">Link GitLab</label>
                        <input type="url" name="link_gitlab" id="link_gitlab"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeAddProjectModal()"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Salvar Projeto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div id="deleteConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden" style="z-index: 50;">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Confirmar Exclusão</h3>
                <button type="button" onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="space-y-4">
                <p class="text-gray-600">Digite a senha para confirmar a exclusão do projeto.</p>
                <div>
                    <input type="password" 
                           id="deletePassword" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           placeholder="Digite a senha">
                    <p id="deleteError" class="mt-1 text-sm text-red-600 hidden"></p>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                        Cancelar
                    </button>
                    <button type="button" onclick="confirmDelete()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200">
                        Excluir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Funções para o modal de alerta customizado
function showCustomAlert(title, message, type = 'success') {
    const alert = document.getElementById('customAlert');
    const alertTitle = document.getElementById('alertTitle');
    const alertMessage = document.getElementById('alertMessage');
    const alertIcon = document.getElementById('alertIcon');

    alertTitle.textContent = title;
    alertMessage.textContent = message;

    if (type === 'success') {
        alertIcon.innerHTML = `
            <svg class="h-12 w-12 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        `;
    } else {
        alertIcon.innerHTML = `
            <svg class="h-12 w-12 text-red-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        `;
    }

    alert.classList.remove('hidden');
}

function closeCustomAlert() {
    document.getElementById('customAlert').classList.add('hidden');
}

// Funções para o modal de adicionar projeto
function openAddProjectModal() {
    document.getElementById('addProjectModal').classList.remove('hidden');
}

function closeAddProjectModal() {
    document.getElementById('addProjectModal').classList.add('hidden');
    document.getElementById('addProjectForm').reset();
}

// Funções para o modal de exclusão
let currentProjectId = null;
function openDeleteModal(id) {
    currentProjectId = id;
    document.getElementById('deleteConfirmModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').classList.add('hidden');
    document.getElementById('deletePassword').value = '';
    document.getElementById('deleteError').classList.add('hidden');
}

async function confirmDelete() {
    const password = document.getElementById('deletePassword').value;
    const errorElem = document.getElementById('deleteError');
    errorElem.classList.add('hidden');
    errorElem.textContent = '';
    if (!password) {
        errorElem.textContent = 'Digite a senha.';
        errorElem.classList.remove('hidden');
        return;
    }
    try {
        const response = await fetch(`/codigos/${currentProjectId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ password })
        });
        const data = await response.json();
        if (response.ok && data.success) {
            showCustomAlert('Sucesso!', 'Projeto excluído com sucesso!', 'success');
            closeDeleteModal();
            loadCards();
        } else {
            errorElem.textContent = data.error || 'Senha incorreta ou erro ao excluir.';
            errorElem.classList.remove('hidden');
        }
    } catch (error) {
        errorElem.textContent = 'Erro ao processar a exclusão.';
        errorElem.classList.remove('hidden');
    }
}

// Manipulação do formulário
document.getElementById('addProjectForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const response = await fetch('/codigos', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            showCustomAlert('Sucesso!', data.message, 'success');
            closeAddProjectModal();
            loadCards();
        } else {
            showCustomAlert('Erro!', data.message || 'Erro ao criar projeto', 'error');
        }
    } catch (error) {
        showCustomAlert('Erro!', 'Erro ao processar a requisição', 'error');
    }
});

// Carregamento inicial
document.addEventListener('DOMContentLoaded', function() {
    loadCards();
});

// Eventos dos filtros
document.getElementById('language-selector').addEventListener('change', function() {
    loadCards();
});

document.getElementById('search-input').addEventListener('input', debounce(function() {
    loadCards();
}, 300));

// Função de debounce para otimizar a busca
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Função para carregar os cards
async function loadCards(page = 1) {
    const language = document.getElementById('language-selector').value;
    const search = document.getElementById('search-input').value;
    
    try {
        const response = await fetch(`/codigos?page=${page}&language=${language}&search=${search}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('card-container').innerHTML = data.html;
            updatePagination(data.current_page, data.last_page);
        }
    } catch (error) {
        showCustomAlert('Erro!', 'Erro ao carregar os projetos', 'error');
    }
}

let currentPageGlobal = 1;
let lastPageGlobal = 1;

// Função para atualizar a paginação
function updatePagination(currentPage, lastPage) {
    currentPageGlobal = currentPage;
    lastPageGlobal = lastPage;
    document.getElementById('current-page').textContent = `Página ${currentPage} de ${lastPage}`;
    document.getElementById('prev-page').disabled = currentPage === 1;
    document.getElementById('next-page').disabled = currentPage === lastPage;
}

// Eventos de paginação
document.getElementById('prev-page').addEventListener('click', function() {
    console.log('Página atual (anterior):', currentPageGlobal);
    if (currentPageGlobal > 1) {
        loadCards(currentPageGlobal - 1);
    }
});

document.getElementById('next-page').addEventListener('click', function() {
    console.log('Página atual (próxima):', currentPageGlobal, 'Última página:', lastPageGlobal);
    if (currentPageGlobal < lastPageGlobal) {
        loadCards(currentPageGlobal + 1);
    }
});
</script>
@endsection 