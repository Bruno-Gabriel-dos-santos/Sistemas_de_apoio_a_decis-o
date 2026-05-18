@php /*
    Componente de gerenciador de arquivos reutilizável.
    Adaptado para uso em sistemas, utilizando o id da migration sistemas e o SistemasArquivosController.
    Espera receber a variável $sistema (model Sistema) como parâmetro.
*/ @endphp

<div class="min-h-[80vh] flex bg-white rounded shadow-lg overflow-hidden mt-4">
    <!-- Lateral Direita (Sidebar) -->
    <aside class="w-1/5 bg-white/80 border-r p-6 flex flex-col gap-6 shadow-lg rounded-xl mt-4 ml-2 mb-2">
        <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-2 shadow">
            <i class="fa fa-folder-open text-indigo-500 text-4xl"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold mb-2 text-left">{{ $sistema->nome }}</h2>
            <p class="text-gray-700 text-left"><span class="font-semibold">ID:</span> <span class="break-all">{{ $sistema->id }}</span></p>
            <p class="text-gray-700 text-left"><span class="font-semibold">Categoria:</span> {{ $sistema->categoria }}</p>
            <p class="text-gray-700 text-left"><span class="font-semibold">Descrição:</span> {{ $sistema->descricao }}</p>
            <p class="text-gray-700 text-center"><span class="font-semibold">Data de Criação:</span> {{ $sistema->created_at }}</p>
            <p class="text-gray-700 text-center"><span class="font-semibold">Data de Atualização:</span> {{ $sistema->updated_at }}</p>
            <p class="text-gray-700 mt-2"><span class="font-semibold">Caminho Atual:</span> <span id="current-path" class="break-all">/</span></p>
            <p class="text-gray-700 mt-1"><span class="font-semibold">Caminho Relativo:</span> <span id="relative-path" class="break-all">/</span></p>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Upload de arquivo(s) ou pasta</label>
                <input type="file" id="upload-file" multiple class="block w-full text-sm text-gray-500" style="display:none" />
                <input type="file" id="upload-folder" multiple webkitdirectory directory class="block w-full text-sm text-gray-500" style="display:none" />
                <div class="flex flex-col gap-2">
                    <button id="sistemaArquivos-btn-upload-file" type="button" class="w-full flex items-center justify-center gap-2 bg-indigo-500 text-white py-2 rounded-lg shadow hover:bg-indigo-600 hover:scale-105 transition-all font-semibold"><i class="fa fa-upload"></i>Selecionar Arquivo(s)</button>
                    <button id="sistemaArquivos-btn-upload-folder" type="button" class="w-full flex items-center justify-center gap-2 bg-emerald-500 text-white py-2 rounded-lg shadow hover:bg-emerald-600 hover:scale-105 transition-all font-semibold"><i class="fa fa-folder-open"></i>Selecionar Pasta</button>
                </div>
                <button id="sistemaArquivos-btn-upload" class="mt-2 w-full flex items-center justify-center gap-2 bg-blue-600 text-white py-2 rounded-lg shadow hover:bg-blue-700 hover:scale-105 transition-all font-semibold"><i class="fa fa-cloud-upload"></i>Enviar</button>
                <div id="upload-selected" class="mt-2 text-xs text-gray-600"></div>
                <div id="upload-progress-list" class="mt-2">
                    <div id="total-progress-container" style="display:none">
                        <div class="text-xs mb-1">Progresso total</div>
                        <div class="w-full bg-gray-200 rounded h-3 overflow-hidden">
                            <div class="bg-blue-500 h-3 rounded" style="width:0%" id="total-progress-bar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <h3 class="text-md font-semibold mb-1">Ações</h3>
            <button id="sistemaArquivos-btn-novo-arquivo" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition" type="button">Novo arquivo</button>
            <button id="sistemaArquivos-btn-nova-pasta" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700 transition mt-2" type="button">Nova pasta</button>
        </div>
    </aside>
    <!-- Conteúdo Principal -->
    <main class="w-4/5 p-8 flex flex-col gap-6">
        <div class="border-b pb-4 mb-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2 m-0">
                    <i class="fa fa-folder-open text-indigo-500"></i>
                    {{ $sistema->nome }}
                </h1>
            </div>
            <p class="text-gray-500 mt-1">Gerenciador de arquivos do sistema</p>
        </div>
        <div class="flex flex-col md:flex-row gap-8">
            <!-- Lista de arquivos -->
            <div class="w-full md:w-1/3">
                <h2 class="text-lg font-semibold mb-2 flex items-center gap-2"><i class="fa fa-list"></i>Arquivos</h2>
                <ul id="file-tree" class="bg-gray-50 border rounded-xl p-2 min-h-[200px] max-h-[400px] overflow-y-auto shadow hover:shadow-lg transition-all" style="max-height:400px; overflow-y:auto;">
                    <li class="text-gray-400 italic">Carregando arquivos...</li>
                </ul>
            </div>
            <!-- Conteúdo do arquivo -->
            <div class="w-full md:w-2/3">
                <h2 class="text-lg font-semibold mb-2 flex items-center gap-2"><i class="fa fa-file-alt"></i>Conteúdo do Arquivo</h2>
                <pre id="file-content" class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-700 text-green-200 rounded-xl p-4 min-h-[200px] max-h-[400px] overflow-x-auto overflow-y-auto whitespace-pre-wrap font-mono shadow-lg border-2 border-indigo-500/30 focus-within:border-indigo-500 transition-all" style="max-height:400px; overflow:auto; font-family: 'Fira Mono', 'JetBrains Mono', 'Menlo', 'Monaco', 'Consolas', monospace;">Selecione um arquivo para visualizar o conteúdo.</pre>
            </div>
        </div>
    </main>
