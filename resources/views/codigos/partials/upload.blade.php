<!-- Upload de Arquivos e Pastas -->
<div class="upload-container bg-white rounded-lg shadow-sm p-6">
    <!-- Área de Upload -->
    <div id="dropzone" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center mb-6 hover:border-blue-500 transition-colors duration-200">
        <input type="file" id="fileInput" multiple webkitdirectory directory class="hidden">
        <div class="flex flex-col items-center">
            <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <p class="text-lg font-medium text-gray-900 mb-2">Arraste e solte arquivos ou pastas aqui</p>
            <p class="text-sm text-gray-500 mb-4">Ou clique para selecionar</p>
            <button id="selectFiles" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition-colors duration-200">
                Selecionar Arquivos
            </button>
        </div>
    </div>

    <!-- Progresso do Upload -->
    <div id="uploadProgress" class="hidden mb-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">Progresso do Upload</span>
            <span id="progressText" class="text-sm text-gray-500">0%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div id="progressBar" class="bg-blue-500 h-2 rounded-full transition-all duration-200" style="width: 0%"></div>
        </div>
    </div>

    <!-- Lista de Arquivos -->
    <div id="fileList" class="space-y-4">
        <!-- Os arquivos serão adicionados aqui dinamicamente -->
    </div>
</div>

<style>
    .upload-container {
        max-width: 800px;
        margin: 0 auto;
    }

    #dropzone {
        min-height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    #dropzone.drag-over {
        background-color: rgba(59, 130, 246, 0.1);
        border-color: #3b82f6;
    }

    .file-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        background: #f9fafb;
    }

    .file-item:hover {
        background: #f3f4f6;
    }

    .file-icon {
        width: 2rem;
        height: 2rem;
        margin-right: 1rem;
        color: #6b7280;
    }

    .file-info {
        flex: 1;
    }

    .file-name {
        font-weight: 500;
        color: #111827;
        margin-bottom: 0.25rem;
    }

    .file-size {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .file-actions {
        display: flex;
        gap: 0.5rem;
    }

    .action-button {
        padding: 0.5rem;
        border-radius: 0.375rem;
        transition: all 0.2s;
    }

    .action-button:hover {
        background-color: #f3f4f6;
    }

    .action-button.edit {
        color: #3b82f6;
    }

    .action-button.view {
        color: #10b981;
    }

    .action-button.delete {
        color: #ef4444;
    }

    .upload-error {
        color: #ef4444;
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    const selectButton = document.getElementById('selectFiles');
    const fileList = document.getElementById('fileList');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const hashIdentidade = '{{ $codigo->hash_identidade }}';

    // Eventos do Dropzone
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('drag-over');
    });

    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('drag-over');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('drag-over');
        handleFiles(e.dataTransfer.files);
    });

    selectButton.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        if (files.length === 0) return;

        uploadProgress.classList.remove('hidden');
        let totalSize = 0;
        let uploadedSize = 0;

        // Converter FileList para Array e calcular tamanho total
        const filesArray = Array.from(files);
        filesArray.forEach(file => {
            totalSize += file.size;
        });

        // Criar FormData e adicionar arquivos
        const formData = new FormData();
        filesArray.forEach(file => {
            // Preservar a estrutura de pastas usando o webkitRelativePath
            const relativePath = file.webkitRelativePath || file.name;
            formData.append('files[]', file, relativePath);
        });
        formData.append('hash_identidade', hashIdentidade);

        // Enviar arquivos
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/codigos/upload', true);
        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);

        // Atualizar progresso
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
                progressText.textContent = Math.round(percentComplete) + '%';
            }
        });

        // Manipular resposta
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showAlert('Sucesso', 'Arquivos enviados com sucesso!', 'success');
                    atualizarListaArquivos();
                } else {
                    showAlert('Erro', response.message || 'Erro ao enviar arquivos', 'error');
                }
            } else {
                showAlert('Erro', 'Erro ao enviar arquivos', 'error');
            }
            uploadProgress.classList.add('hidden');
        };

        xhr.onerror = function() {
            showAlert('Erro', 'Erro de conexão ao enviar arquivos', 'error');
            uploadProgress.classList.add('hidden');
        };

        xhr.send(formData);
    }

    function atualizarListaArquivos() {
        fetch(`/codigos/${hashIdentidade}/listar?path=/`)
            .then(response => response.json())
            .then(data => {
                renderizarListaArquivos(data);
            })
            .catch(error => {
                showAlert('Erro', 'Erro ao atualizar lista de arquivos', 'error');
            });
    }

    function renderizarListaArquivos(items) {
        fileList.innerHTML = '';
        items.forEach(item => {
            const itemElement = document.createElement('div');
            itemElement.className = 'file-item';
            
            itemElement.innerHTML = `
                <div class="file-icon">
                    ${item.type === 'directory' ? 
                        '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>' :
                        '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
                    }
                </div>
                <div class="file-info">
                    <div class="file-name">${item.name}</div>
                    <div class="file-size">${item.type === 'directory' ? 'Pasta' : 'Arquivo'}</div>
                </div>
                <div class="file-actions">
                    ${item.type === 'file' ? `
                        <button class="action-button edit" onclick="editarArquivo('${item.path}')" title="Editar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button class="action-button view" onclick="visualizarArquivo('${item.path}')" title="Visualizar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    ` : ''}
                    <button class="action-button delete" onclick="excluirItem('${item.path}')" title="Excluir">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            `;
            
            fileList.appendChild(itemElement);
        });
    }

    function showAlert(title, message, type = 'info') {
        // Implementar função de alerta personalizado
        const alertElement = document.createElement('div');
        alertElement.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg ${
            type === 'success' ? 'bg-green-500' :
            type === 'error' ? 'bg-red-500' :
            'bg-blue-500'
        } text-white`;
        
        alertElement.innerHTML = `
            <div class="font-bold">${title}</div>
            <div>${message}</div>
        `;
        
        document.body.appendChild(alertElement);
        
        setTimeout(() => {
            alertElement.remove();
        }, 3000);
    }

    function editarArquivo(path) {
        fetch(`/arquivos/${hashIdentidade}/visualizar/${path}`)
            .then(response => response.text())
            .then(content => {
                // Aqui você pode implementar um modal ou redirecionamento para edição
                const novoConteudo = prompt('Editar arquivo:', content);
                if (novoConteudo !== null) {
                    return fetch(`/arquivos/${hashIdentidade}/editar/${path}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ content: novoConteudo })
                    });
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Sucesso', 'Arquivo atualizado com sucesso!', 'success');
                    atualizarListaArquivos();
                } else {
                    showAlert('Erro', data.message || 'Erro ao atualizar arquivo', 'error');
                }
            })
            .catch(error => {
                showAlert('Erro', 'Erro ao editar arquivo', 'error');
            });
    }

    function visualizarArquivo(path) {
        window.open(`/arquivos/${hashIdentidade}/visualizar/${path}`, '_blank');
    }

    function excluirItem(path) {
        if (confirm('Tem certeza que deseja excluir este item?')) {
            fetch(`/arquivos/${hashIdentidade}/excluir/${path}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Sucesso', 'Item excluído com sucesso!', 'success');
                    atualizarListaArquivos();
                } else {
                    showAlert('Erro', data.message || 'Erro ao excluir item', 'error');
                }
            })
            .catch(error => {
                showAlert('Erro', 'Erro ao excluir item', 'error');
            });
        }
    }

    // Inicialização
    atualizarListaArquivos();
});
</script> 