@extends('layouts.app')

@section('content')
<div class="py-12 flex flex-col items-center">
    <div class="w-4/5 mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">{{ $categoria->categoria }}</h2>
        <div class="flex items-center justify-between mb-4">
            <input id="pesquisa-arquivo" type="text" placeholder="Pesquisar arquivos..." class="border rounded-lg px-4 py-1.5 w-1/3 focus:ring-2 focus:ring-blue-400" />
            <div class="flex">
                <button id="btn-upload-arquivo-individual" class="w-44 px-0 py-1.5 font-semibold bg-blue-600 text-white shadow hover:bg-blue-700 transition-all rounded-l-lg border-r border-white">Enviar Arquivos</button>
                <button id="btn-upload-arquivo" class="w-44 px-0 py-1.5 font-semibold bg-green-600 text-white shadow hover:bg-green-700 transition-all border-l border-white border-r-0 rounded-none">Enviar Pastas</button>
                <button id="btn-criar-pasta" class="w-44 px-0 py-1 rounded-r-lg bg-blue-500 text-white hover:bg-blue-600 text-base border-l border-white">Nova Pasta</button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <div class="mb-4 flex items-center gap-2">
                <button id="btn-voltar-pasta" class="px-2 py-1 rounded bg-gray-200 text-gray-700 hover:bg-gray-300" style="display:none">Voltar</button>
                <span id="caminho-atual" class="text-sm text-gray-600"></span>
            </div>
            <table class="min-w-full bg-white rounded-lg shadow">
                <thead>
                    <tr class="bg-gray-100 text-gray-700">
                        <th class="px-4 py-2">Nome</th>
                        <th class="px-4 py-2">Descrição</th>
                        <th class="px-4 py-2">Data</th>
                        <th class="px-4 py-2">Tamanho</th>
                        <th class="px-4 py-2">Ações</th>
                    </tr>
                </thead>
                <tbody id="tabela-arquivos">
                    <!-- Linhas de arquivos via JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Modal Visualizar Arquivo -->
<div id="modal-visualizar-arquivo" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-2xl relative">
        <button id="btn-fechar-modal" class="absolute top-2 right-2 text-gray-500 hover:text-red-600 text-2xl">&times;</button>
        <div id="conteudo-arquivo" class="w-full"></div>
    </div>
</div>
<!-- Modal Upload Arquivo -->
<div id="modal-upload-arquivo" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Adicionar Arquivo(s) ou Pasta</h3>
        <form id="form-upload-arquivo">
            <input type="file" id="input-arquivo" class="mb-4" multiple webkitdirectory directory />
            <input type="text" id="input-descricao" class="border rounded-lg px-4 py-2 w-full mb-4" placeholder="Descrição (opcional, para todos)" />
            <div class="w-full mb-2">
                <div class="bg-gray-200 rounded h-3 overflow-hidden">
                    <div id="progress-bar" class="bg-green-500 h-3 rounded" style="width:0%"></div>
                </div>
                <div id="progress-text" class="text-xs text-gray-600 mt-1"></div>
            </div>
            <button type="submit" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700">Enviar</button>
            <button type="button" id="btn-cancelar-upload" class="px-4 py-2 rounded bg-gray-300 text-gray-800 hover:bg-gray-400 ml-2">Cancelar</button>
        </form>
    </div>
</div>
<!-- Modal Nova Pasta -->
<div id="modal-nova-pasta" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Criar Nova Pasta</h3>
        <form id="form-nova-pasta">
            <input type="text" id="input-nome-pasta" class="border rounded-lg px-4 py-2 w-full mb-4" placeholder="Nome da pasta" required />
            <div class="flex justify-end gap-2">
                <button type="button" id="btn-cancelar-nova-pasta" class="px-4 py-2 rounded bg-gray-300 text-gray-800 hover:bg-gray-400">Cancelar</button>
                <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Criar</button>
            </div>
        </form>
    </div>