</div>

<div id="custom-alert" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:9999; background:rgba(0,0,0,0.3); align-items:center; justify-content:center;">
    <div id="custom-alert-content" class="bg-white rounded-xl shadow-2xl px-8 py-6 text-center text-lg font-semibold text-gray-800" style="min-width:300px; max-width:90vw;"></div>
</div>

<div id="delete-modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:10000; background:rgba(0,0,0,0.3); align-items:center; justify-content:center;">
    <div class="bg-white rounded shadow-lg px-8 py-6 text-center" style="min-width:320px; max-width:90vw;">
        <div class="text-lg font-semibold mb-2">Apagar <span id="delete-item-name"></span>?</div>
        <input type="password" id="delete-password" class="border rounded px-3 py-2 w-full mb-4" placeholder="Digite a senha" autocomplete="off" />
        <div class="flex gap-4 justify-center">
            <button id="btn-confirm-delete" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">Apagar</button>
            <button id="btn-cancel-delete" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400 transition">Cancelar</button>
        </div>
    </div>
</div>

<!-- Modal para criar arquivo/pasta -->
<div id="modal-criar" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:10001; background:rgba(0,0,0,0.3); align-items:center; justify-content:center;">
    <div class="bg-white rounded shadow-lg px-8 py-6 text-center" style="min-width:320px; max-width:90vw;">
        <div id="modal-criar-titulo" class="text-lg font-semibold mb-2"></div>
        <input type="text" id="modal-criar-input" class="border rounded px-3 py-2 w-full mb-4" placeholder="Digite o nome" autocomplete="off" />
        <div class="flex gap-4 justify-center">
            <button id="btn-confirm-criar" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Criar</button>
            <button id="btn-cancel-criar" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400 transition">Cancelar</button>
        </div>
    </div>
</div>

