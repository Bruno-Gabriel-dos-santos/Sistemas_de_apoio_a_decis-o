@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto mt-8 space-y-6">
    @if (($section['slug'] ?? null) === 'irpf')
        <div class="flex items-center justify-between bg-white rounded-2xl shadow px-6 py-5 border border-gray-100">
            <div>
                <p class="text-sm uppercase tracking-wide text-indigo-500 font-semibold">Área financeira</p>
                <h1 class="text-3xl font-bold text-gray-900">IRPF</h1>
            </div>
            <a href="{{ route('financeiro.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
        </div>

        @if (session('success'))
            <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 px-4 py-2 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-2 rounded-lg">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            @foreach ($irpfBoards as $bucket => $board)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col h-full">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $board['title'] }}</h3>
                            <p class="text-sm text-gray-500">{{ $board['description'] }}</p>
                        </div>
                        <form method="POST" action="{{ route('financeiro.irpf.upload') }}" enctype="multipart/form-data" class="flex gap-2">
                            @csrf
                            <input type="hidden" name="bucket" value="{{ $bucket }}">
                            <label for="upload-{{ $bucket }}" class="p-2 rounded-full border border-gray-200 text-gray-500 hover:text-indigo-600 hover:border-indigo-200 cursor-pointer" title="Enviar PDF">
                                <i class="fa fa-upload"></i>
                            </label>
                            <input id="upload-{{ $bucket }}" type="file" name="arquivo" accept="application/pdf" class="sr-only" onchange="this.form.submit()">
                        </form>
                    </div>
                    <div class="mt-4 space-y-3 overflow-y-auto pr-2 custom-scroll flex-1" style="max-height: 320px;">
                        @php
                            $items = $irpfDocuments[$bucket] ?? [];
                        @endphp
                        @forelse ($items as $item)
                            <div class="rounded-xl border border-gray-200 p-3 flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $item['name'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $item['updated'] }}</p>
                                    </div>
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100">{{ $board['title'] }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <span>{{ $item['size'] }}</span>
                                    <div class="flex gap-2">
                                        <a href="{{ route('financeiro.irpf.view', ['bucket' => $bucket, 'filename' => $item['name']]) }}"
                                           target="_blank"
                                           class="text-xs uppercase tracking-wide px-2 py-1 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200">
                                            Visualizar
                                        </a>
                                        <a href="{{ route('financeiro.irpf.download', ['bucket' => $bucket, 'filename' => $item['name']]) }}"
                                           class="text-xs uppercase tracking-wide px-2 py-1 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200">
                                            Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Nenhum documento cadastrado.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-2xl shadow px-6 py-6 flex items-center justify-between border border-gray-100">
            <div>
                <p class="text-sm uppercase tracking-wide text-indigo-500 font-semibold">Área financeira</p>
                <h1 class="text-3xl font-bold text-gray-900">{{ $section['title'] }}</h1>
            </div>
            <a href="{{ route('financeiro.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
        </div>

        <div id="fin-feedback" class="hidden px-4 py-2 rounded-lg text-sm font-semibold"></div>

        <div class="bg-white rounded-2xl shadow px-6 py-6 space-y-4 border border-gray-100">
            <div class="flex flex-col md:flex-row gap-6">
                <div class="w-full md:w-1/3 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Caminho atual</p>
                            <div id="fin-current-path" class="text-sm font-mono text-gray-800">/</div>
                        </div>
                        <button id="fin-btn-root" class="text-xs px-3 py-1 rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50">Ir para raiz</button>
                    </div>
                    <div>
                        <input type="file" id="fin-upload-file" multiple class="hidden" />
                        <input type="file" id="fin-upload-folder" webkitdirectory directory class="hidden" />
                        <div class="flex flex-col gap-2">
                            <button id="fin-btn-upload-file" type="button" class="w-full flex items-center justify-center gap-2 bg-indigo-500 text-white py-2 rounded-lg shadow hover:bg-indigo-600 hover:scale-[1.01] transition font-semibold"><i class="fa fa-upload"></i>Selecionar arquivo(s)</button>
                            <button id="fin-btn-upload-folder" type="button" class="w-full flex items-center justify-center gap-2 bg-emerald-500 text-white py-2 rounded-lg shadow hover:bg-emerald-600 hover:scale-[1.01] transition font-semibold"><i class="fa fa-folder-open"></i>Selecionar pasta</button>
                            <button id="fin-btn-upload-start" type="button" class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white py-2 rounded-lg shadow hover:bg-blue-700 hover:scale-[1.01] transition font-semibold"><i class="fa fa-cloud-upload-alt"></i>Enviar</button>
                        </div>
                        <div id="fin-upload-selected" class="text-xs text-gray-500 mt-2"></div>
                        <div id="fin-upload-progress" class="hidden mt-2">
                            <div class="w-full bg-gray-200 rounded h-2 overflow-hidden">
                                <div id="fin-progress-bar" class="bg-indigo-500 h-2" style="width:0%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button id="fin-btn-new-file" type="button" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 py-2 rounded-lg border border-gray-200 text-sm font-semibold">Novo arquivo</button>
                        <button id="fin-btn-new-folder" type="button" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 py-2 rounded-lg border border-gray-200 text-sm font-semibold">Nova pasta</button>
                    </div>
                    <div class="border border-dashed border-gray-300 rounded-lg p-3 max-h-[420px] overflow-y-auto">
                        <ul id="fin-file-tree" class="space-y-1 text-sm text-gray-700">
                            <li class="text-gray-400 italic">Carregando arquivos...</li>
                        </ul>
                    </div>
                </div>
                <div class="w-full md:w-2/3">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2"><i class="fa fa-file-alt text-indigo-500"></i>Pré-visualização</h2>
                        <span id="fin-selected-file-label" class="text-sm text-gray-500">Nenhum arquivo selecionado</span>
                    </div>
                    <pre id="fin-file-content" class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 text-green-200 rounded-xl p-4 min-h-[280px] max-h-[420px] overflow-auto whitespace-pre-wrap font-mono border border-slate-600">Selecione um arquivo para visualizar.</pre>
                </div>
            </div>
        </div>
        <div id="fin-modal-wrapper" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <h3 id="fin-modal-title" class="text-xl font-semibold text-gray-900">Novo item</h3>
                <input id="fin-modal-input" type="text" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400" placeholder="Digite o nome">
                <div class="flex gap-3 justify-end">
                    <button id="fin-modal-cancel" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancelar</button>
                    <button id="fin-modal-confirm" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Criar</button>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Fast access</p>
                <h3 class="text-lg font-semibold text-gray-900">Outras áreas financeiras</h3>
            </div>
            <span class="text-sm text-gray-500">Navegue rapidamente entre as seções</span>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($sections as $slug => $item)
                <a href="{{ route('financeiro.show', $slug) }}"
                   class="px-3 py-1.5 rounded-full border {{ $item['title'] === $section['title'] ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">
                    {{ $item['title'] }}
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection

@if (($section['slug'] ?? null) === 'irpf')
    {{-- Scripts específicos já tratados acima --}}
@else
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sectionSlug = @json($section['slug']);
    const apiBase = `/api/financeiro-arquivos/${sectionSlug}`;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const fileTreeEl = document.getElementById('fin-file-tree');
    const currentPathEl = document.getElementById('fin-current-path');
    const selectedLabel = document.getElementById('fin-selected-file-label');
    const fileContentEl = document.getElementById('fin-file-content');
    const feedbackEl = document.getElementById('fin-feedback');

    const btnRoot = document.getElementById('fin-btn-root');
    const btnUploadFile = document.getElementById('fin-btn-upload-file');
    const btnUploadFolder = document.getElementById('fin-btn-upload-folder');
    const btnUploadStart = document.getElementById('fin-btn-upload-start');
    const inputUploadFile = document.getElementById('fin-upload-file');
    const inputUploadFolder = document.getElementById('fin-upload-folder');
    const uploadSelectedEl = document.getElementById('fin-upload-selected');
    const uploadProgressEl = document.getElementById('fin-upload-progress');
    const progressBarEl = document.getElementById('fin-progress-bar');
    const btnNewFile = document.getElementById('fin-btn-new-file');
    const btnNewFolder = document.getElementById('fin-btn-new-folder');
    const modalWrapper = document.getElementById('fin-modal-wrapper');
    const modalTitle = document.getElementById('fin-modal-title');
    const modalInput = document.getElementById('fin-modal-input');
    const modalCancel = document.getElementById('fin-modal-cancel');
    const modalConfirm = document.getElementById('fin-modal-confirm');

    let currentPath = '/';
    let filesToUpload = [];
    let currentFilePath = null;

    function showMessage(message, type = 'success') {
        if (!feedbackEl) return alert(message);
        feedbackEl.textContent = message;
        feedbackEl.classList.remove('hidden');
        feedbackEl.classList.toggle('bg-emerald-100', type === 'success');
        feedbackEl.classList.toggle('text-emerald-700', type === 'success');
        feedbackEl.classList.toggle('border', true);
        feedbackEl.classList.toggle('border-emerald-200', type === 'success');
        feedbackEl.classList.toggle('bg-red-100', type === 'error');
        feedbackEl.classList.toggle('text-red-700', type === 'error');
        feedbackEl.classList.toggle('border-red-200', type === 'error');
        setTimeout(() => {
            feedbackEl.classList.add('hidden');
        }, 4000);
    }

    function setCurrentPath(relative) {
        relative = relative || '';
        currentPath = relative ? '/' + relative : '/';
        currentPathEl.textContent = currentPath;
    }

    function getCurrentRelativePath() {
        return currentPath === '/' ? '' : currentPath.replace(/^\//, '');
    }

    function fetchTree() {
        fetch(`${apiBase}/tree`)
            .then(response => response.json())
            .then(data => {
                const nodes = data.tree || [];
                fileTreeEl.innerHTML = '';
                if (!nodes.length) {
                    fileTreeEl.innerHTML = '<li class="text-gray-400 italic">Nenhum arquivo encontrado.</li>';
                } else {
                    nodes.forEach(node => fileTreeEl.appendChild(createTreeNode(node, '')));
                }
            })
            .catch(() => showMessage('Não foi possível carregar a árvore de arquivos.', 'error'));
    }

    function createTreeNode(node, parentPath) {
        const li = document.createElement('li');
        li.className = 'py-1';
        const fullPath = parentPath ? `${parentPath}/${node.name}` : node.name;

        if (node.type === 'directory') {
            li.innerHTML = `<div class="flex items-center justify-between gap-2">
                <span class="fin-folder cursor-pointer text-blue-700 font-semibold" data-path="${fullPath}">📁 ${node.name}</span>
                <div class="flex gap-1 text-xs">
                    <button class="fin-download text-indigo-600 hover:text-indigo-800" data-path="${fullPath}" data-type="dir" title="Baixar"><i class="fa fa-download"></i></button>
                    <button class="fin-delete text-red-600 hover:text-red-800" data-path="${fullPath}" data-type="dir" title="Excluir"><i class="fa fa-trash"></i></button>
                </div>
            </div>`;
            const childList = document.createElement('ul');
            childList.className = 'ml-4 border-l border-dashed border-gray-200 pl-2';
            if (node.children && node.children.length) {
                node.children.forEach(child => childList.appendChild(createTreeNode(child, fullPath)));
            } else {
                childList.innerHTML = '<li class="text-xs text-gray-400 italic">Vazio</li>';
            }
            li.appendChild(childList);
        } else {
            li.innerHTML = `<div class="flex items-center justify-between gap-2">
                <span class="fin-file cursor-pointer text-gray-800" data-path="${fullPath}">🗎 ${node.name}</span>
                <div class="flex gap-1 text-xs">
                    <button class="fin-edit text-green-600 hover:text-green-800" data-path="${fullPath}" title="Editar"><i class="fa fa-edit"></i></button>
                    <button class="fin-download text-indigo-600 hover:text-indigo-800" data-path="${fullPath}" data-type="file" title="Baixar"><i class="fa fa-download"></i></button>
                    <button class="fin-delete text-red-600 hover:text-red-800" data-path="${fullPath}" data-type="file" title="Excluir"><i class="fa fa-trash"></i></button>
                </div>
            </div>`;
        }

        return li;
    }

    fileTreeEl.addEventListener('click', (event) => {
        const target = event.target.closest('button, span');
        if (!target) return;

        if (target.classList.contains('fin-folder')) {
            setCurrentPath(target.dataset.path || '');
            selectedLabel.textContent = 'Nenhum arquivo selecionado';
            fileContentEl.textContent = 'Selecione um arquivo para visualizar.';
            currentFilePath = null;
            return;
        }

        if (target.classList.contains('fin-file')) {
            openFile(target.dataset.path, false);
            return;
        }

        if (target.classList.contains('fin-edit')) {
            event.stopPropagation();
            openFile(target.dataset.path, true);
            return;
        }

        if (target.classList.contains('fin-download')) {
            event.stopPropagation();
            downloadItem(target.dataset.path);
            return;
        }

        if (target.classList.contains('fin-delete')) {
            event.stopPropagation();
            deleteItem(target.dataset.path);
        }
    });

    function openFile(path, editMode = false) {
        if (!path) return;
        fetch(`${apiBase}/file?path=${encodeURIComponent(path)}`)
            .then(resp => {
                if (!resp.ok) {
                    return resp.text().then(text => Promise.reject(text || 'Erro ao carregar arquivo.'));
                }
                return resp.text();
            })
            .then(content => {
                currentFilePath = path;
                selectedLabel.textContent = path;
                if (editMode) {
                    enterEditMode(path, content);
                } else {
                    fileContentEl.textContent = content;
                }
            })
            .catch(error => showMessage(error, 'error'));
    }

    function enterEditMode(path, content) {
        fileContentEl.innerHTML = '';
        const textarea = document.createElement('textarea');
        textarea.id = 'fin-editor';
        textarea.className = 'w-full h-64 p-3 border rounded-lg bg-white text-gray-800 font-mono text-sm';
        textarea.value = content;
        fileContentEl.appendChild(textarea);

        const actions = document.createElement('div');
        actions.className = 'mt-3 flex gap-2';
        actions.innerHTML = `
            <button id="fin-save-edit" class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700 flex items-center gap-2"><i class="fa fa-save"></i>Salvar</button>
            <button id="fin-cancel-edit" class="px-4 py-2 rounded-lg bg-gray-400 text-white hover:bg-gray-500 flex items-center gap-2"><i class="fa fa-times"></i>Cancelar</button>
        `;
        fileContentEl.appendChild(actions);

        document.getElementById('fin-save-edit').onclick = () => {
            const conteudo = textarea.value;
            fetch(`${apiBase}/save`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ path, conteudo }),
            })
                .then(resp => resp.json())
                .then(data => {
                    if (data.success) {
                        showMessage('Arquivo salvo com sucesso!');
                        fileContentEl.textContent = conteudo;
                    } else {
                        showMessage(data.error || 'Erro ao salvar arquivo.', 'error');
                    }
                })
                .catch(() => showMessage('Erro ao salvar arquivo.', 'error'));
        };

        document.getElementById('fin-cancel-edit').onclick = () => openFile(path, false);
    }

    function deleteItem(path) {
        if (!path) return;
        if (!confirm('Deseja realmente excluir este item?')) {
            return;
        }
        fetch(`${apiBase}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ path }),
        })
            .then(resp => resp.json())
            .then(data => {
                if (data.success) {
                    showMessage('Item excluído com sucesso!');
                    fetchTree();
                } else {
                    showMessage(data.error || 'Erro ao excluir item.', 'error');
                }
            })
            .catch(() => showMessage('Erro ao excluir item.', 'error'));
    }

    function downloadItem(path) {
        if (!path) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${apiBase}/download`;
        form.style.display = 'none';
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = csrfToken;
        form.appendChild(csrf);
        const inputPath = document.createElement('input');
        inputPath.type = 'hidden';
        inputPath.name = 'path';
        inputPath.value = path;
        form.appendChild(inputPath);
        document.body.appendChild(form);
        form.submit();
        setTimeout(() => form.remove(), 1000);
    }

    btnRoot.addEventListener('click', () => {
        setCurrentPath('');
        selectedLabel.textContent = 'Nenhum arquivo selecionado';
        fileContentEl.textContent = 'Selecione um arquivo para visualizar.';
    });

    let modalMode = null;
    function openModal(mode) {
        modalMode = mode;
        modalTitle.textContent = mode === 'file' ? 'Novo arquivo' : 'Nova pasta';
        modalInput.value = '';
        modalWrapper.classList.remove('hidden');
        modalWrapper.classList.add('flex');
        setTimeout(() => modalInput.focus(), 50);
    }

    function closeModal() {
        modalWrapper.classList.add('hidden');
        modalWrapper.classList.remove('flex');
        modalMode = null;
    }

    modalCancel.addEventListener('click', closeModal);

    modalConfirm.addEventListener('click', () => {
        const nome = modalInput.value.trim();
        if (!nome) {
            modalInput.focus();
            return;
        }
        const endpoint = modalMode === 'file' ? 'create-file' : 'create-folder';
        fetch(`${apiBase}/${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ path: getCurrentRelativePath(), nome }),
        })
            .then(resp => resp.json())
            .then(data => {
                if (data.success) {
                    showMessage(modalMode === 'file' ? 'Arquivo criado com sucesso!' : 'Pasta criada com sucesso!');
                    fetchTree();
                } else {
                    showMessage(data.error || 'Não foi possível concluir a operação.', 'error');
                }
            })
            .catch(() => showMessage('Erro ao comunicar com o servidor.', 'error'))
            .finally(closeModal);
    });

    btnNewFile.addEventListener('click', () => openModal('file'));
    btnNewFolder.addEventListener('click', () => openModal('folder'));

    btnUploadFile.addEventListener('click', () => inputUploadFile.click());
    btnUploadFolder.addEventListener('click', () => inputUploadFolder.click());

    inputUploadFile.addEventListener('change', (event) => {
        filesToUpload = Array.from(event.target.files);
        uploadSelectedEl.textContent = filesToUpload.length ? `${filesToUpload.length} arquivo(s) selecionado(s).` : '';
        inputUploadFolder.value = '';
    });

    inputUploadFolder.addEventListener('change', (event) => {
        filesToUpload = Array.from(event.target.files);
        if (filesToUpload.length) {
            const first = filesToUpload[0].webkitRelativePath || '';
            const folderName = first.split('/')[0] || 'pasta';
            uploadSelectedEl.textContent = `${folderName} (${filesToUpload.length} arquivos)`;
        } else {
            uploadSelectedEl.textContent = '';
        }
        inputUploadFile.value = '';
    });

    btnUploadStart.addEventListener('click', async () => {
        if (!filesToUpload.length) {
            return showMessage('Selecione arquivos ou uma pasta.', 'error');
        }
        if (!window.StreamingUpload) {
            return showMessage('Streaming não configurado neste ambiente.', 'error');
        }
        uploadProgressEl.classList.remove('hidden');
        progressBarEl.style.width = '0%';

        try {
            await uploadFinanceiroViaStreaming(filesToUpload, ({ descriptor, percent }) => {
                const totalPercent = ((descriptor.fileIndex - 1) + (percent / 100)) / (descriptor.totalFiles || filesToUpload.length) * 100;
                progressBarEl.style.width = `${totalPercent.toFixed(1)}%`;
            });
            showMessage('Upload concluído com sucesso!');
            filesToUpload = [];
            uploadSelectedEl.textContent = '';
            inputUploadFile.value = '';
            inputUploadFolder.value = '';
            fetchTree();
        } catch (error) {
            showMessage(error.message || 'Erro durante o upload.', 'error');
        } finally {
            setTimeout(() => {
                uploadProgressEl.classList.add('hidden');
                progressBarEl.style.width = '0%';
            }, 1000);
        }
    });

    function uploadFinanceiroViaStreaming(files, progressCallback) {
        const client = window.StreamingUpload.getDefaultClient();
        const totalFiles = files.length;

        return client.upload(files, {
            buildRequest: (file, index) => {
                const relativePath = buildFinanceiroRelativePath(file);
                return {
                    relativePath,
                    fileName: file.name,
                    context: 'financeiro',
                    contextPayload: {
                        section: sectionSlug,
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

    function buildFinanceiroRelativePath(file) {
        let relative = file.webkitRelativePath ? file.webkitRelativePath.replace(/\\/g, '/') : file.name;
        relative = relative.replace(/^\//, '');

        const currentRelative = getCurrentRelativePath();
        if (currentRelative) {
            relative = currentRelative.replace(/\/$/, '') + '/' + relative;
        }

        return relative.replace(/^\//, '');
    }

    fetchTree();
});
</script>
@endif