</div>
<!-- Modal de confirmação de exclusão -->
<div id="modal-confirmar-exclusao" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md flex flex-col items-center">
        <div class="text-lg font-semibold mb-4">Tem certeza que deseja excluir?</div>
        <div class="flex gap-4">
            <button id="btn-confirmar-excluir" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Excluir</button>
            <button id="btn-cancelar-excluir" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Cancelar</button>
        </div>
    </div>
</div>
<!-- Botão e modal para upload de arquivo individual -->
<div id="modal-upload-arquivo-individual" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Adicionar Arquivo</h3>
        <form id="form-upload-arquivo-individual">
            <input type="file" id="input-arquivo-individual" class="mb-4" multiple />
            <input type="text" id="input-descricao-individual" class="border rounded-lg px-4 py-2 w-full mb-4" placeholder="Descrição (opcional, para todos)" />
            <div class="w-full mb-2">
                <div class="bg-gray-200 rounded h-3 overflow-hidden">
                    <div id="progress-bar-individual" class="bg-green-500 h-3 rounded" style="width:0%"></div>
                </div>
                <div id="progress-text-individual" class="text-xs text-gray-600 mt-1"></div>
            </div>
            <button type="submit" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700">Enviar</button>
            <button type="button" id="btn-cancelar-upload-individual" class="px-4 py-2 rounded bg-gray-300 text-gray-800 hover:bg-gray-400 ml-2">Cancelar</button>
        </form>
    </div>
</div>
<script>
const idCategoria = {{ $categoria->id }};
const WS_URLS = @json($websocket_urls ?? []);
const STREAM_UPLOAD_TOKEN = @json($upload_token ?? null);
let todosArquivos = [];
let pastaAtual = '';
let filtroAtual = '';

// Variáveis para controle da exclusão
let excluirTipo = null; // 'arquivo' ou 'pasta'
let excluirId = null;
let excluirNomePasta = null;

function atualizarCaminho() {
    document.getElementById('caminho-atual').innerText = pastaAtual ? '/' + pastaAtual : '/';
    document.getElementById('btn-voltar-pasta').style.display = pastaAtual ? '' : 'none';
}

document.getElementById('btn-voltar-pasta').onclick = function() {
    if (!pastaAtual) return;
    const partes = pastaAtual.split('/');
    partes.pop();
    pastaAtual = partes.join('/');
    exibirArquivosNaPasta();
};

function buscarArquivos(filtro = '') {
    filtroAtual = filtro;
    fetch('/arquivos/listar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ categoria: idCategoria, filtro })
    })
    .then(resp => {
        const contentType = resp.headers.get('content-type') || '';
        if (!resp.ok || !contentType.includes('application/json')) {
            return resp.text().then(text => {
                const errorText = text && text.trim().length ? text : 'Resposta inválida ao listar arquivos.';
                throw new Error(errorText);
            });
        }
        return resp.json();
    })
    .then(arquivos => {
        todosArquivos = arquivos;
        exibirArquivosNaPasta();
    })
    .catch(error => {
        showCustomAlert('Não foi possível carregar os arquivos. Tente novamente.', 'error');
    });
}