<div id="modal-excluir-projeto" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:10002; background:rgba(0,0,0,0.3); align-items:center; justify-content:center; backdrop-filter: blur(2px);">
    <div class="bg-white rounded-2xl shadow-2xl px-8 py-8 text-center" style="min-width:320px; max-width:90vw;">
        <div class="text-2xl font-bold mb-4 flex items-center justify-center gap-2 text-rose-700"><i class="fa fa-trash"></i>Excluir sistema?</div>
        <input type="password" id="senha-excluir-projeto" class="border-2 border-gray-300 rounded-lg px-3 py-2 w-full mb-4 focus:border-rose-500 focus:ring-2 focus:ring-rose-200 transition" placeholder="Digite a senha" autocomplete="off" />
        <div class="flex gap-4 justify-center">
            <button id="btn-confirm-excluir-projeto" class="flex items-center gap-2 bg-rose-600 text-white px-4 py-2 rounded-lg shadow hover:bg-rose-700 hover:scale-105 transition-all font-semibold"><i class="fa fa-trash"></i>Excluir</button>
            <button id="btn-cancel-excluir-projeto" class="flex items-center gap-2 bg-gray-300 text-gray-800 px-4 py-2 rounded-lg shadow hover:bg-gray-400 hover:scale-105 transition-all font-semibold"><i class="fa fa-times"></i>Cancelar</button>
        </div>
    </div>
</div>

<script>
let rootPath = '/';
let currentPath = rootPath;
let pathStack = [rootPath];
let filesToUpload = [];
let deletePath = null;
let criarTipo = null;
const sistemaId = {{ $sistema->id }};

function setCurrentPath(newPath) {
    currentPath = newPath;
    document.getElementById('current-path').innerText = `sistemas/${sistemaId}${currentPath}`;
    // Atualiza caminho relativo
    let relative = currentPath || '/';
    if (!relative.startsWith('/')) relative = '/' + relative;
    document.getElementById('relative-path').innerText = relative;
}

