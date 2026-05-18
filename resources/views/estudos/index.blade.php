@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <div class="flex">
                <button id="btn-estudos" class="bg-blue-600 text-white px-4 py-2 rounded-l hover:bg-blue-700 font-bold">Estudos</button>
                <button id="btn-pesquisas" class="bg-blue-600 text-white px-4 py-2 rounded-r hover:bg-blue-700">Pesquisas</button>
            </div>
            <div class="flex items-center gap-4">
                <input id="pesquisa-estudos-pesquisas" type="text" placeholder="Pesquisar..." class="border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400" />
                <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700" onclick="abrirModalNovo()">Novo Conteúdo</button>
            </div>
        </div>
        <div id="cards-area"></div>
        <div id="pagination-area" class="mt-6 flex justify-center gap-2"></div>
    </div>
</div>
<!-- Modal de cadastro (exemplo simples) -->
<div id="modal-novo" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded shadow-lg w-full max-w-lg">
        <form id="form-novo-conteudo" method="POST" enctype="multipart/form-data">
            @csrf
            <h2 class="text-xl font-bold mb-4" id="modal-titulo">Novo Estudo</h2>
            <input type="text" name="titulo" placeholder="Título" class="w-full mb-2 border p-2 rounded" required>
            <input type="text" name="descricao" placeholder="Descrição" class="w-full mb-2 border p-2 rounded" required>
            <input type="text" name="autor" placeholder="Autor" class="w-full mb-2 border p-2 rounded" required>
            <input type="date" name="data" class="w-full mb-2 border p-2 rounded" required>
            <input type="text" name="tag" placeholder="Tag (opcional)" class="w-full mb-2 border p-2 rounded">
            <input type="hidden" name="capa_stream_path" id="estudo-capa-stream-path">
            <input type="file" name="capa" class="w-full mb-2 border p-2 rounded" required>
            <p id="estudo-upload-status" class="text-sm text-gray-600 mb-2 hidden"></p>
            <textarea name="conteudo" placeholder="Conteúdo" class="w-full mb-2 border p-2 rounded" rows="4" required></textarea>
            <div class="flex justify-end">
                <button type="button" onclick="fecharModalNovo()" class="mr-2 px-4 py-2 bg-gray-300 rounded">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Salvar</button>
            </div>
        </form>
    </div>
</div>
<script>
let abaAtual = 'estudos';

function carregarCards(tipo, page = 1, search = '') {
    abaAtual = tipo;
    let url = tipo === 'estudos' ? '{{ route('ajax.estudos') }}' : '{{ route('ajax.pesquisas') }}';
    url += '?page=' + page;
    if (search.trim()) {
        url += '&search=' + encodeURIComponent(search);
    }
    fetch(url)
        .then(response => response.json())
        .then(json => {
            renderizarCards(json.data, tipo);
            renderizarPaginacao(json, tipo, search);
            // Atualiza o destaque visual das abas
            if (tipo === 'estudos') {
                document.getElementById('btn-estudos').classList.add('font-bold');
                document.getElementById('btn-pesquisas').classList.remove('font-bold');
            } else {
                document.getElementById('btn-pesquisas').classList.add('font-bold');
                document.getElementById('btn-estudos').classList.remove('font-bold');
            }
        });
}

function renderizarCards(cards, tipo) {
    let html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">';
    for (const card of cards) {
        html += `
            <a href="/${tipo}/${card.id}" class="block bg-white rounded-lg shadow p-4 hover:bg-blue-50 transition cursor-pointer">
                <img src="/storage/${card.capa}" alt="Capa" class="w-full h-40 object-cover rounded mb-2">
                <h3 class="text-xl font-bold">${card.titulo}</h3>
                <p class="text-gray-600">${card.descricao ?? ''}</p>
                <div class="flex justify-between items-center mt-2">
                    <span class="text-sm text-gray-500">${card.autor}</span>
                    <span class="text-sm text-gray-400">${(card.data ?? '').split('-').reverse().join('/')}</span>
                </div>
            </a>
        `;
    }
    html += '</div>';
    document.getElementById('cards-area').innerHTML = html;
}

function renderizarPaginacao(json, tipo, search = '') {
    let html = '';
    if (json.prev_page_url) {
        html += `<button class="px-3 py-1 bg-gray-200 rounded" onclick="carregarCards('${tipo}', ${json.current_page - 1}, '${search}')">&laquo; Anterior</button>`;
    }
    html += `<span class="px-3 py-1">${json.current_page} / ${json.last_page}</span>`;
    if (json.next_page_url) {
        html += `<button class="px-3 py-1 bg-gray-200 rounded" onclick="carregarCards('${tipo}', ${json.current_page + 1}, '${search}')">Próxima &raquo;</button>`;
    }
    document.getElementById('pagination-area').innerHTML = html;
}

document.getElementById('btn-estudos').onclick = function() { 
    document.getElementById('pesquisa-estudos-pesquisas').value = '';
    carregarCards('estudos'); 
}
document.getElementById('btn-pesquisas').onclick = function() { 
    document.getElementById('pesquisa-estudos-pesquisas').value = '';
    carregarCards('pesquisas'); 
}

// Event listener para busca
document.getElementById('pesquisa-estudos-pesquisas').addEventListener('input', function() {
    const searchTerm = this.value;
    carregarCards(abaAtual, 1, searchTerm);
});

// Carrega estudos por padrão ao abrir a página
carregarCards('estudos');