function exibirArquivosNaPasta() {
    atualizarCaminho();
    const tabela = document.getElementById('tabela-arquivos');
    tabela.innerHTML = '';

    // Função para pegar o path relativo ao diretório atual
    function getRelPath(arq) {
        let relPath = arq.path || arq.nome;
        if (pastaAtual && relPath.startsWith(pastaAtual + '/')) {
            relPath = relPath.substring(pastaAtual.length + 1);
        } else if (pastaAtual && relPath !== pastaAtual) {
            // Se não começa com pastaAtual, não está neste diretório
            return null;
        }
        return relPath;
    }

    // Filtrar pastas do diretório atual
    const pastas = todosArquivos.filter(arq => {
        if (arq.tipo !== 'pasta') return false;
        const relPath = getRelPath(arq);
        if (!relPath) return false;
        if (filtroAtual && !arq.nome.toLowerCase().includes(filtroAtual.toLowerCase())) return false;
        // Não mostrar a pasta cujo path é igual ao da pastaAtual
        if (arq.path === pastaAtual) return false;
        return !relPath.includes('/');
    });

    // Filtrar arquivos do diretório atual
    const arquivos = todosArquivos.filter(arq => {
        if (arq.tipo !== 'arquivo') return false;
        const relPath = getRelPath(arq);
        if (!relPath) return false;
        if (filtroAtual && !arq.nome.toLowerCase().includes(filtroAtual.toLowerCase())) return false;
        return !relPath.includes('/');
    });

    // Exibir pastas
    pastas.forEach(pasta => {
        const linha = document.createElement('tr');
        linha.innerHTML = `
            <td class="px-4 py-2 font-bold text-blue-700 cursor-pointer flex items-center gap-2">
                <span class="abrir-pasta"><i class="fa fa-folder mr-2"></i>${pasta.nome}</span>
            </td>
            <td class="px-4 py-2"></td>
            <td class="px-4 py-2"></td>
            <td class="px-4 py-2"></td>
            <td class="px-4 py-2 flex gap-2">
                <button class="excluir-pasta px-3 py-1.5 bg-red-500 text-white rounded hover:bg-red-600 text-sm" title="Excluir pasta"><i class="fa fa-trash"></i> Excluir</button>
                <button class="baixar-pasta px-3 py-1.5 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm" title="Baixar pasta"><i class="fa fa-download"></i> Baixar</button>
            </td>
        `;
        linha.querySelector('.abrir-pasta').onclick = function(e) {
            e.stopPropagation();
            pastaAtual = pastaAtual ? pastaAtual + '/' + pasta.nome : pasta.nome;
            exibirArquivosNaPasta();
        };
        linha.querySelector('.excluir-pasta').onclick = function(e) {
            e.stopPropagation();
            excluirPasta(pasta.nome);
        };
        linha.querySelector('.baixar-pasta').onclick = function(e) {
            e.stopPropagation();
            baixarArquivoOuPasta(pasta.id, pasta.nome + '.zip');
        };
        tabela.appendChild(linha);
    });

    // Exibir arquivos
    arquivos.forEach(arq => {
        let linha = document.createElement('tr');
        linha.innerHTML = `
            <td class="px-4 py-2">${arq.nome}</td>
            <td class="px-4 py-2">${arq.descricao || ''}</td>
            <td class="px-4 py-2">${arq.data || ''}</td>
            <td class="px-4 py-2">${arq.tamanho_arquivo ? (arq.tamanho_arquivo/1024).toFixed(1) + ' KB' : ''}</td>
            <td class="px-4 py-2 flex gap-2">
                <button class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600" onclick="visualizarArquivo('${arq.id}')">Visualizar</button>
                <button class="px-3 py-1.5 bg-red-500 text-white rounded hover:bg-red-600 text-sm" onclick="excluirArquivo('${arq.id}')">Excluir</button>
                <button class="baixar-arquivo px-3 py-1.5 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm" title="Baixar arquivo"><i class="fa fa-download"></i> Baixar</button>
            </td>
        `;
        linha.querySelector('.baixar-arquivo').onclick = function(e) {
            e.stopPropagation();
            baixarArquivoOuPasta(arq.id, arq.nome);
        };
        tabela.appendChild(linha);
    });

    if (pastas.length === 0 && arquivos.length === 0) {
        tabela.innerHTML = '<tr><td colspan="5" class="text-center text-gray-400">Nenhum arquivo ou pasta encontrado.</td></tr>';
    }
}
// Substituir chamada de buscarArquivos() para exibir a árvore
buscarArquivos();
// Pesquisa dinâmica
const pesquisaInput = document.getElementById('pesquisa-arquivo');
pesquisaInput.addEventListener('input', function() {
    buscarArquivos(this.value);
});
// Modal Visualizar
function visualizarArquivo(id) {
    window.open('/arquivos/visualizador/' + id, '_blank');
}
document.getElementById('btn-fechar-modal').onclick = function() {
    document.getElementById('modal-visualizar-arquivo').style.display = 'none';
};
// Função para abrir modal de confirmação
function abrirModalExclusao(tipo, id, nomePasta = null) {
    excluirTipo = tipo;
    excluirId = id;
    excluirNomePasta = nomePasta;
    document.getElementById('modal-confirmar-exclusao').style.display = 'flex';
}
// Fechar modal
function fecharModalExclusao() {
    document.getElementById('modal-confirmar-exclusao').style.display = 'none';
    excluirTipo = null;
    excluirId = null;
    excluirNomePasta = null;
}
// Botão cancelar
document.getElementById('btn-cancelar-excluir').onclick = fecharModalExclusao;
// Botão confirmar
document.getElementById('btn-confirmar-excluir').onclick = function() {
    if (excluirTipo === 'arquivo') {
        fetch('/arquivos/excluir', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ id: excluirId })
        })
        .then(resp => resp.json())
        .then(() => { fecharModalExclusao(); buscarArquivos(); });
    } else if (excluirTipo === 'pasta') {
        // Encontrar o registro da pasta pelo path
        const pathPasta = pastaAtual ? pastaAtual + '/' + excluirNomePasta : excluirNomePasta;
        const pasta = todosArquivos.find(a => a.path === pathPasta && a.tipo === 'pasta');
        if (!pasta) { fecharModalExclusao(); return; }
        fetch('/arquivos/excluir-pasta', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ id: pasta.id })
        })
        .then(resp => resp.json())
        .then(() => { fecharModalExclusao(); buscarArquivos(); });
    }
};
// Substituir alert por modal estilizada
function excluirArquivo(id) {
    abrirModalExclusao('arquivo', id);
}
function excluirPasta(nomePasta) {
    abrirModalExclusao('pasta', null, nomePasta);
}
// Modal Upload
const btnUpload = document.getElementById('btn-upload-arquivo');
const modalUpload = document.getElementById('modal-upload-arquivo');
const btnCancelarUpload = document.getElementById('btn-cancelar-upload');
const formUpload = document.getElementById('form-upload-arquivo');