function renderTree(tree, parent, parentPath = rootPath) {
    tree.forEach(function(item) {
        var li = document.createElement('li');
        let fullPath = parentPath + (parentPath.endsWith('/') ? '' : '/') + item.name;
        if (item.type === 'directory') {
            li.innerHTML = `<span class="folder-toggle cursor-pointer text-blue-800 font-semibold" data-path="${fullPath}">📁 ${item.name}</span> <span class="download-item cursor-pointer text-indigo-600 ml-2" title="Baixar" data-path="${fullPath}" data-type="dir">⬇️</span> <span class="delete-item cursor-pointer text-red-600 ml-2" title="Apagar" data-path="${fullPath}" data-type="dir">🗑️</span>`;
            var ul = document.createElement('ul');
            ul.style.display = 'none';
            ul.className = 'ml-4';
            if (item.children && item.children.length > 0) {
                renderTree(item.children, ul, fullPath);
            }
            li.appendChild(ul);
            li.querySelector('.folder-toggle').addEventListener('click', function(e) {
                e.stopPropagation();
                if (ul.style.display === 'none') {
                    ul.style.display = 'block';
                    pathStack.push(fullPath);
                    setCurrentPath(fullPath);
                } else {
                    ul.style.display = 'none';
                    pathStack.pop();
                    setCurrentPath(pathStack[pathStack.length - 1] || rootPath);
                }
            });
        } else {
            li.innerHTML = `<a href="#" class="file-link text-blue-700 hover:underline" data-file="${fullPath}">🗎 ${item.name}</a> <span class="edit-item cursor-pointer text-green-600 ml-2" title="Editar" data-path="${fullPath}">✏️</span> <span class="download-item cursor-pointer text-indigo-600 ml-2" title="Baixar" data-path="${fullPath}" data-type="file">⬇️</span> <span class="delete-item cursor-pointer text-red-600 ml-2" title="Apagar" data-path="${fullPath}" data-type="file">🗑️</span>`;
        }
        parent.appendChild(li);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    function carregarArvore() {
        fetch(`/api/sistemasArquivos/${sistemaId}/tree`)
            .then(response => response.json())
            .then(data => {
                const fileTree = document.getElementById('file-tree');
                fileTree.innerHTML = '';
                if (!data.tree || data.tree.length === 0) {
                    fileTree.innerHTML = '<li class="text-gray-400 italic">Nenhum arquivo encontrado.</li>';
                } else {
                    renderTree(data.tree, fileTree, rootPath);
                }
                setCurrentPath(currentPath);
                fileTree.addEventListener('click', function(e) {
                    if (e.target.classList.contains('file-link')) {
                        e.preventDefault();
                        const file = e.target.getAttribute('data-file');
                        fetch(`/api/sistemasArquivos/arquivo?file=${encodeURIComponent(file)}&id=${sistemaId}`)
                            .then(resp => resp.text())
                            .then(content => {
                                document.getElementById('file-content').innerText = content;
                                document.getElementById('file-content').setAttribute('data-path', file);
                                document.getElementById('file-content').setAttribute('data-mode', 'view');
                            });
                    }
                    if (e.target.classList.contains('edit-item')) {
                        e.stopPropagation();
                        const path = e.target.getAttribute('data-path');
                        fetch(`/api/sistemasArquivos/arquivo?file=${encodeURIComponent(path)}&id=${sistemaId}`)
                            .then(resp => resp.text())
                            .then(content => {
                                const pre = document.getElementById('file-content');
                                pre.innerHTML = '';
                                const textarea = document.createElement('textarea');
                                textarea.id = 'editor-textarea';
                                textarea.className = 'w-full h-64 max-h-96 p-2 border rounded font-mono text-sm bg-gray-100 text-black';
                                textarea.value = content;
                                textarea.style.overflow = 'auto';
                                pre.appendChild(textarea);
                                const btns = document.createElement('div');
                                btns.className = 'mt-2 flex gap-2';
                                btns.innerHTML = `<button id='btn-save-edit' class='bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition'>Salvar</button><button id='btn-cancel-edit' class='bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500 transition'>Cancelar</button>`;
                                pre.appendChild(btns);
                                pre.setAttribute('data-path', path);
                                pre.setAttribute('data-mode', 'edit');
                                document.getElementById('btn-save-edit').onclick = function() {
                                    const conteudo = document.getElementById('editor-textarea').value;
                                    fetch(`/api/sistemasArquivos/salvar-arquivo`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        },
                                        body: JSON.stringify({ id: sistemaId, path: path, conteudo: conteudo })
                                    })
                                    .then(resp => resp.json())
                                    .then(data => {
                                        if (data.success) {
                                            showCustomAlert('Arquivo salvo com sucesso!', 'success');
                                            pre.innerText = conteudo;
                                            pre.setAttribute('data-mode', 'view');
                                        } else {
                                            showCustomAlert(data.error || 'Erro ao salvar!', 'error');
                                        }
                                    })
                                    .catch(() => {
                                        showCustomAlert('Erro ao salvar!', 'error');
                                    });
                                };
                                document.getElementById('btn-cancel-edit').onclick = function() {
                                    fetch(`/api/sistemasArquivos/arquivo?file=${encodeURIComponent(path)}&id=${sistemaId}`)
                                        .then(resp => resp.text())
                                        .then(content => {
                                            pre.innerText = content;
                                            pre.setAttribute('data-mode', 'view');
                                        });
                                };
                            });
                    }
                    if (e.target.classList.contains('delete-item')) {
                        e.stopPropagation();
                        const path = e.target.getAttribute('data-path');
                        const type = e.target.getAttribute('data-type');
                        showDeleteModal(path, type);
                    }
                    if (e.target.classList.contains('download-item')) {
                        e.stopPropagation();
                        const path = e.target.getAttribute('data-path');
                        // Download via POST para manter padrão
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/api/sistemasArquivos/download';
                        form.style.display = 'none';
                        // CSRF
                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        form.appendChild(csrf);
                        // id
                        const inputId = document.createElement('input');
                        inputId.type = 'hidden';
                        inputId.name = 'id';
                        inputId.value = sistemaId;
                        form.appendChild(inputId);
                        // Path
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'path';
                        input.value = path;
                        form.appendChild(input);
                        document.body.appendChild(form);
                        form.submit();
                        setTimeout(() => form.remove(), 1000);
                    }
                });
            });
    }
    carregarArvore();

    // Upload
    const btnUploadFile = document.getElementById('sistemaArquivos-btn-upload-file');
    const btnUploadFolder = document.getElementById('sistemaArquivos-btn-upload-folder');
    const inputFile = document.getElementById('upload-file');
    const inputFolder = document.getElementById('upload-folder');
    const uploadSelected = document.getElementById('upload-selected');
    const btnUpload = document.getElementById('sistemaArquivos-btn-upload');
    const progressList = document.getElementById('upload-progress-list');
    const totalProgressContainer = document.getElementById('total-progress-container');
    const totalProgressBar = document.getElementById('total-progress-bar');

    btnUploadFile.addEventListener('click', function() { inputFile.click(); });
    btnUploadFolder.addEventListener('click', function() { inputFolder.click(); });
    inputFile.addEventListener('change', function(e) {
        filesToUpload = Array.from(e.target.files);
        if (filesToUpload.length) {
            uploadSelected.innerText = filesToUpload.length + ' arquivo(s) selecionado(s).';
        } else {
            uploadSelected.innerText = '';
        }
        inputFolder.value = '';
    });
    inputFolder.addEventListener('change', function(e) {
        filesToUpload = Array.from(e.target.files);
        if (filesToUpload.length) {
            uploadSelected.innerText = 'Pasta selecionada: ' + (filesToUpload[0].webkitRelativePath ? filesToUpload[0].webkitRelativePath.split('/')[0] : '') + ' (' + filesToUpload.length + ' arquivo(s))';
        } else {
            uploadSelected.innerText = '';
        }
        inputFile.value = '';
    });
    btnUpload.addEventListener('click', async function() {
        if (!filesToUpload.length) {
            return showCustomAlert('Selecione arquivos ou uma pasta!', 'error');
        }
        if (!window.StreamingUpload) {
            return showCustomAlert('Streaming não configurado.', 'error');
        }

        progressList.style.display = '';
        totalProgressContainer.style.display = '';
        totalProgressBar.style.width = '0%';

        try {
            await uploadSistemaArquivosViaStreaming(filesToUpload, ({ descriptor, percent }) => {
                const totalPercent = ((descriptor.fileIndex - 1) + (percent / 100)) / (descriptor.totalFiles || filesToUpload.length) * 100;
                totalProgressBar.style.width = `${totalPercent.toFixed(1)}%`;
            });

            showCustomAlert('Upload concluído!', 'success');
            carregarArvore();
            filesToUpload = [];
            inputFile.value = '';
            inputFolder.value = '';
            uploadSelected.innerText = '';
        } catch (error) {
            showCustomAlert(error.message || 'Erro no upload de arquivo!', 'error');
        } finally {
            setTimeout(() => {
                totalProgressContainer.style.display = 'none';
                totalProgressBar.style.width = '0%';
            }, 800);
        }
    });

    // Exclusão
    document.getElementById('btn-confirm-delete').onclick = function() {
        const senha = document.getElementById('delete-password').value;
        if (!senha) {
            showCustomAlert('Digite a senha!', 'error');
            return;
        }
        fetch(`/api/sistemasArquivos/${sistemaId}/delete-arquivo`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ path: deletePath, password: senha })
        })
        .then(resp => resp.json())
        .then(data => {
            if (data.success) {
                showCustomAlert(data.message || 'Arquivo/pasta apagado!', 'success');
                carregarArvore();
            } else {
                showCustomAlert(data.error || 'Erro ao apagar!', 'error');
            }
            document.getElementById('delete-modal').style.display = 'none';
            deletePath = null;
        })
        .catch(() => {
            showCustomAlert('Erro ao apagar!', 'error');
            document.getElementById('delete-modal').style.display = 'none';
            deletePath = null;
        });
    };

    // Criação de pasta/arquivo
    document.getElementById('btn-confirm-criar').onclick = function() {
        const nome = document.getElementById('modal-criar-input').value.trim();
        if (!nome) {
            showCustomAlert('Digite o nome!', 'error');
            return;
        }
        const path = currentPath;
        let url, body;
        if (criarTipo === 'pasta') {
            url = '/api/sistemasArquivos/criar-pasta';
            body = { id: sistemaId, path, nome };
        } else {
            url = '/api/sistemasArquivos/criar-arquivo';
            body = { id: sistemaId, path, nome };
        }
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(body)
        })
        .then(resp => resp.json())
        .then(data => {
            if (data.success) {
                showCustomAlert((criarTipo === 'arquivo' ? 'Arquivo' : 'Pasta') + ' criado(a) com sucesso!', 'success');
                carregarArvore();
            } else {
                showCustomAlert(data.error || 'Erro ao criar!', 'error');
            }
            document.getElementById('modal-criar').style.display = 'none';
            criarTipo = null;
        })
        .catch(() => {
            showCustomAlert('Erro ao criar!', 'error');
            document.getElementById('modal-criar').style.display = 'none';
            criarTipo = null;
        });
    };

    // Modal de exclusão
    function showDeleteModal(path, type) {
        deletePath = path;
        document.getElementById('delete-item-name').innerText = (type === 'dir' ? 'a pasta' : 'o arquivo') + ' ' + path.split('/').pop();
        document.getElementById('delete-password').value = '';
        document.getElementById('delete-modal').style.display = 'flex';
    }
    document.getElementById('btn-cancel-delete').onclick = function() {
        document.getElementById('delete-modal').style.display = 'none';
        deletePath = null;
    };

    // Modal customizado para criar arquivo/pasta
    function showCriarModal(tipo) {
        criarTipo = tipo;
        document.getElementById('modal-criar-titulo').innerText = tipo === 'arquivo' ? 'Novo arquivo' : 'Nova pasta';
        document.getElementById('modal-criar-input').value = '';
        document.getElementById('modal-criar').style.display = 'flex';
        setTimeout(() => document.getElementById('modal-criar-input').focus(), 100);
    }
    document.getElementById('sistemaArquivos-btn-novo-arquivo').onclick = function() { showCriarModal('arquivo'); };
    document.getElementById('sistemaArquivos-btn-nova-pasta').onclick = function() { showCriarModal('pasta'); };
    document.getElementById('btn-cancel-criar').onclick = function() {
        document.getElementById('modal-criar').style.display = 'none';
        criarTipo = null;
    };

    // Alerta customizado
    function showCustomAlert(message, type = 'success') {
        const alertDiv = document.getElementById('custom-alert');
        const alertContent = document.getElementById('custom-alert-content');
        alertContent.innerHTML = `<i class="fa ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} mr-2"></i> ${message}`;
        alertContent.className = 'bg-white rounded-xl shadow-2xl px-8 py-6 text-center text-lg font-semibold ' + (type === 'success' ? 'text-green-700' : 'text-red-700');
        alertDiv.style.display = 'flex';
        alertDiv.style.animation = 'fadeInDown 0.4s';
        setTimeout(() => {
            alertDiv.style.animation = 'fadeOutUp 0.4s';
            setTimeout(() => { alertDiv.style.display = 'none'; }, 400);
        }, 2500);
    }
});

