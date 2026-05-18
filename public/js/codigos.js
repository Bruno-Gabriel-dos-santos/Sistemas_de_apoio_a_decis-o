// Variáveis globais
let typingTimer;
const doneTypingInterval = 500;
let currentPage = 1;
let selectedLanguage = '';
let searchQuery = '';

// Função para atualizar os cards
async function updateCards(page = 1) {
    const language = document.getElementById('languageFilter').value;
    const search = document.getElementById('searchInput').value;
    
    try {
        const response = await fetch(`/codigos?page=${page}&language=${language}&search=${search}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('cardList').innerHTML = data.html;
            document.getElementById('pagination').innerHTML = data.pagination;
            
            // Atualizar URL sem recarregar a página
            const url = new URL(window.location);
            if (language) url.searchParams.set('language', language);
            else url.searchParams.delete('language');
            if (search) url.searchParams.set('search', search);
            else url.searchParams.delete('search');
            url.searchParams.set('page', page);
            window.history.pushState({}, '', url);
        } else {
            Swal.fire('Erro', data.message || 'Ocorreu um erro ao carregar os projetos', 'error');
        }
    } catch (error) {
        console.error('Erro ao atualizar cards:', error);
        Swal.fire('Erro', 'Ocorreu um erro ao atualizar os projetos', 'error');
    }
}

// Função principal para carregar os cards
function loadCards(page = 1) {
    const language = document.getElementById('language-selector').value;
    const search = document.getElementById('search-input').value;
    
    $.ajax({
        url: '/codigos',
        method: 'GET',
        data: {
            page: page,
            language: language,
            search: search
        },
        success: function(response) {
            if (response.success) {
                $('#card-container').html(response.html);
                updatePagination(response.current_page, response.last_page);
                currentPage = response.current_page;
                
                // Atualizar URL sem recarregar a página
                const url = new URL(window.location);
                if (language) url.searchParams.set('language', language);
                else url.searchParams.delete('language');
                if (search) url.searchParams.set('search', search);
                else url.searchParams.delete('search');
                url.searchParams.set('page', page);
                window.history.pushState({}, '', url);
            } else {
                showError('Erro ao carregar os projetos');
            }
        },
        error: function(xhr) {
            showError('Erro ao carregar os projetos');
        }
    });
}

// Função para atualizar a paginação
function updatePagination(currentPage, lastPage) {
    const prevBtn = document.getElementById('prev-page');
    const nextBtn = document.getElementById('next-page');
    
    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === lastPage;
    
    document.getElementById('current-page').textContent = `Página ${currentPage} de ${lastPage}`;
}

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

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Event listeners para navegação
    document.getElementById('prev-page')?.addEventListener('click', function() {
        if (currentPage > 1) {
            loadCards(currentPage - 1);
        }
    });

    document.getElementById('next-page')?.addEventListener('click', function() {
        loadCards(currentPage + 1);
    });

    // Event listener para o seletor de linguagem
    document.getElementById('language-selector')?.addEventListener('change', function() {
        currentPage = 1;
        loadCards(1);
    });

    // Event listener para a barra de pesquisa com debounce
    document.getElementById('search-input')?.addEventListener('input', debounce(function() {
        currentPage = 1;
        loadCards(1);
    }, 500));

    // Carregar cards iniciais
    loadCards(1);

    // Função para abrir o modal de adicionar projeto
    window.openAddProjectModal = function() {
        document.getElementById('addProjectModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // Função para fechar o modal
    window.closeAddProjectModal = function() {
        document.getElementById('addProjectModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
        document.getElementById('addProjectForm').reset();
    }

    // Função para submeter o formulário de novo projeto
    window.submitProjectForm = function(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);

        $.ajax({
            url: '/codigos',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                closeAddProjectModal();
                showSuccess('Projeto adicionado com sucesso!');
                loadCards(currentPage);
            },
            error: function(xhr) {
                const errors = xhr.responseJSON.errors;
                let errorMessage = 'Por favor, corrija os seguintes erros:\n';
                for (let field in errors) {
                    errorMessage += `${errors[field].join('\n')}\n`;
                }
                showError(errorMessage);
            }
        });
    }

    // Event listener para o formulário
    document.getElementById('addProjectForm')?.addEventListener('submit', submitProjectForm);

    // Filtro de linguagem
    const languageFilter = document.getElementById('languageFilter');
    languageFilter?.addEventListener('change', () => updateCards());

    // Campo de pesquisa com debounce
    const searchInput = document.getElementById('searchInput');
    searchInput?.addEventListener('keyup', function() {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => updateCards(), doneTypingInterval);
    });

    // Formulário de adicionar projeto
    const addProjectForm = document.getElementById('addProjectForm');
    addProjectForm?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('/codigos', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    title: 'Sucesso!',
                    text: data.message || 'Projeto adicionado com sucesso!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
                closeAddProjectModal();
                updateCards();
            } else {
                Swal.fire({
                    title: 'Erro!',
                    text: data.message || 'Erro ao criar o projeto',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        } catch (error) {
            console.error('Erro ao criar projeto:', error);
            Swal.fire({
                title: 'Erro!',
                text: 'Ocorreu um erro ao criar o projeto',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });

    // Paginação
    document.addEventListener('click', function(e) {
        if (e.target.tagName === 'A' && e.target.closest('.pagination')) {
            e.preventDefault();
            const href = e.target.getAttribute('href');
            if (href) {
                const page = href.split('page=')[1];
                updateCards(page);
                window.scrollTo(0, 0);
            }
        }
    });
});

// Funções do Modal
function openModal() {
    const modal = document.getElementById('addProjectModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal() {
    const modal = document.getElementById('addProjectModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
        document.getElementById('addProjectForm')?.reset();
    }
}

// Funções de exclusão e edição
async function deleteProject(id) {
    const result = await Swal.fire({
        title: 'Tem certeza?',
        text: "Esta ação não pode ser desfeita!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch(`/codigos/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire('Excluído!', data.message, 'success');
                updateCards();
            } else {
                Swal.fire('Erro!', data.message || 'Erro ao excluir o projeto', 'error');
            }
        } catch (error) {
            console.error('Erro ao excluir projeto:', error);
            Swal.fire('Erro!', 'Ocorreu um erro ao excluir o projeto', 'error');
        }
    }
}

async function editProject(id) {
    try {
        const response = await fetch(`/codigos/${id}/edit`);
        const data = await response.json();

        if (data.success) {
            const { projeto } = data;
            
            document.getElementById('nome_projeto').value = projeto.nome_projeto;
            document.getElementById('tipo_linguagem').value = projeto.tipo_linguagem;
            document.getElementById('descricao').value = projeto.descricao;
            document.getElementById('link_github').value = projeto.link_github || '';
            document.getElementById('link_gitlab').value = projeto.link_gitlab || '';
            
            // Adicionar ID do projeto ao formulário
            const form = document.getElementById('addProjectForm');
            form.dataset.projectId = id;
            
            // Alterar o método do formulário para PUT
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'PUT';
            form.appendChild(methodInput);
            
            openModal();
        } else {
            Swal.fire('Erro!', data.message || 'Erro ao carregar dados do projeto', 'error');
        }
    } catch (error) {
        console.error('Erro ao carregar projeto:', error);
        Swal.fire('Erro!', 'Ocorreu um erro ao carregar o projeto', 'error');
    }
} 