function abrirModalNovo() {
    document.getElementById('modal-novo').classList.remove('hidden');
    resetNovoConteudoForm();
    atualizarActionModal();
}

function fecharModalNovo() {
    document.getElementById('modal-novo').classList.add('hidden');
}

function resetNovoConteudoForm() {
    const form = document.getElementById('form-novo-conteudo');
    const statusEl = document.getElementById('estudo-upload-status');
    const hiddenInput = document.getElementById('estudo-capa-stream-path');
    if (form) {
        delete form.dataset.streamingReady;
    }
    if (statusEl) {
        statusEl.classList.add('hidden');
        statusEl.textContent = '';
    }
    if (hiddenInput) {
        hiddenInput.value = '';
    }
    const fileInput = form?.querySelector('input[name="capa"]');
    if (fileInput) {
        fileInput.value = '';
    }
}

function atualizarActionModal() {
    const form = document.getElementById('form-novo-conteudo');
    const titulo = document.getElementById('modal-titulo');
    if (abaAtual === 'estudos') {
        form.action = "{{ route('estudos.store') }}";
        titulo.innerText = 'Novo Estudo';
    } else {
        form.action = "{{ route('pesquisas.store') }}";
        titulo.innerText = 'Nova Pesquisa';
    }
}

function ativarAbaEstudos() {
    abaAtual = 'estudos';
    btnEstudos.classList.add('font-bold');
    btnPesquisas.classList.remove('font-bold');
    estudosArea.classList.remove('hidden');
    pesquisasArea.classList.add('hidden');
    atualizarActionModal();
}
function ativarAbaPesquisas() {
    abaAtual = 'pesquisas';
    btnPesquisas.classList.add('font-bold');
    btnEstudos.classList.remove('font-bold');
    pesquisasArea.classList.remove('hidden');
    estudosArea.classList.add('hidden');
    atualizarActionModal();
}

btnEstudos.onclick = ativarAbaEstudos;
btnPesquisas.onclick = ativarAbaPesquisas;

// Ativa a aba correta ao carregar a página
if (abaAtual === 'pesquisas') {
    ativarAbaPesquisas();
} else {
    ativarAbaEstudos();
}

(function() {
    const form = document.getElementById('form-novo-conteudo');
    const fileInput = form?.querySelector('input[name="capa"]');
    const hiddenPathInput = document.getElementById('estudo-capa-stream-path');
    const statusEl = document.getElementById('estudo-upload-status');
    const tituloInput = form?.querySelector('input[name="titulo"]');
    let isUploading = false;

    if (!form || !fileInput || !hiddenPathInput) {
        return;
    }

    form.addEventListener('submit', async function(event) {
        if (form.dataset.streamingReady === 'true') {
            delete form.dataset.streamingReady;
            return;
        }

        event.preventDefault();

        if (!fileInput.files || !fileInput.files.length) {
            updateEstudoStatus('Selecione a capa antes de salvar.', 'error');
            return;
        }

        if (!window.StreamingUpload) {
            updateEstudoStatus('Streaming não configurado no navegador.', 'error');
            return;
        }

        if (isUploading) {
            return;
        }

        isUploading = true;
        try {
            const client = window.StreamingUpload.getDefaultClient();
            const file = fileInput.files[0];
            const finalName = buildEstudoFileName((tituloInput?.value || 'conteudo'), file.name);

            const [result] = await client.upload([file], {
                buildRequest: () => ({
                    relativePath: finalName,
                    fileName: finalName,
                    context: 'public',
                    contextPayload: {
                        target_directory: 'capas',
                        final_name: finalName,
                        original_name: file.name,
                        preserve_name: true,
                    },
                    fileIndex: 1,
                    totalFiles: 1,
                }),
                onFileProgress: ({ percent }) => {
                    const pct = Number(percent || 0).toFixed(1);
                    updateEstudoStatus(`Enviando capa... ${pct}%`);
                },
            });

            const contextInfo = result?.context || {};
            const finalPath = contextInfo.final_relative_path || result?.filePath || result?.relativePath;
            if (!finalPath) {
                throw new Error('O servidor não retornou o caminho final da capa.');
            }

            hiddenPathInput.value = finalPath.replace(/^\/+/, '');
            fileInput.value = '';
            updateEstudoStatus('Capa enviada com sucesso!', 'success');
            form.dataset.streamingReady = 'true';
            form.submit();
        } catch (error) {
            updateEstudoStatus(error.message || 'Erro ao enviar a capa.', 'error');
        } finally {
            isUploading = false;
        }
    });

    function buildEstudoFileName(prefix, originalName) {
        const safePrefix = slugify(prefix || 'conteudo');
        const extension = originalName && originalName.includes('.') ? '.' + originalName.split('.').pop() : '';
        return `${safePrefix}-${Date.now()}${extension}`;
    }

    function slugify(text) {
        return (text || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-zA-Z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .toLowerCase() || 'conteudo';
    }

    function updateEstudoStatus(message, type = 'info') {
        if (!statusEl) {
            if (type === 'error') {
                alert(message);
            }
            return;
        }
        if (!message) {
            statusEl.classList.add('hidden');
            statusEl.textContent = '';
            return;
        }
        statusEl.classList.remove('hidden');
        const className = type === 'error' ? 'text-red-600' : type === 'success' ? 'text-green-600' : 'text-gray-600';
        statusEl.className = `text-sm ${className} mb-2`;
        statusEl.textContent = message;
    }
})();
</script>
@endsection 