btnUpload.onclick = function() {
    modalUpload.style.display = 'flex';
};
btnCancelarUpload.onclick = function() {
    modalUpload.style.display = 'none';
};
formUpload.onsubmit = async function(e) {
    e.preventDefault();
    const files = document.getElementById('input-arquivo').files;
    const descricao = document.getElementById('input-descricao').value;
    if (!files.length) return;

    modalUpload.style.display = 'flex';

    const totalFiles = files.length;
    const updateProgress = ({ percent, fileIndex }) => {
        document.getElementById('progress-bar').style.width = percent + '%';
        document.getElementById('progress-text').innerText = `Enviando ${fileIndex}/${totalFiles} arquivos (${percent}%)`;
    };

    try {
        await uploadArquivosViaStreaming(
            files,
            (file) => {
                let path = file.webkitRelativePath || file.name;
                if (pastaAtual) {
                    path = pastaAtual.replace(/\/$/, '') + '/' + path.replace(/^\//, '');
                }
                return path.replace(/^\//, '');
            },
            descricao,
            updateProgress
        );
        modalUpload.style.display = 'none';
        buscarArquivos();
    } catch (error) {
        modalUpload.style.display = 'none';
        alert('Erro ao enviar arquivos: ' + error.message);
    }
};
document.getElementById('btn-criar-pasta').onclick = function() {
    document.getElementById('modal-nova-pasta').style.display = 'flex';
    document.getElementById('input-nome-pasta').value = '';
    document.getElementById('input-nome-pasta').focus();
};
document.getElementById('btn-cancelar-nova-pasta').onclick = function() {
    document.getElementById('modal-nova-pasta').style.display = 'none';
};
document.getElementById('form-nova-pasta').onsubmit = function(e) {
    e.preventDefault();
    const nome = document.getElementById('input-nome-pasta').value.trim();
    if (!nome) return;
    const path = pastaAtual ? pastaAtual + '/' + nome : nome;
    fetch('/arquivos/criar-pasta', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ categoria: idCategoria, path, nome })
    })
    .then(resp => resp.json())
    .then(() => {
        document.getElementById('modal-nova-pasta').style.display = 'none';
        buscarArquivos();
    });
};
// Função para baixar arquivo ou pasta via fetch assíncrono
function baixarArquivoOuPasta(id, nome) {
    const url = `/arquivos/download/${id}?zip=1`;
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(resp => {
        if (!resp.ok) throw new Error('Erro ao baixar');
        return resp.blob();
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        const downloadName = nome.toLowerCase().endsWith('.zip') ? nome : `${nome}.zip`;
        a.download = downloadName;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    })
    .catch(() => alert('Erro ao baixar arquivo/pasta'));
}
// JS para upload de arquivo individual
const btnUploadIndividual = document.getElementById('btn-upload-arquivo-individual');
const modalUploadIndividual = document.getElementById('modal-upload-arquivo-individual');
const btnCancelarUploadIndividual = document.getElementById('btn-cancelar-upload-individual');
const formUploadIndividual = document.getElementById('form-upload-arquivo-individual');