function uploadSistemaArquivosViaStreaming(files, progressCallback) {
    const client = window.StreamingUpload.getDefaultClient();
    const totalFiles = files.length;

    return client.upload(files, {
        buildRequest: (file, index) => {
            const relativePath = buildSistemaRelativePath(file);
            return {
                relativePath,
                fileName: file.name,
                context: 'sistemas',
                contextPayload: {
                    sistema_id: {{ $sistema->id }},
                },
                fileIndex: index + 1,
                totalFiles,
            };
        },
        onFileProgress: ({ descriptor, percent }) => {
            if (typeof progressCallback === 'function') {
                progressCallback({
                    descriptor,
                    percent: Number(percent),
                });
            }
        },
    });
}

function getSistemaCurrentRelativePath() {
    if (!currentPath || currentPath === '/' || currentPath === rootPath) {
        return '';
    }
    return currentPath.replace(/^\//, '');
}

function buildSistemaRelativePath(file) {
    let relative = file.webkitRelativePath ? file.webkitRelativePath.replace(/\\/g, '/') : file.name;
    relative = relative.replace(/^\//, '');

    const currentRelative = getSistemaCurrentRelativePath();
    if (currentRelative) {
        relative = currentRelative.replace(/\/$/, '') + '/' + relative;
    }

    return relative.replace(/^\//, '');
}
</script> 