btnUploadIndividual.onclick = function() {
    modalUploadIndividual.style.display = 'flex';
};
btnCancelarUploadIndividual.onclick = function() {
    modalUploadIndividual.style.display = 'none';
};
formUploadIndividual.onsubmit = function(e) {
    e.preventDefault();
    const files = document.getElementById('input-arquivo-individual').files;
    const descricao = document.getElementById('input-descricao-individual').value;
    if (!files.length) return;

    modalUploadIndividual.style.display = 'flex';

    const totalFiles = files.length;
    const updateProgress = ({ percent, fileIndex }) => {
        document.getElementById('progress-bar-individual').style.width = percent + '%';
        document.getElementById('progress-text-individual').innerText = `Enviando ${fileIndex}/${totalFiles} arquivos (${percent}%)`;
    };

    uploadArquivosViaStreaming(
        files,
        (file) => {
            const base = pastaAtual ? pastaAtual.replace(/\/$/, '') + '/' : '';
            return (base + file.name).replace(/^\//, '');
        },
        descricao,
        updateProgress
    )
    .then(() => {
        modalUploadIndividual.style.display = 'none';
        buscarArquivos();
    })
    .catch(error => {
        modalUploadIndividual.style.display = 'none';
        alert('Erro ao enviar arquivos: ' + error.message);
    });
};

function uploadArquivosViaStreaming(files, resolveRelativePath, descricao, progressCallback) {
    if (!window.StreamingUpload) {
        throw new Error('StreamingUpload não disponível.');
    }
    const client = window.StreamingUpload.getDefaultClient();
    const totalFiles = files.length;

    return client.upload(files, {
        buildRequest: (file, index) => {
            const relativePath = (resolveRelativePath(file) || file.name).replace(/^\//, '');
            return {
                relativePath,
                context: 'arquivos',
                contextPayload: {
                    categoria: idCategoria,
                    descricao: descricao || null,
                    path: relativePath,
                },
                fileIndex: index + 1,
                totalFiles,
            };
        },
        onFileProgress: ({ descriptor, percent }) => {
            if (typeof progressCallback === 'function') {
                progressCallback({
                    percent,
                    fileIndex: descriptor.fileIndex || 1,
                    totalFiles: descriptor.totalFiles || totalFiles,
                });
            }
        },
    });
}
</script>
@endsection 